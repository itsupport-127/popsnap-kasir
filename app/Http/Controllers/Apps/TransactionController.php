<?php

namespace App\Http\Controllers\Apps;

use App\Exceptions\PaymentGatewayException;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Customer;
use App\Models\LoyaltyPointHistory;
use App\Models\Promo;
use App\Models\PaymentSetting;
use App\Models\Product;
use App\Models\Receivable;
use App\Models\StockMutation;
use App\Models\Transaction;
use App\Services\AuditLogService;
use App\Services\CashierShiftService;
use App\Services\LoyaltyService;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\PromoService;
use App\Services\StockMutationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class TransactionController extends Controller
{
    public function __construct(
        private readonly CashierShiftService $cashierShiftService,
        private readonly AuditLogService $auditLogService,
        private readonly PromoService $promoService,
        private readonly LoyaltyService $loyaltyService,
        private readonly StockMutationService $stockMutationService
    ) {}

    /**
     * Ambil model Promo dari daftar voucher_id yang dikirim kasir.
     *
     * PENTING: $ids BOLEH punya id yang sama berkali-kali — itu berarti
     * kode voucher itu di-apply lebih dari 1x dalam transaksi ini (mis.
     * customer menang kode PANJAT17 sebanyak 2x lewat undian, di-set lewat
     * counter +/- di kasir). Kalau kita pakai Promo::whereIn(...)->get()
     * langsung, hasilnya otomatis ke-dedupe jadi 1 baris per id (perilaku
     * SQL IN / Eloquent), jadi kemenangan ke-2 dst hilang begitu aja —
     * itu penyebab total nggak ke-update pas counter dinaikin. Di sini kita
     * fetch modelnya sekali per id unik, lalu susun ulang list-nya sesuai
     * urutan & JUMLAH kemunculan id di $ids supaya tiap kemenangan tetap
     * dihitung sendiri-sendiri oleh LoyaltyService::previewCheckout().
     *
     * @param  array<int>  $ids
     * @return \Illuminate\Support\Collection<int, Promo>
     */
    private function resolveVouchersByIds(array $ids): \Illuminate\Support\Collection
    {
        $ids = array_values(array_filter(array_map('intval', $ids)));

        if (empty($ids)) {
            return collect();
        }

        $promosById = Promo::whereIn('id', array_unique($ids))->get()->keyBy('id');

        return collect($ids)
            ->map(fn (int $id) => $promosById->get($id))
            ->filter();
    }

    /**
     * index
     *
     * @return void
     */
    public function index()
    {
        $userId = auth()->user()->id;
        $activeShift = $this->cashierShiftService->getActiveShiftForUser($userId);

        // Get active cart items (not held)
        $carts = Cart::with(['product', 'customer:id,name,is_loyalty_member,loyalty_tier,member_code'])
            ->where('cashier_id', $userId)
            ->active()
            ->latest()
            ->get();

        // Pelanggan yang sedang aktif di keranjang (kalau ada), supaya
        // tetap muncul walau kasir pindah halaman lalu kembali lagi.
        $activeCustomer = $carts->first()?->customer;

        $initialPricingPreview = $this->loyaltyService->previewCheckout(
            $this->promoService->previewCart($carts, null)
        );


        // Get held carts grouped by hold_id
        $heldCarts = Cart::with('product:id,title,sell_price,image')
            ->where('cashier_id', $userId)
            ->held()
            ->get()
            ->groupBy('hold_id')
            ->map(function ($items, $holdId) {
                $first = $items->first();
                $customer = $first->customer_id
                    ? \App\Models\Customer::select('id', 'name')->find($first->customer_id)
                    : null;

                return [
                    'hold_id' => $holdId,
                    'label' => $first->hold_label,
                    'held_at' => $first->held_at?->toISOString(),
                    'items_count' => $items->sum('qty'),
                    'total' => $items->sum('price'),
                    'customer_id' => $first->customer_id,
                    'customer_name' => $customer?->name,
                ];
            })
            ->values();

        // get all customers
        $customers = Customer::latest()->get();

        // get all products with categories for product grid — cuma yang
        // is_sellable (bukan bahan/aksesoris internal doang) yang muncul
        // di kasir.
        $products = Product::with('category:id,name')
            ->select('id', 'barcode', 'title', 'description', 'image', 'buy_price', 'sell_price', 'stock', 'category_id')
            ->where('stock', '>', 0)
            ->where('is_sellable', true)
            ->orderBy('title')
            ->get();
        $pricingBadges = $this->promoService->previewProducts($products, null);
        $products = $products->map(function (Product $product) use ($pricingBadges) {
            $pricing = $pricingBadges->get($product->id);

            return [
                ...$product->toArray(),
                'pricing_badge' => $pricing && ! empty($pricing['promo']) ? [
                    'label' => $pricing['promo']['label'],
                    'promo_price' => $pricing['promo']['price_context']
                        ? $pricing['effective_unit_price']
                        : null,
                    'base_price' => $pricing['base_unit_price'],
                    'kind' => $pricing['promo']['discount_kind'],
                ] : null,
            ];
        });

        // get all categories
        $categories = \App\Models\Category::select('id', 'name', 'image')
            ->orderBy('name')
            ->get();

        $paymentSetting = PaymentSetting::first();

        $carts_total = 0;
        foreach ($carts as $cart) {
            $carts_total += $cart->price;
        }

        $defaultGateway = $paymentSetting?->default_gateway ?? 'cash';
        if (
            $defaultGateway !== 'cash'
            && (! $paymentSetting || ! $paymentSetting->isGatewayReady($defaultGateway))
        ) {
            $defaultGateway = 'cash';
        }

        // Get active bank accounts for bank transfer
        $bankAccounts = \App\Models\BankAccount::active()->ordered()->get();

        return Inertia::render('Dashboard/Transactions/Index', [
            'carts' => $carts,
            'carts_total' => $carts_total,
            'heldCarts' => $heldCarts,
            'activeCustomer' => $activeCustomer,
            'editingTransactionId' => request()->query('editing') ? (int) request()->query('editing') : null,
            'editingInvoice' => request()->query('editing_invoice'),
            'customers' => $customers,
            'products' => $products,
            'categories' => $categories,
            'initialPricingPreview' => $initialPricingPreview,
            'paymentGateways' => $paymentSetting?->enabledGateways() ?? [],
            'defaultPaymentGateway' => $defaultGateway,
            'bankAccounts' => $bankAccounts,
            'qrisStaticConfig' => $paymentSetting?->qrisStaticConfig() ?? null,
            'shiftSummary' => $this->cashierShiftService->summarizeForDisplay($activeShift),
            'loyaltyTierOptions'  => $this->loyaltyService->tierOptions(),
            'memberRegistrationEnabled' => \App\Models\Setting::getBool('member_registration_pos_enabled', true),
            'generalVouchers'     => Promo::query()
                ->where('activation_mode', Promo::ACTIVATION_MANUAL)
                ->whereNull('customer_id')
                ->where('is_active', true)
                ->where(function ($q) {
                    $q->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                })
                ->get()
                ->filter(fn (Promo $promo) => $promo->usageAvailable())
                ->map(fn (Promo $promo) => $this->promoService->serializeManualPromo($promo))
                ->values(),
        ]);
    }

    /**
     * searchProduct
     *
     * @param  mixed  $request
     * @return void
     */
    public function searchProduct(Request $request)
    {
        // find product by barcode — cuma produk is_sellable yang boleh
        // masuk keranjang lewat scan barcode di kasir.
        $product = Product::where('barcode', $request->barcode)
            ->where('is_sellable', true)
            ->first();

        if ($product) {
            return response()->json([
                'success' => true,
                'data' => $product,
            ]);
        }

        return response()->json([
            'success' => false,
            'data' => null,
        ]);
    }

    public function previewPricing(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'discount' => ['nullable', 'integer', 'min:0'],
            'shipping_cost' => ['nullable', 'integer', 'min:0'],
            'redeem_points' => ['nullable', 'integer', 'min:0'],
            // Voucher tunggal (lama) tetap didukung, plus array voucher (baru)
            // supaya kasir bisa pakai lebih dari satu voucher sekaligus.
            'customer_voucher_id' => ['nullable', 'integer', 'exists:promos,id'],
            'customer_voucher_ids' => ['nullable', 'array'],
            'customer_voucher_ids.*' => ['integer', 'exists:promos,id'],
            // Metode bayar yang lagi dipilih kasir — dipakai buat filter
            // promo yang punya syarat "hanya cashless"/"hanya cash".
            'payment_method' => ['nullable', 'string'],
            // Diskon manual PER ITEM — kasir pilih 1 barang di keranjang,
            // kasih diskon Rp/% khusus buat barang itu (beda dari 'discount'
            // di atas yang motong TOTAL keranjang).
            'item_discounts' => ['nullable', 'array'],
            'item_discounts.*.cart_id' => ['required', 'integer'],
            'item_discounts.*.mode' => ['required', Rule::in(['nominal', 'percent'])],
            'item_discounts.*.value' => ['required', 'numeric', 'min:0'],
        ]);

        $customer = isset($validated['customer_id'])
            ? Customer::find($validated['customer_id'])
            : null;
        $voucherIds = ! empty($validated['customer_voucher_ids'])
            ? $validated['customer_voucher_ids']
            : (isset($validated['customer_voucher_id']) ? [$validated['customer_voucher_id']] : []);
        $vouchers = $this->resolveVouchersByIds($voucherIds);

        $carts = Cart::with('product.category')
            ->where('cashier_id', $request->user()->id)
            ->active()
            ->latest()
            ->get();

        $pricingPreview = $this->promoService->previewCart($carts, $customer);

        return response()->json([
            'success' => true,
            'data' => $this->loyaltyService->previewCheckout($pricingPreview, $customer, [
                'manual_discount' => (int) ($validated['discount'] ?? 0),
                'shipping_cost' => (int) ($validated['shipping_cost'] ?? 0),
                'redeem_points' => (int) ($validated['redeem_points'] ?? 0),
                'vouchers' => $vouchers,
                'payment_method' => $validated['payment_method'] ?? null,
                'item_discounts' => $validated['item_discounts'] ?? [],
            ]),
        ]);
    }

    /**
     * addToCart
     *
     * @param  mixed  $request
     * @return void
     */
    public function addToCart(Request $request)
    {
        // Cari produk berdasarkan ID yang diberikan
        $product = Product::whereId($request->product_id)->first();

        // Jika produk tidak ditemukan, redirect dengan pesan error
        if (! $product) {
            return redirect()->back()->with('error', 'Product not found.');
        }

        // Produk internal (is_sellable=false) sengaja disembunyikan dari
        // pencarian kasir — ini jaga-jaga tambahan biar nggak bisa masuk
        // keranjang lewat cara lain juga (misal request manual).
        if (! $product->is_sellable) {
            return redirect()->back()->with('error', 'Produk ini bukan buat dijual, jadi nggak bisa ditambahkan ke keranjang.');
        }

        // Cek stok produk
        if ($product->stock < $request->qty) {
            return redirect()->back()->with('error', 'Out of Stock Product!.');
        }

        // Cek keranjang — hanya cart aktif (bukan yang sedang di-hold)
        $cart = Cart::with('product')
            ->where('product_id', $request->product_id)
            ->where('cashier_id', auth()->user()->id)
            ->active()
            ->first();

        if ($cart) {
            // Tingkatkan qty
            $cart->increment('qty', $request->qty);

            // Jumlahkan harga * kuantitas
            $cart->price = $cart->product->sell_price * $cart->qty;

            $cart->save();
        } else {
            // Insert ke keranjang
            Cart::create([
                'cashier_id' => auth()->user()->id,
                'product_id' => $request->product_id,
                'qty' => $request->qty,
                'price' => $request->sell_price * $request->qty,
            ]);
        }

        return redirect()->route('transactions.index')->with('success', 'Product Added Successfully!.');
    }

    /**
     * destroyCart
     *
     * @param  mixed  $request
     * @return void
     */
    public function destroyCart($cart_id)
    {
        $cart = Cart::with('product')->whereId($cart_id)->first();

        if ($cart) {
            $cart->delete();

            return back();
        } else {
            // Handle case where no cart is found (e.g., redirect with error message)
            return back()->withErrors(['message' => 'Cart not found']);
        }

    }

    /**
     * updateCart - Update cart item quantity
     *
     * @param  mixed  $request
     * @param  int  $cart_id
     * @return void
     */
    public function updateCart(Request $request, $cart_id)
    {
        $request->validate([
            'qty' => 'required|integer|min:1',
        ]);

        $cart = Cart::with('product')->whereId($cart_id)
            ->where('cashier_id', auth()->user()->id)
            ->first();

        if (! $cart) {
            return response()->json([
                'success' => false,
                'message' => 'Cart item not found',
            ], 404);
        }

        // Check stock availability
        if ($cart->product->stock < $request->qty) {
            return response()->json([
                'success' => false,
                'message' => 'Stok tidak mencukupi. Tersedia: '.$cart->product->stock,
            ], 422);
        }

        // Update quantity and price
        $cart->qty = $request->qty;
        $cart->price = $cart->product->sell_price * $request->qty;
        $cart->save();

        return back()->with('success', 'Quantity updated successfully');
    }

    /**
     * Batalkan mode edit transaksi — kosongkan lagi keranjang yang tadi
     * diisi otomatis dari isi transaksi lama, tanpa menyimpan apa-apa.
     */
    public function cancelEdit()
    {
        Cart::where('cashier_id', auth()->user()->id)->active()->delete();

        return redirect()->route('transactions.index');
    }

    /**
     * holdCart - Hold current cart items for later
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function holdCart(Request $request)
    {
        $request->validate([
            'label' => 'nullable|string|max:50',
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        $userId = auth()->user()->id;

        // Get active cart items
        $activeCarts = Cart::where('cashier_id', $userId)
            ->active()
            ->get();

        if ($activeCarts->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Keranjang kosong, tidak ada yang bisa ditahan',
            ], 422);
        }

        // Generate unique hold ID
        $holdId = 'HOLD-'.strtoupper(uniqid());
        $label = $request->label ?: 'Transaksi '.now()->format('H:i');

        // Mark all active cart items as held, sekaligus simpan pelanggan
        // yang sedang dipilih supaya tidak hilang saat dilanjutkan nanti.
        Cart::where('cashier_id', $userId)
            ->active()
            ->update([
                'hold_id' => $holdId,
                'hold_label' => $label,
                'held_at' => now(),
                'customer_id' => $request->customer_id,
            ]);

        return back()->with('success', 'Transaksi ditahan: '.$label);
    }

    /**
     * setCartCustomer - Simpan pelanggan yang dipilih ke keranjang aktif,
     * supaya tetap ingat siapa pelanggannya walau kasir pindah halaman
     * atau browser di-refresh.
     */
    public function setCartCustomer(Request $request)
    {
        $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
        ]);

        $userId = auth()->user()->id;

        Cart::where('cashier_id', $userId)
            ->active()
            ->update(['customer_id' => $request->customer_id]);

        return back();
    }

    /**
     * resumeCart - Resume a held cart
     *
     * @param  string  $holdId
     * @return \Illuminate\Http\JsonResponse
     */
    public function resumeCart($holdId)
    {
        $userId = auth()->user()->id;

        // Check if there are any active carts (not held)
        $activeCarts = Cart::where('cashier_id', $userId)
            ->active()
            ->count();

        if ($activeCarts > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Selesaikan atau tahan transaksi aktif terlebih dahulu',
            ], 422);
        }

        // Get held carts
        $heldCarts = Cart::where('cashier_id', $userId)
            ->forHold($holdId)
            ->get();

        if ($heldCarts->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Transaksi ditahan tidak ditemukan',
            ], 404);
        }

        // Resume by clearing hold info
        Cart::where('cashier_id', $userId)
            ->forHold($holdId)
            ->update([
                'hold_id' => null,
                'hold_label' => null,
                'held_at' => null,
            ]);

        return back()->with('success', 'Transaksi dilanjutkan');
    }

    /**
     * clearHold - Delete a held cart
     *
     * @param  string  $holdId
     * @return \Illuminate\Http\JsonResponse
     */
    public function clearHold($holdId)
    {
        $userId = auth()->user()->id;

        $deleted = Cart::where('cashier_id', $userId)
            ->forHold($holdId)
            ->delete();

        if ($deleted === 0) {
            return request()->wantsJson()
                ? response()->json([
                    'success' => false,
                    'message' => 'Transaksi ditahan tidak ditemukan',
                ], 404)
                : back()->with('error', 'Transaksi ditahan tidak ditemukan');
        }

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Transaksi ditahan berhasil dihapus',
            ]);
        }

        return back()->with('success', 'Transaksi ditahan berhasil dihapus');
    }

    /**
     * getHeldCarts - Get all held carts for current user
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getHeldCarts()
    {
        $userId = auth()->user()->id;

        $heldCarts = Cart::with('product:id,title,sell_price,image')
            ->where('cashier_id', $userId)
            ->held()
            ->get()
            ->groupBy('hold_id')
            ->map(function ($items, $holdId) {
                $first = $items->first();

                return [
                    'hold_id' => $holdId,
                    'label' => $first->hold_label,
                    'held_at' => $first->held_at,
                    'items_count' => $items->sum('qty'),
                    'total' => $items->sum('price'),
                    'items' => $items->map(fn ($item) => [
                        'id' => $item->id,
                        'product' => $item->product,
                        'qty' => $item->qty,
                        'price' => $item->price,
                    ]),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'held_carts' => $heldCarts,
        ]);
    }

    /**
     * store
     *
     * @param  mixed  $request
     * @return void
     */
    public function store(Request $request, PaymentGatewayManager $paymentGatewayManager)
    {
        $isPayLater     = $request->boolean('pay_later');
        $paymentGateway = $isPayLater ? null : $request->input('payment_gateway');
        if ($paymentGateway) {
            $paymentGateway = strtolower($paymentGateway);
        }

        $isDualPayment  = $paymentGateway === 'dual_cash_qris';
        $dualCash       = $isDualPayment ? max(0, (int) $request->input('dual_cash', 0)) : 0;
        $dualQris       = $isDualPayment ? max(0, (int) $request->input('dual_qris', 0)) : 0;

        $paymentSetting = null;

        if ($isPayLater && ! $request->filled('due_date')) {
            return redirect()
                ->route('transactions.index')
                ->with('error', 'Tanggal jatuh tempo wajib diisi untuk nota barang.');
        }

        // Validasi gateway — skip untuk dual_cash_qris dan gateway kosong
        if ($paymentGateway && ! $isDualPayment) {
            $paymentSetting = PaymentSetting::first();

            if (! $paymentSetting || ! $paymentSetting->isGatewayReady($paymentGateway)) {
                return redirect()
                    ->route('transactions.index')
                    ->with('error', 'Gateway pembayaran belum dikonfigurasi.');
            }
        }

        $length = 10;
        $random = '';
        for ($i = 0; $i < $length; $i++) {
            $random .= rand(0, 1) ? rand(0, 9) : chr(rand(ord('a'), ord('z')));
        }

        $invoice = 'TRX-'.Str::upper($random);
        $isCashPayment  = empty($paymentGateway) && ! $isPayLater && ! $isDualPayment;
        $isQrisStatic   = $paymentGateway === 'qris_static';
        $manualDiscount = max(0, (int) $request->input('discount', 0));
        // Diskon manual PER ITEM — array {cart_id, mode, value} dari kasir.
        // Disaring manual (bukan pakai $request->validate() penuh, mengikuti
        // pola input-input lain di method ini) supaya entri yang aneh-aneh
        // (cart_id bukan angka, mode bukan nominal/percent) nggak ikut lolos.
        $itemDiscounts = collect($request->input('item_discounts', []))
            ->filter(fn ($entry) => is_array($entry) && isset($entry['cart_id']))
            ->map(fn ($entry) => [
                'cart_id' => (int) $entry['cart_id'],
                'mode' => ($entry['mode'] ?? null) === 'percent' ? 'percent' : 'nominal',
                'value' => max(0, (float) ($entry['value'] ?? 0)),
            ])
            ->filter(fn ($entry) => $entry['value'] > 0)
            ->values()
            ->all();
        $shippingCost   = max(0, (int) $request->input('shipping_cost', 0));
        $requestedRedeemPoints = max(0, (int) $request->input('redeem_points', 0));
        $cashAmount = $isCashPayment
            ? max(0, (int) $request->cash)
            : ($isDualPayment ? $dualCash : 0);
        $customer = $request->filled('customer_id')
            ? Customer::find($request->integer('customer_id'))
            : null;
        // Terima 'customer_voucher_ids' (array, multi-voucher) — tetap fallback
        // ke 'customer_voucher_id' tunggal untuk kompatibilitas mundur.
        $requestedVoucherIds = $request->input('customer_voucher_ids');
        $requestedVoucherIds = is_array($requestedVoucherIds) ? array_filter(array_map('intval', $requestedVoucherIds)) : [];
        if (empty($requestedVoucherIds) && $request->filled('customer_voucher_id')) {
            $requestedVoucherIds = [$request->integer('customer_voucher_id')];
        }
        $vouchers = $this->resolveVouchersByIds($requestedVoucherIds);

        $transaction = DB::transaction(function () use (
            $request,
            $invoice,
            $cashAmount,
            $paymentGateway,
            $isCashPayment,
            $isQrisStatic,
            $isDualPayment,
            $dualCash,
            $dualQris,
            $isPayLater,
            $manualDiscount,
            $shippingCost,
            $requestedRedeemPoints,
            $customer,
            $vouchers,
            $itemDiscounts
        ) {
            $carts = Cart::with('product')
                ->where('cashier_id', auth()->user()->id)
                ->active()
                ->get();

            return $this->persistTransactionFromCart($carts, $customer, $vouchers, [
                'invoice' => $invoice,
                'cashAmount' => $cashAmount,
                'paymentGateway' => $paymentGateway,
                'isCashPayment' => $isCashPayment,
                'isQrisStatic' => $isQrisStatic,
                'isDualPayment' => $isDualPayment,
                'dualCash' => $dualCash,
                'dualQris' => $dualQris,
                'isPayLater' => $isPayLater,
                'manualDiscount' => $manualDiscount,
                'shippingCost' => $shippingCost,
                'requestedRedeemPoints' => $requestedRedeemPoints,
                'itemDiscounts' => $itemDiscounts,
                'customerId' => $request->customer_id,
                'dueDate' => $request->due_date,
                'bankAccountId' => $paymentGateway === 'bank_transfer' ? $request->bank_account_id : null,
            ]);
        });

        // Dual payment dan QRIS statis tidak butuh API call ke gateway —
        // konfirmasi dilakukan manual oleh kasir. Langsung redirect ke struk.
        if ($paymentGateway && ! $isDualPayment) {
            try {
                $paymentResponse = $paymentGatewayManager->createPayment($transaction, $paymentGateway, $paymentSetting);

                $transaction->update([
                    'payment_reference' => $paymentResponse['reference'] ?? null,
                    'payment_url'       => $paymentResponse['payment_url'] ?? null,
                ]);
            } catch (PaymentGatewayException $exception) {
                return redirect()
                    ->route('transactions.print', $transaction->invoice)
                    ->with('error', $exception->getMessage());
            }
        }

        return to_route('transactions.print', $transaction->invoice);
    }

    /**
     * Inti dari "checkout": ubah isi keranjang (Cart) jadi baris-baris
     * Transaction + TransactionDetail, potong stok, catat profit & mutasi
     * stok, selesaikan poin loyalti & pemakaian voucher.
     *
     * Dipakai oleh DUA alur:
     * - store()  : $existingTransaction = null  → bikin transaksi baru
     * - update() : $existingTransaction diisi   → EDIT transaksi yang sudah
     *   ada (invoice & cashier_shift_id ASLI dipertahankan — supaya laporan
     *   shift & nomor invoice yang sudah dicetak/dibagikan ke pelanggan
     *   tidak berubah, cuma isinya yang dikoreksi).
     *
     * $opts keys: invoice, cashAmount, paymentGateway, isCashPayment,
     * isQrisStatic, isDualPayment, dualCash, dualQris, isPayLater,
     * manualDiscount, shippingCost, requestedRedeemPoints, itemDiscounts,
     * customerId, dueDate, bankAccountId.
     */
    private function persistTransactionFromCart(
        \Illuminate\Support\Collection $carts,
        ?Customer $customer,
        \Illuminate\Support\Collection $vouchers,
        array $opts,
        ?Transaction $existingTransaction = null
    ): Transaction {
        if ($carts->isEmpty()) {
            abort(422, 'Keranjang kosong.');
        }

        $cashAmount = $opts['cashAmount'];
        $paymentGateway = $opts['paymentGateway'];
        $isCashPayment = $opts['isCashPayment'];
        $isQrisStatic = $opts['isQrisStatic'];
        $isDualPayment = $opts['isDualPayment'];
        $dualCash = $opts['dualCash'];
        $dualQris = $opts['dualQris'];
        $isPayLater = $opts['isPayLater'];
        $manualDiscount = $opts['manualDiscount'];
        $shippingCost = $opts['shippingCost'];
        $requestedRedeemPoints = $opts['requestedRedeemPoints'];
        $itemDiscounts = $opts['itemDiscounts'];

        // Kalau lagi EDIT, transaksi tetap "milik" shift ASLINYA — bukan
        // shift yang sedang aktif sekarang. Kalau ini transaksi BARU, wajib
        // ada shift aktif buat nempelin transaksinya.
        $cashierShiftId = $existingTransaction?->cashier_shift_id;
        if (! $cashierShiftId) {
            $activeShift = $this->cashierShiftService->requireActiveShiftForUser(
                auth()->user()->id,
                lockForUpdate: true
            );
            $cashierShiftId = $activeShift->id;
        }

        $finalPaymentMethod = $isPayLater ? 'pay_later' : ($isDualPayment ? 'dual_cash_qris' : ($paymentGateway ?: 'cash'));
        $pricingPreview = $this->promoService->previewCart($carts, $customer, null, $finalPaymentMethod);

        foreach ($vouchers as $voucherToCheck) {
            if (! $voucherToCheck->matchesPaymentMethod($finalPaymentMethod)) {
                $requirement = $voucherToCheck->payment_method_restriction === 'cashless_only'
                    ? 'pembayaran cashless (non-tunai)'
                    : 'pembayaran tunai';
                abort(422, "Promo \"{$voucherToCheck->name}\" cuma berlaku untuk {$requirement}. Ganti metode bayar atau lepas promonya dulu.");
            }

            // Cek ulang mode "1x per pelanggan" tepat sebelum commit — jaga-
            // jaga kalau antara preview terakhir & submit ini, pelanggan
            // yang sama sempat kepakai kode ini di transaksi lain (mis. 2
            // kasir beda buka POS bersamaan). Kalau lagi EDIT transaksi yang
            // sebelumnya sudah pakai voucher ini, jangan dianggap "dobel".
            if ($voucherToCheck->usage_limit_mode === Promo::USAGE_LIMIT_PER_CUSTOMER
                && $customer
                && $voucherToCheck->used_transaction_id !== $existingTransaction?->id
                && $this->promoService->customerAlreadyUsedPromo($customer, $voucherToCheck)) {
                abort(422, "Promo \"{$voucherToCheck->name}\" cuma bisa dipakai 1x per pelanggan, dan pelanggan ini udah pernah pakai. Lepas promonya dulu.");
            }
        }

        $checkoutPreview = $this->loyaltyService->previewCheckout($pricingPreview, $customer, [
            'manual_discount' => $manualDiscount,
            'shipping_cost' => $shippingCost,
            'redeem_points' => $requestedRedeemPoints,
            'vouchers' => $vouchers,
            'payment_method' => $finalPaymentMethod,
            'item_discounts' => $itemDiscounts,
        ]);
        $pricingItems = collect($pricingPreview['items']);
        $subtotalAfterPromo = (int) data_get($pricingPreview, 'summary.subtotal_after_promo', 0);
        $voucherDiscount = (int) data_get($checkoutPreview, 'summary.voucher_discount_total', 0);
        $voucherDiscountByCartId = collect(data_get($checkoutPreview, 'summary.voucher_discount_by_cart_id', []));
        $voucherNameByCartId = collect(data_get($checkoutPreview, 'summary.voucher_name_by_cart_id', []));
        $voucherPromoIdByCartId = collect(data_get($checkoutPreview, 'summary.voucher_promo_id_by_cart_id', []));
        $itemManualDiscountByCartId = collect(data_get($checkoutPreview, 'summary.item_manual_discount_by_cart_id', []));
        $itemManualDiscountTotal = (int) data_get($checkoutPreview, 'summary.item_manual_discount_total', 0);
        $loyaltyDiscount = (int) data_get($checkoutPreview, 'summary.loyalty_discount_total', 0);
        $appliedManualDiscount = (int) data_get($checkoutPreview, 'summary.manual_discount_total', 0);
        $grandTotal = (int) data_get($checkoutPreview, 'summary.grand_total', 0);
        $changeAmount = $isCashPayment ? max(0, $cashAmount - $grandTotal) : ($isDualPayment ? max(0, ($dualCash + $dualQris) - $grandTotal) : 0);

        $transactionData = [
            'cashier_id' => $existingTransaction->cashier_id ?? auth()->user()->id,
            'cashier_shift_id' => $cashierShiftId,
            'customer_id' => $opts['customerId'],
            'invoice' => $existingTransaction->invoice ?? $opts['invoice'],
            'cash' => $cashAmount,
            'change' => $changeAmount,
            'discount' => $appliedManualDiscount,
            'loyalty_points_redeemed' => (int) data_get($checkoutPreview, 'summary.applied_redeem_points', 0),
            'loyalty_discount_total' => $loyaltyDiscount,
            'customer_voucher_discount' => $voucherDiscount,
            // Sebelumnya dihitung tapi nggak pernah disimpan — akibatnya
            // kolom ini selalu 0 walau ada diskon manual per item, dan
            // struk/laporan yang ngandelin kolom ini jadi salah baca.
            'item_manual_discount_total' => $itemManualDiscountTotal,
            'customer_voucher_code' => collect(data_get($checkoutPreview, 'vouchers', []))->pluck('code')->filter()->implode(', ') ?: null,
            'customer_voucher_name' => collect(data_get($checkoutPreview, 'vouchers', []))->pluck('name')->filter()->implode(', ') ?: null,
            'shipping_cost' => $shippingCost,
            'grand_total' => $grandTotal,
            'payment_method' => $finalPaymentMethod,
            'payment_status' => $isCashPayment || $isQrisStatic || $isDualPayment ? 'paid' : ($isPayLater ? 'unpaid' : 'pending'),
            'dual_cash' => $isDualPayment ? $dualCash : null,
            'dual_qris' => $isDualPayment ? $dualQris : null,
            'bank_account_id' => $opts['bankAccountId'],
        ];

        if ($existingTransaction) {
            $existingTransaction->update($transactionData);
            $transaction = $existingTransaction;
        } else {
            $transaction = Transaction::create($transactionData);
        }

        foreach ($carts as $cart) {
            $pricingItem = $pricingItems->firstWhere('cart_id', $cart->id);
            $lineTotal = (int) data_get($pricingItem, 'line_total', $cart->price);
            $linePromoDiscount = (int) data_get($pricingItem, 'line_discount_total', 0);
            $lineVoucherDiscount = (int) $voucherDiscountByCartId->get($cart->id, 0);
            $lineItemManualDiscount = (int) $itemManualDiscountByCartId->get($cart->id, 0);
            $baseUnitPrice = (int) data_get($pricingItem, 'base_unit_price', $cart->product->sell_price);
            $unitPrice = (int) data_get($pricingItem, 'effective_unit_price', $cart->product->sell_price);
            $lineTotalAfterItemDiscounts = max(0, $lineTotal - $lineVoucherDiscount - $lineItemManualDiscount);

            $transaction->details()->create([
                'transaction_id' => $transaction->id,
                'product_id' => $cart->product_id,
                'qty' => $cart->qty,
                'base_unit_price' => $baseUnitPrice,
                'unit_price' => $unitPrice,
                'price' => $lineTotal,
                'discount_total' => $linePromoDiscount + $lineVoucherDiscount + $lineItemManualDiscount,
                'voucher_discount' => $lineVoucherDiscount,
                'item_manual_discount' => $lineItemManualDiscount,
                'promo_id' => data_get($pricingItem, 'promo.id') ?? $voucherPromoIdByCartId->get($cart->id),
                'pricing_rule_name' => data_get($pricingItem, 'promo.name')
                    ?? (($names = $voucherNameByCartId->get($cart->id)) ? implode(', ', (array) $names) : null)
                    ?? ($lineItemManualDiscount > 0 ? 'Diskon Manual' : null),
                'pricing_rule_kind' => data_get($pricingItem, 'promo.discount_kind'),
                'pricing_group_key' => data_get($pricingItem, 'promo_group_key'),
                'pricing_group_label' => data_get($pricingItem, 'promo_group_label'),
            ]);

            $product = Product::where('id', $cart->product_id)->lockForUpdate()->first();

            if (! $product || $product->stock < $cart->qty) {
                abort(422, 'Stok "'.($product->title ?? 'produk').'" tidak lagi mencukupi. Tersedia: '.($product->stock ?? 0).', diminta: '.$cart->qty.'.');
            }

            if (! $product->is_sellable) {
                abort(422, 'Produk "'.$product->title.'" bukan buat dijual (internal), jadi nggak bisa diselesaikan transaksinya. Hapus dari keranjang dulu.');
            }

            $total_buy_price = $product->buy_price * $cart->qty;
            $lineShare = $subtotalAfterPromo > 0
                ? $lineTotalAfterItemDiscounts / max(1, $subtotalAfterPromo - $voucherDiscount - $itemManualDiscountTotal)
                : 0;
            $allocatedManualDiscount = (int) round($appliedManualDiscount * $lineShare);
            $netSellPrice = max(0, $lineTotalAfterItemDiscounts - $allocatedManualDiscount);
            $profits = $netSellPrice - $total_buy_price;

            $transaction->profits()->create([
                'transaction_id' => $transaction->id,
                'total' => $profits,
            ]);

            $stockBefore = $product->stock;
            $product->stock = $product->stock - $cart->qty;
            $product->save();

            $this->stockMutationService->recordSale(
                $product,
                $transaction,
                $cart->qty,
                $stockBefore,
                $product->stock,
                auth()->user()->id
            );
        }

        Cart::where('cashier_id', auth()->user()->id)->active()->delete();

        $this->loyaltyService->finalizeTransaction($transaction, $customer, $checkoutPreview);

        $appliedVoucherIds = collect(data_get($checkoutPreview, 'vouchers', []))->pluck('id')->all();

        foreach ($vouchers as $appliedVoucher) {
            if (! in_array($appliedVoucher->id, $appliedVoucherIds, true)) {
                continue;
            }

            if ($appliedVoucher->isPersonal()) {
                $appliedVoucher->update([
                    'is_used' => true,
                    'used_at' => now(),
                    'used_transaction_id' => $transaction->id,
                ]);
            } else {
                $newCount = $appliedVoucher->used_count + 1;
                $reachedMax = $appliedVoucher->max_uses && $newCount >= $appliedVoucher->max_uses;
                $appliedVoucher->update([
                    'used_count' => $newCount,
                    'is_used' => $reachedMax,
                    'used_at' => now(),
                    'used_transaction_id' => $transaction->id,
                ]);
            }
        }

        if ($isPayLater) {
            Receivable::updateOrCreate(
                ['transaction_id' => $transaction->id],
                [
                    'customer_id' => $opts['customerId'],
                    'invoice' => $transaction->invoice,
                    'total' => $grandTotal,
                    'paid' => 0,
                    'due_date' => $opts['dueDate'],
                    'status' => 'unpaid',
                ]
            );
        } elseif ($existingTransaction) {
            // Kalau sebelumnya nota barang (pay_later) tapi pas diedit
            // metode bayarnya diganti jadi bukan pay_later lagi, piutang
            // lama yang nggak relevan lagi ini harus ikut dibersihkan.
            Receivable::where('transaction_id', $transaction->id)->delete();
        }

        return $transaction->fresh(['customer']);
    }

    public function print($invoice)
    {
        // get transaction
        $transaction = Transaction::with('details.product', 'details.promo', 'cashier', 'customer', 'receivable', 'bankAccount')
            ->where('invoice', $invoice)
            ->firstOrFail();

        return Inertia::render('Dashboard/Transactions/Print', [
            'transaction' => $transaction,
            'printSettings' => SettingController::printSettings(),
        ]);
    }

    /**
     * Display transaction history.
     */
    public function history(Request $request)
    {
        $salesReturnTablesReady = Schema::hasTable('sales_returns') && Schema::hasTable('sales_return_items');

        $filters = [
            'invoice' => $request->input('invoice'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'category_id' => $request->input('category_id'),
        ];

        $query = Transaction::query()
            ->with([
                'cashier:id,name',
                'cashierShift:id,opened_at,status',
                'customer:id,name',
                'receivable',
                'details.product:id,title,image',
            ])
            ->withSum('details as total_items', 'qty')
            ->withSum('profits as total_profit', 'total')
            ->orderByDesc('created_at');

        if ($salesReturnTablesReady) {
            $query->with('details.salesReturnItems.salesReturn:id,status');
        }

        if (! $request->user()->isSuperAdmin()) {
            $query->where('cashier_id', $request->user()->id);
        }

        $query
            ->when($filters['invoice'], function (Builder $builder, $invoice) {
                $builder->where('invoice', 'like', '%'.$invoice.'%');
            })
            ->when($filters['start_date'], function (Builder $builder, $date) {
                $builder->whereDate('created_at', '>=', $date);
            })
            ->when($filters['end_date'], function (Builder $builder, $date) {
                $builder->whereDate('created_at', '<=', $date);
            })
            ->when($filters['category_id'], function (Builder $builder, $categoryId) {
                // Cuma tampilkan transaksi yang punya minimal 1 item dari kategori ini
                // (mis. "Add Print", "Keychain", "Studio 2 Popsnap", dst).
                $builder->whereHas('details.product', function (Builder $productQuery) use ($categoryId) {
                    $productQuery->where('category_id', $categoryId);
                });
            });

        $transactions = $query->paginate(10)->withQueryString();
        $transactions->through(function (Transaction $transaction) use ($salesReturnTablesReady) {
            $canCreateSalesReturn = false;

            if ($salesReturnTablesReady) {
                $allReturned = true;

                foreach ($transaction->details as $detail) {
                    $returnedQty = (int) $detail->salesReturnItems
                        ->filter(fn ($item) => $item->salesReturn?->status === 'completed')
                        ->sum('qty_return');

                    if ($returnedQty < (int) $detail->qty) {
                        $allReturned = false;
                        break;
                    }
                }

                $canCreateSalesReturn = $transaction->details->isNotEmpty() && ! $allReturned;
            }

            return [
                ...$transaction->toArray(),
                'can_create_sales_return' => $canCreateSalesReturn,
            ];
        });

        return Inertia::render('Dashboard/Transactions/History', [
            'transactions' => $transactions,
            'filters' => $filters,
            'salesReturnFeatureReady' => $salesReturnTablesReady,
            'categories' => \App\Models\Category::select('id', 'name')->orderBy('name')->get(),
        ]);
    }

    /**
     * Confirm payment for bank transfer transactions
     */
    public function confirmPayment(Transaction $transaction)
    {
        if ($transaction->payment_status === 'paid') {
            return redirect()
                ->back()
                ->with('error', 'Transaksi sudah dibayar.');
        }

        $beforeStatus = $transaction->payment_status;
        $transaction->update([
            'payment_status' => 'paid',
        ]);

        $this->auditLogService->log(
            event: 'transaction.payment_confirmed',
            module: 'transactions',
            auditable: $transaction,
            description: "Pembayaran untuk invoice {$transaction->invoice} dikonfirmasi.",
            before: [
                'invoice' => $transaction->invoice,
                'payment_method' => $transaction->payment_method,
                'payment_status' => $beforeStatus,
                'bank_account_id' => $transaction->bank_account_id,
            ],
            after: [
                'invoice' => $transaction->invoice,
                'payment_method' => $transaction->payment_method,
                'payment_status' => 'paid',
                'bank_account_id' => $transaction->bank_account_id,
            ],
            meta: [
                'invoice' => $transaction->invoice,
                'bank_account_id' => $transaction->bank_account_id,
            ],
        );

        return redirect()
            ->back()
            ->with('success', "Pembayaran untuk invoice {$transaction->invoice} berhasil dikonfirmasi.");
    }

    /**
     * Update payment status manually — hanya untuk admin/yang punya permission
     */
    public function updateStatus(Request $request, Transaction $transaction)
    {
        $allowed = ['paid', 'pending', 'failed', 'expired', 'unpaid', 'partial'];
        $status  = $request->input('payment_status');

        if (! in_array($status, $allowed)) {
            return redirect()->back()->with('error', 'Status tidak valid.');
        }

        $before = $transaction->payment_status;

        DB::transaction(function () use ($transaction, $status, $before) {
            $transaction->update(['payment_status' => $status]);

            // Sama kayak webhook payment gateway: kalau status manual diubah
            // jadi gagal/expired (dan sebelumnya belum failed/expired/paid),
            // stok yang udah kepotong pas checkout otomatis dikembalikan.
            if (
                in_array($status, ['failed', 'expired'], true)
                && ! in_array($before, ['failed', 'expired', 'paid'], true)
            ) {
                $this->stockMutationService->restoreStockForTransaction($transaction, auth()->id());
            }
        });

        $this->auditLogService->log(
            event: 'transaction.status_updated',
            module: 'transactions',
            auditable: $transaction,
            description: "Status pembayaran invoice {$transaction->invoice} diubah dari {$before} menjadi {$status}.",
            before: ['payment_status' => $before],
            after:  ['payment_status' => $status],
        );

        return redirect()->back()->with('success', "Status berhasil diubah menjadi {$status}.");
    }

    /**
     * EDIT TRANSAKSI — tahap 1: pindahkan isi transaksi lama ke keranjang
     * kasir yang sedang login, lalu redirect ke halaman kasir supaya bisa
     * diubah (tambah/kurang/ganti item, dst) pakai UI & mesin hitung harga
     * yang SAMA PERSIS dengan transaksi baru — bukan bikin form terpisah
     * yang gampang beda logic dari aslinya.
     *
     * Transaksi lewat payment gateway online (Midtrans/Xendit/dst) SENGAJA
     * tidak boleh diedit — pembayarannya sudah diproses pihak ketiga, kalau
     * mau dikoreksi harus lewat Retur Penjualan, bukan diedit langsung.
     */
    public function edit(Transaction $transaction)
    {
        $onlineGateways = ['midtrans', 'xendit', 'doku', 'flip'];
        if (in_array($transaction->payment_method, $onlineGateways, true)) {
            return back()->with('error', "Transaksi dengan pembayaran online ({$transaction->payment_method}) tidak bisa diedit langsung. Gunakan Retur Penjualan.");
        }

        try {
            $this->cashierShiftService->requireActiveShiftForUser(auth()->user()->id);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->with('error', 'Buka shift kasir dulu sebelum mengedit transaksi.');
        }

        // Bersihkan keranjang aktif kasir ini dulu (bukan yang di-hold) —
        // supaya nggak ketuker sama transaksi lain yang lagi disiapkan.
        Cart::where('cashier_id', auth()->user()->id)->active()->delete();

        $transaction->load('details.product');

        foreach ($transaction->details as $detail) {
            if (! $detail->product) {
                continue; // produk sudah dihapus permanen dari DB, tidak bisa dimasukkan ulang ke keranjang
            }

            Cart::create([
                'cashier_id' => auth()->user()->id,
                'customer_id' => $transaction->customer_id,
                'product_id' => $detail->product_id,
                'qty' => $detail->qty,
                'price' => $detail->product->sell_price * $detail->qty,
            ]);
        }

        return redirect()->route('transactions.index', [
            'editing' => $transaction->id,
            'editing_invoice' => $transaction->invoice,
        ]);
    }

    /**
     * EDIT TRANSAKSI — tahap 2: simpan hasil edit.
     *
     * Alurnya: batalkan dulu SEMUA efek transaksi lama (stok, poin loyalti,
     * pemakaian voucher, mutasi stok, histori poin, profit, item) — PERSIS
     * seperti destroy(), cuma baris transaksinya sendiri TIDAK dihapus —
     * lalu terapkan ulang dari keranjang yang sudah dikoreksi kasir, pakai
     * mesin hitung yang sama dengan checkout normal (persistTransactionFromCart).
     * Nomor invoice & shift asalnya tetap dipertahankan.
     */
    public function update(Request $request, Transaction $transaction, PaymentGatewayManager $paymentGatewayManager)
    {
        $onlineGateways = ['midtrans', 'xendit', 'doku', 'flip'];
        if (in_array($transaction->payment_method, $onlineGateways, true)) {
            abort(422, 'Transaksi dengan pembayaran online tidak bisa diedit langsung. Gunakan Retur Penjualan.');
        }

        $isPayLater     = $request->boolean('pay_later');
        $paymentGateway = $isPayLater ? null : $request->input('payment_gateway');
        if ($paymentGateway) {
            $paymentGateway = strtolower($paymentGateway);
        }
        if ($paymentGateway && in_array($paymentGateway, $onlineGateways, true)) {
            abort(422, 'Nggak bisa ganti ke metode pembayaran online lewat edit. Gunakan Retur Penjualan kalau mau ganti ke itu.');
        }

        $isDualPayment  = $paymentGateway === 'dual_cash_qris';
        $dualCash       = $isDualPayment ? max(0, (int) $request->input('dual_cash', 0)) : 0;
        $dualQris       = $isDualPayment ? max(0, (int) $request->input('dual_qris', 0)) : 0;

        if ($isPayLater && ! $request->filled('due_date')) {
            return redirect()->route('transactions.index')->with('error', 'Tanggal jatuh tempo wajib diisi untuk nota barang.');
        }

        $isCashPayment  = empty($paymentGateway) && ! $isPayLater && ! $isDualPayment;
        $isQrisStatic   = $paymentGateway === 'qris_static';
        $manualDiscount = max(0, (int) $request->input('discount', 0));
        $itemDiscounts = collect($request->input('item_discounts', []))
            ->filter(fn ($entry) => is_array($entry) && isset($entry['cart_id']))
            ->map(fn ($entry) => [
                'cart_id' => (int) $entry['cart_id'],
                'mode' => ($entry['mode'] ?? null) === 'percent' ? 'percent' : 'nominal',
                'value' => max(0, (float) ($entry['value'] ?? 0)),
            ])
            ->filter(fn ($entry) => $entry['value'] > 0)
            ->values()
            ->all();
        $shippingCost   = max(0, (int) $request->input('shipping_cost', 0));
        $requestedRedeemPoints = max(0, (int) $request->input('redeem_points', 0));
        $cashAmount = $isCashPayment ? max(0, (int) $request->cash) : ($isDualPayment ? $dualCash : 0);
        $customer = $request->filled('customer_id') ? Customer::find($request->integer('customer_id')) : null;
        $requestedVoucherIds = $request->input('customer_voucher_ids');
        $requestedVoucherIds = is_array($requestedVoucherIds) ? array_filter(array_map('intval', $requestedVoucherIds)) : [];
        if (empty($requestedVoucherIds) && $request->filled('customer_voucher_id')) {
            $requestedVoucherIds = [$request->integer('customer_voucher_id')];
        }
        $vouchers = $this->resolveVouchersByIds($requestedVoucherIds);

        $transaction->load(['details.product', 'profits', 'customer:id,name']);
        $snapshot = $transaction->toArray();
        $snapshot['details'] = $transaction->details->toArray();

        $updated = DB::transaction(function () use (
            $request, $transaction, $cashAmount, $paymentGateway, $isCashPayment, $isQrisStatic,
            $isDualPayment, $dualCash, $dualQris, $isPayLater, $manualDiscount, $shippingCost,
            $requestedRedeemPoints, $customer, $vouchers, $itemDiscounts
        ) {
            // ── Batalkan semua efek transaksi LAMA (persis logic destroy(),
            //    minus menghapus baris transaksinya sendiri) ──────────────
            foreach ($transaction->details as $detail) {
                $product = Product::where('id', $detail->product_id)->lockForUpdate()->first();
                if ($product) {
                    $product->increment('stock', $detail->qty);
                }
            }

            if ($transaction->customer_id) {
                $oldCustomer = Customer::where('id', $transaction->customer_id)->lockForUpdate()->first();
                if ($oldCustomer) {
                    $delta = (int) $transaction->loyalty_points_redeemed - (int) $transaction->loyalty_points_earned;
                    $oldCustomer->update([
                        'loyalty_points' => max(0, (int) $oldCustomer->loyalty_points + $delta),
                        // Dua ini juga wajib dibalikin — sebelumnya kelewat,
                        // jadi tiap kali transaksi diedit, "total belanja" &
                        // "jumlah transaksi" pelanggan itu double-count terus
                        // (nambah lagi pas transaksi baru dibuat ulang, tanpa
                        // pernah dikurangi dari transaksi lama yang dibatalkan).
                        'loyalty_total_spent' => max(0, (int) $oldCustomer->loyalty_total_spent - (int) $transaction->grand_total),
                        'loyalty_transaction_count' => max(0, (int) $oldCustomer->loyalty_transaction_count - 1),
                    ]);
                }
            }

            Promo::where('used_transaction_id', $transaction->id)->update([
                'used_count' => DB::raw('GREATEST(used_count - 1, 0)'),
                'is_used' => false,
                'used_at' => null,
                'used_transaction_id' => null,
            ]);

            foreach ($transaction->details->pluck('promo_id')->filter() as $promoId) {
                Promo::where('id', $promoId)->update(['used_count' => DB::raw('GREATEST(used_count - 1, 0)')]);
            }

            StockMutation::where('reference_type', 'sale')->where('reference_id', $transaction->id)->delete();
            LoyaltyPointHistory::where('transaction_id', $transaction->id)->delete();
            $transaction->profits()->delete();
            $transaction->details()->delete();

            // ── Terapkan ulang dari keranjang yang sudah dikoreksi ────────
            $carts = Cart::with('product')->where('cashier_id', auth()->user()->id)->active()->get();

            return $this->persistTransactionFromCart($carts, $customer, $vouchers, [
                'invoice' => $transaction->invoice,
                'cashAmount' => $cashAmount,
                'paymentGateway' => $paymentGateway,
                'isCashPayment' => $isCashPayment,
                'isQrisStatic' => $isQrisStatic,
                'isDualPayment' => $isDualPayment,
                'dualCash' => $dualCash,
                'dualQris' => $dualQris,
                'isPayLater' => $isPayLater,
                'manualDiscount' => $manualDiscount,
                'shippingCost' => $shippingCost,
                'requestedRedeemPoints' => $requestedRedeemPoints,
                'itemDiscounts' => $itemDiscounts,
                'customerId' => $request->customer_id,
                'dueDate' => $request->due_date,
                'bankAccountId' => $paymentGateway === 'bank_transfer' ? $request->bank_account_id : null,
            ], $transaction);
        });

        $this->auditLogService->log(
            event: 'transaction.edited',
            module: 'transactions',
            auditable: $updated,
            description: "Transaksi {$updated->invoice} diedit oleh {$request->user()->name}.",
            before: $snapshot,
            after: $updated->fresh(['details', 'profits'])->toArray(),
        );

        return to_route('transactions.print', $updated->invoice)
            ->with('success', "Transaksi {$updated->invoice} berhasil diperbarui.");
    }

    /**
     * destroy — HAPUS PERMANEN transaksi beserta semua jejaknya (stok
     * dikembalikan, pemakaian voucher direset, poin loyalti dikembalikan,
     * mutasi stok/histori poin/profit dihapus). Beda dari Retur (yang
     * sengaja NINGGALIN jejak) — ini ngilangin transaksi seolah nggak
     * pernah kejadian. Dipakai buat kasus salah input yang ketauan
     * SETELAH transaksi selesai & customer udah pergi, bukan buat
     * pembatalan/retur barang normal.
     *
     * Sengaja minta konfirmasi ketik ulang invoice (dicek di FE & BE)
     * karena aksi ini PERMANEN & TIDAK BISA DIBATALKAN.
     */
    public function destroy(Request $request, Transaction $transaction)
    {
        $request->validate([
            'confirm_invoice' => ['required', 'string'],
        ]);

        if ($request->input('confirm_invoice') !== $transaction->invoice) {
            return back()->with('error', 'Nomor invoice yang diketik nggak cocok. Transaksi nggak jadi dihapus.');
        }

        $transaction->load(['details.product', 'profits', 'customer:id,name']);

        // Snapshot lengkap buat audit log — begitu transaksi ini dihapus,
        // ini satu-satunya jejak yang tersisa buat ditelusuri lagi nanti.
        $snapshot = $transaction->toArray();
        $snapshot['details'] = $transaction->details->toArray();

        DB::transaction(function () use ($transaction, $request) {
            // 1. Kembalikan stok tiap produk yang kepotong dari transaksi ini
            foreach ($transaction->details as $detail) {
                $product = Product::where('id', $detail->product_id)->lockForUpdate()->first();

                if ($product) {
                    $product->increment('stock', $detail->qty);
                }
            }

            // 2. Kembalikan poin loyalti customer (poin yang didapat ditarik
            //    lagi, poin yang dipakai buat bayar dikembalikan)
            if ($transaction->customer_id) {
                $customer = Customer::where('id', $transaction->customer_id)->lockForUpdate()->first();

                if ($customer) {
                    $delta = (int) $transaction->loyalty_points_redeemed - (int) $transaction->loyalty_points_earned;
                    $customer->update([
                        'loyalty_points' => max(0, (int) $customer->loyalty_points + $delta),
                        // Sebelumnya kelewat: "total belanja" & "jumlah
                        // transaksi" pelanggan nggak pernah dikurangi lagi
                        // pas transaksinya dihapus permanen, jadi angkanya
                        // nyangkut/nggak akurat walau transaksinya udah hilang.
                        'loyalty_total_spent' => max(0, (int) $customer->loyalty_total_spent - (int) $transaction->grand_total),
                        'loyalty_transaction_count' => max(0, (int) $customer->loyalty_transaction_count - 1),
                    ]);
                }
            }

            // 3. Reset voucher yang nempel di level transaksi (kode manual
            //    kayak PANJAT8100 dkk)
            Promo::where('used_transaction_id', $transaction->id)->update([
                'used_count' => DB::raw('GREATEST(used_count - 1, 0)'),
                'is_used' => false,
                'used_at' => null,
                'used_transaction_id' => null,
            ]);

            // 4. Turunkan used_count promo yang nempel per-item (mode
            //    "Per Item" / auto promo), satu-satu sesuai jumlah baris
            //    yang benar-benar makai kode itu
            foreach ($transaction->details->pluck('promo_id')->filter() as $promoId) {
                Promo::where('id', $promoId)->update([
                    'used_count' => DB::raw('GREATEST(used_count - 1, 0)'),
                ]);
            }

            // 5. Hapus jejak mutasi stok dari penjualan ini
            StockMutation::where('reference_type', 'sale')
                ->where('reference_id', $transaction->id)
                ->delete();

            // 6. Hapus histori poin loyalti transaksi ini
            LoyaltyPointHistory::where('transaction_id', $transaction->id)->delete();

            // 7. Hapus catatan profit
            $transaction->profits()->delete();

            // 8. Hapus item-item transaksi
            $transaction->details()->delete();

            // 9. Terakhir, hapus transaksinya sendiri
            $transaction->delete();
        });

        $this->auditLogService->log(
            event: 'transaction.deleted',
            module: 'transactions',
            auditable: $transaction,
            description: "Transaksi {$transaction->invoice} dihapus permanen oleh {$request->user()->name}.",
            before: $snapshot,
            after: null,
        );

        return redirect()->route('transactions.history')
            ->with('success', "Transaksi {$transaction->invoice} berhasil dihapus permanen.");
    }
}