<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Promo;
use App\Models\PromoBundleItem;
use App\Models\PromoBuyGetItem;
use App\Models\PromoQtyBreak;
use App\Services\AuditLogService;
use App\Services\LoyaltyService;
use App\Services\PromoService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Satu menu admin untuk semua jenis promo/diskon — menggantikan menu
 * terpisah "Promo Harga" (Pricing Rules, auto) dan "Voucher Customer"
 * (manual). Bedanya sekarang cuma satu pertanyaan: activation_mode
 * (auto / manual), bukan dua sistem yang berbeda.
 */
class PromoController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly LoyaltyService $loyaltyService,
        private readonly PromoService $promoService
    ) {}

    public function index(Request $request)
    {
        $filters = [
            'search' => $request->input('search'),
            'status' => $request->input('status'),
            'activation_mode' => $request->input('activation_mode'),
            'discount_kind' => $request->input('discount_kind'),
        ];

        $promos = Promo::query()
            ->with(['product:id,title', 'category:id,name', 'customer:id,name,no_telp', 'creator:id,name', 'qtyBreaks', 'bundleItems', 'buyGetItems'])
            ->when($filters['search'], function ($query, $search) {
                $query->where(function ($builder) use ($search) {
                    $builder
                        ->where('name', 'like', '%'.$search.'%')
                        ->orWhere('code', 'like', '%'.$search.'%')
                        ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->when($filters['activation_mode'], function ($query, $mode) {
                $query->where('activation_mode', $mode);
            })
            ->when($filters['discount_kind'], function ($query, $kind) {
                $query->where('discount_kind', $kind);
            })
            ->when($filters['status'] !== null && $filters['status'] !== '', function ($query) use ($filters) {
                match ($filters['status']) {
                    'active' => $query->where('is_active', true)->where('is_used', false)
                        ->where(function ($builder) {
                            $builder->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                        })
                        ->where(function ($builder) {
                            $builder->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                        }),
                    'scheduled' => $query->where('is_active', true)->whereNotNull('starts_at')->where('starts_at', '>', now()),
                    'expired' => $query->whereNotNull('ends_at')->where('ends_at', '<', now()),
                    'used' => $query->where('is_used', true),
                    'inactive' => $query->where('is_active', false),
                    default => null,
                };
            })
            ->orderByDesc('is_active')
            ->orderByDesc('priority')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString()
            ->through(fn (Promo $promo) => [
                'id' => $promo->id,
                'name' => $promo->name,
                'code' => $promo->code,
                'discount_kind' => $promo->discount_kind,
                'discount_kind_label' => $promo->discountKindLabel(),
                'activation_mode' => $promo->activation_mode,
                'is_active' => (bool) $promo->is_active,
                'priority' => (int) $promo->priority,
                'target_type' => $promo->target_type,
                'customer_scope' => $promo->customer_scope,
                'customer' => $promo->customer,
                'product' => $promo->product,
                'category' => $promo->category,
                'discount_type' => $promo->discount_type,
                'discount_value' => (float) $promo->discount_value,
                'minimum_order' => (int) $promo->minimum_order,
                'max_uses' => $promo->max_uses,
                'used_count' => (int) $promo->used_count,
                'starts_at' => optional($promo->starts_at)?->toIso8601String(),
                'ends_at' => optional($promo->ends_at)?->toIso8601String(),
                'status_label' => $promo->currentStatusLabel(),
                'qty_breaks_count' => $promo->qtyBreaks->count(),
                'bundle_items_count' => $promo->bundleItems->count(),
                'buy_get_items_count' => $promo->buyGetItems->count(),
            ]);

        $summaryBase = Promo::query()->get();

        return Inertia::render('Dashboard/Promos/Index', [
            'promos' => $promos,
            'filters' => $filters,
            'summary' => [
                'active' => $summaryBase->filter(fn (Promo $promo) => $promo->currentStatusLabel() === 'active')->count(),
                'scheduled' => $summaryBase->filter(fn (Promo $promo) => $promo->currentStatusLabel() === 'scheduled')->count(),
                'expired' => $summaryBase->filter(fn (Promo $promo) => $promo->currentStatusLabel() === 'expired')->count(),
                'used' => $summaryBase->filter(fn (Promo $promo) => $promo->currentStatusLabel() === 'used')->count(),
                'inactive' => $summaryBase->filter(fn (Promo $promo) => $promo->currentStatusLabel() === 'inactive')->count(),
                'auto' => $summaryBase->where('activation_mode', Promo::ACTIVATION_AUTO)->count(),
                'manual' => $summaryBase->where('activation_mode', Promo::ACTIVATION_MANUAL)->count(),
            ],
            'recentAudits' => AuditLog::query()
                ->where('module', 'promos')
                ->latest('id')
                ->limit(5)
                ->get(['id', 'event', 'description', 'created_at']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Dashboard/Promos/Create', $this->formPayload());
    }

    public function store(Request $request)
    {
        $validated = $this->validatePromo($request);

        if ($validated['promo']['activation_mode'] !== Promo::ACTIVATION_MANUAL) {
            $validated['promo']['customer_id'] = null;
        }

        $promo = Promo::create([
            ...$validated['promo'],
            'created_by' => $request->user()?->id,
            'code' => $validated['promo']['code'] ?: ($validated['promo']['activation_mode'] === Promo::ACTIVATION_MANUAL ? $this->generatePromoCode() : null),
        ]);

        $this->syncPromoRelations($promo, $validated['relations']);

        $this->auditLogService->log(
            event: 'promo.created',
            module: 'promos',
            auditable: $promo,
            description: 'Promo dibuat.',
            after: $this->auditPayload($promo->fresh(['qtyBreaks', 'bundleItems', 'buyGetItems']))
        );

        return redirect()
            ->route('promos.index')
            ->with('success', 'Promo berhasil dibuat.');
    }

    public function edit(Promo $promo)
    {
        $promo->load(['qtyBreaks', 'bundleItems', 'buyGetItems']);

        return Inertia::render('Dashboard/Promos/Edit', [
            ...$this->formPayload(),
            'promo' => [
                ...$promo->toArray(),
                'qty_breaks' => $promo->qtyBreaks->map(fn (PromoQtyBreak $break) => [
                    'id' => $break->id,
                    'min_qty' => (int) $break->min_qty,
                    'discount_type' => $break->discount_type,
                    'discount_value' => (float) $break->discount_value,
                    'sort_order' => (int) $break->sort_order,
                ])->values()->all(),
                'bundle_items' => $promo->bundleItems->map(fn (PromoBundleItem $item) => [
                    'id' => $item->id,
                    'product_id' => (int) $item->product_id,
                    'quantity' => (int) $item->quantity,
                    'sort_order' => (int) $item->sort_order,
                ])->values()->all(),
                'buy_get_items' => $promo->buyGetItems->map(fn (PromoBuyGetItem $item) => [
                    'id' => $item->id,
                    'product_id' => (int) $item->product_id,
                    'role' => $item->role,
                    'quantity' => (int) $item->quantity,
                    'sort_order' => (int) $item->sort_order,
                ])->values()->all(),
            ],
        ]);
    }

    public function update(Request $request, Promo $promo)
    {
        $before = $this->auditPayload($promo->load(['qtyBreaks', 'bundleItems', 'buyGetItems']));
        $validated = $this->validatePromo($request, $promo);

        if ($validated['promo']['activation_mode'] !== Promo::ACTIVATION_MANUAL) {
            $validated['promo']['customer_id'] = null;
        }

        $promo->update([
            ...$validated['promo'],
            'code' => $validated['promo']['code'] ?: $promo->code,
        ]);
        $this->syncPromoRelations($promo, $validated['relations']);

        $this->auditLogService->log(
            event: 'promo.updated',
            module: 'promos',
            auditable: $promo,
            description: 'Promo diperbarui.',
            before: $before,
            after: $this->auditPayload($promo->fresh(['qtyBreaks', 'bundleItems', 'buyGetItems']))
        );

        return redirect()
            ->route('promos.index')
            ->with('success', 'Promo berhasil diperbarui.');
    }

    public function destroy(Promo $promo)
    {
        $before = $this->auditPayload($promo->load(['qtyBreaks', 'bundleItems', 'buyGetItems']));
        $promo->delete();

        $this->auditLogService->log(
            event: 'promo.deleted',
            module: 'promos',
            auditable: $promo,
            description: 'Promo dihapus.',
            before: $before
        );

        return back()->with('success', 'Promo berhasil dihapus.');
    }

    public function preview(Request $request)
    {
        $validated = $this->validatePromo($request);
        $promo = new Promo($validated['promo']);
        $promo->setRelation('qtyBreaks', collect($validated['relations']['qty_breaks'])->map(fn (array $break) => new PromoQtyBreak($break)));
        $promo->setRelation('bundleItems', collect($validated['relations']['bundle_items'])->map(fn (array $item) => new PromoBundleItem($item)));
        $promo->setRelation('buyGetItems', collect($validated['relations']['buy_get_items'])->map(fn (array $item) => new PromoBuyGetItem($item)));

        $customer = $request->filled('preview_customer_id')
            ? Customer::find($request->integer('preview_customer_id'))
            : null;

        // Promo manual dengan target "semua produk" (BOGO/grosir generik)
        // tidak punya pivot produk untuk disimulasikan dari sisi auto-preview
        // — hitung langsung lewat calculateManualPromoDiscount dengan contoh
        // keranjang sederhana.
        if ($promo->isManual() && in_array($promo->discount_kind, [Promo::KIND_BUY_X_GET_Y, Promo::KIND_QTY_BREAK], true)) {
            $sampleCarts = $this->buildPreviewCarts($promo);
            $autoPreview = $this->promoService->previewCartWithPromos($sampleCarts, $customer, collect());
            $cartItems = $autoPreview['items'];
            $subtotal = (int) collect($cartItems)->sum('line_total');
            $result = $this->promoService->calculateManualPromoDiscount($promo, $subtotal, $cartItems);

            return response()->json([
                'success' => true,
                'data' => [
                    ...$autoPreview,
                    'summary' => [
                        ...$autoPreview['summary'],
                        'voucher_discount_total' => $result['discount'],
                        'voucher_free_items' => $result['free_items'],
                    ],
                ],
            ]);
        }

        $sampleCarts = $this->buildPreviewCarts($promo);

        return response()->json([
            'success' => true,
            'data' => $this->promoService->previewCartWithPromos($sampleCarts, $customer, collect([$promo])),
        ]);
    }

    private function formPayload(): array
    {
        return [
            'products' => Product::orderBy('title')->get(['id', 'title', 'sell_price', 'category_id']),
            'categories' => Category::orderBy('name')->get(['id', 'name']),
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'no_telp', 'is_loyalty_member', 'loyalty_tier', 'loyalty_points']),
            'tierOptions' => $this->loyaltyService->tierOptions(),
            'kindOptions' => [
                ['value' => Promo::KIND_STANDARD_DISCOUNT, 'label' => 'Diskon Nominal / Persen'],
                ['value' => Promo::KIND_QTY_BREAK, 'label' => 'Grosir Bertingkat'],
                ['value' => Promo::KIND_BUNDLE_PRICE, 'label' => 'Bundle Harga'],
                ['value' => Promo::KIND_BUY_X_GET_Y, 'label' => 'Beli X Gratis Y'],
            ],
            'activationOptions' => [
                ['value' => Promo::ACTIVATION_AUTO, 'label' => 'Otomatis'],
                ['value' => Promo::ACTIVATION_MANUAL, 'label' => 'Pakai Kode di Kasir'],
            ],
            'scopeOptions' => [
                ['value' => Promo::SCOPE_ALL, 'label' => 'Semua Pelanggan'],
                ['value' => Promo::SCOPE_WALK_IN, 'label' => 'Tanpa Pelanggan / Non-Member'],
                ['value' => Promo::SCOPE_REGISTERED, 'label' => 'Pelanggan Terdaftar'],
                ['value' => Promo::SCOPE_MEMBER, 'label' => 'Member Loyalty'],
            ],
        ];
    }

    private function validatePromo(Request $request, ?Promo $promo = null): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'discount_kind' => ['required', Rule::in([
                Promo::KIND_STANDARD_DISCOUNT,
                Promo::KIND_QTY_BREAK,
                Promo::KIND_BUNDLE_PRICE,
                Promo::KIND_BUY_X_GET_Y,
            ])],
            'activation_mode' => ['required', Rule::in([
                Promo::ACTIVATION_AUTO,
                Promo::ACTIVATION_MANUAL,
            ])],
            'code' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('promos', 'code')->ignore($promo?->id),
            ],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'max_uses' => ['nullable', 'integer', 'min:1'],
            'is_active' => ['nullable', 'boolean'],
            'priority' => ['nullable', 'integer', 'min:0'],
            'target_type' => ['required', Rule::in([
                Promo::TARGET_ALL,
                Promo::TARGET_PRODUCT,
                Promo::TARGET_CATEGORY,
            ])],
            'product_id' => ['nullable', 'integer', 'exists:products,id'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'customer_scope' => ['required', Rule::in([
                Promo::SCOPE_ALL,
                Promo::SCOPE_WALK_IN,
                Promo::SCOPE_REGISTERED,
                Promo::SCOPE_MEMBER,
            ])],
            'eligible_loyalty_tiers' => ['nullable', 'array'],
            'eligible_loyalty_tiers.*' => ['string', Rule::in(array_keys($this->loyaltyService->tiers()))],
            'discount_type' => [
                Rule::requiredIf(fn () => in_array($request->input('discount_kind'), [
                    Promo::KIND_STANDARD_DISCOUNT,
                    Promo::KIND_QTY_BREAK,
                ], true)),
                'nullable',
                Rule::in([
                    Promo::TYPE_PERCENTAGE,
                    Promo::TYPE_FIXED_AMOUNT,
                    Promo::TYPE_FIXED_PRICE,
                ]),
            ],
            'discount_value' => [
                Rule::requiredIf(fn () => in_array($request->input('discount_kind'), [
                    Promo::KIND_STANDARD_DISCOUNT,
                    Promo::KIND_QTY_BREAK,
                ], true)),
                'nullable',
                'numeric',
                'min:0.01',
            ],
            'minimum_order' => ['nullable', 'integer', 'min:0'],
            'buy_qty' => ['nullable', 'integer', 'min:1'],
            'get_qty' => ['nullable', 'integer', 'min:1'],
            'get_product_id' => ['nullable', 'integer', 'exists:products,id'],
            'get_discount_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'preview_quantity_multiplier' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'qty_breaks' => ['nullable', 'array'],
            'qty_breaks.*.min_qty' => ['nullable', 'integer', 'min:1'],
            'qty_breaks.*.discount_type' => ['nullable', Rule::in([
                Promo::TYPE_PERCENTAGE,
                Promo::TYPE_FIXED_AMOUNT,
                Promo::TYPE_FIXED_PRICE,
            ])],
            'qty_breaks.*.discount_value' => ['nullable', 'numeric', 'min:0.01'],
            'qty_breaks.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'bundle_items' => ['nullable', 'array'],
            'bundle_items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'bundle_items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'bundle_items.*.sort_order' => ['nullable', 'integer', 'min:0'],
            'buy_get_items' => ['nullable', 'array'],
            'buy_get_items.*.product_id' => ['nullable', 'integer', 'exists:products,id'],
            'buy_get_items.*.role' => ['nullable', Rule::in([
                PromoBuyGetItem::ROLE_BUY,
                PromoBuyGetItem::ROLE_GET,
            ])],
            'buy_get_items.*.quantity' => ['nullable', 'integer', 'min:1'],
            'buy_get_items.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        // Baris array kosong (belum dipilih produk/nilai) dianggap "belum
        // dipakai", bukan error — difilter dulu di sini, sebelum dicek
        // kelengkapan minimumnya di bawah. Mencegah baris template kosong
        // dari kind lain ikut menggagalkan validasi (lihat riwayat bug ini).
        if ($validated['discount_kind'] !== Promo::KIND_QTY_BREAK) {
            $validated['qty_breaks'] = [];
        } else {
            $validated['qty_breaks'] = collect($validated['qty_breaks'] ?? [])
                ->filter(fn (array $break) => ! empty($break['min_qty']) && $break['discount_value'] !== null && $break['discount_value'] !== '')
                ->values()
                ->all();
        }

        if ($validated['discount_kind'] !== Promo::KIND_BUNDLE_PRICE) {
            $validated['bundle_items'] = [];
        } else {
            $validated['bundle_items'] = collect($validated['bundle_items'] ?? [])
                ->filter(fn (array $item) => ! empty($item['product_id']))
                ->values()
                ->all();
        }

        // buy_get_items (pivot produk spesifik) HANYA dipakai untuk BOGO
        // otomatis dengan target produk tertentu. BOGO manual generik
        // ("semua produk") tidak butuh pivot ini sama sekali — cukup
        // target_type=all + buy_qty/get_qty.
        if ($validated['discount_kind'] !== Promo::KIND_BUY_X_GET_Y || $validated['activation_mode'] !== Promo::ACTIVATION_AUTO) {
            $validated['buy_get_items'] = [];
        } else {
            $validated['buy_get_items'] = collect($validated['buy_get_items'] ?? [])
                ->filter(fn (array $item) => ! empty($item['product_id']))
                ->values()
                ->all();
        }

        if ($validated['target_type'] !== Promo::TARGET_PRODUCT) {
            $validated['product_id'] = null;
        }

        if ($validated['target_type'] !== Promo::TARGET_CATEGORY) {
            $validated['category_id'] = null;
        }

        if (
            $validated['discount_type'] === Promo::TYPE_PERCENTAGE
            && (float) ($validated['discount_value'] ?? 0) > 100
        ) {
            $request->validate(['discount_value' => ['max:100']]);
        }

        if ($validated['discount_kind'] === Promo::KIND_QTY_BREAK && empty($validated['qty_breaks'])) {
            $request->validate(['qty_breaks' => ['required', 'array', 'min:1']]);
        }

        if ($validated['discount_kind'] === Promo::KIND_BUNDLE_PRICE && count($validated['bundle_items'] ?? []) < 2) {
            $request->validate(['bundle_items' => ['required', 'array', 'min:2']]);
        }

        // BOGO otomatis dengan target produk tertentu butuh pivot buy/get.
        // BOGO manual (atau auto dengan target_type=all) tidak butuh ini.
        if (
            $validated['discount_kind'] === Promo::KIND_BUY_X_GET_Y
            && $validated['activation_mode'] === Promo::ACTIVATION_AUTO
            && $validated['target_type'] !== Promo::TARGET_ALL
        ) {
            $buyCount = collect($validated['buy_get_items'] ?? [])->where('role', PromoBuyGetItem::ROLE_BUY)->count();
            $getCount = collect($validated['buy_get_items'] ?? [])->where('role', PromoBuyGetItem::ROLE_GET)->count();

            if ($buyCount === 0 || $getCount === 0) {
                $request->validate(['buy_get_items' => ['required', 'array', 'min:2']]);
            }
        }

        if (
            $validated['discount_kind'] === Promo::KIND_BUY_X_GET_Y
            && $validated['activation_mode'] === Promo::ACTIVATION_MANUAL
        ) {
            // BOGO manual generik: buy_qty/get_qty wajib, pivot tidak dipakai.
            $validated['buy_qty'] = max(1, (int) ($validated['buy_qty'] ?? 1));
            $validated['get_qty'] = max(1, (int) ($validated['get_qty'] ?? 1));
            $validated['get_discount_percent'] = min(100, max(0, (float) ($validated['get_discount_percent'] ?? 100)));
        }

        $validated['is_active'] = (bool) ($validated['is_active'] ?? false);
        $validated['priority'] = max(0, (int) ($validated['priority'] ?? 0));
        $validated['minimum_order'] = max(0, (int) ($validated['minimum_order'] ?? 0));
        $validated['eligible_loyalty_tiers'] = $validated['customer_scope'] === Promo::SCOPE_MEMBER
            ? array_values(array_unique($validated['eligible_loyalty_tiers'] ?? []))
            : null;
        $validated['preview_quantity_multiplier'] = max(1, (int) ($validated['preview_quantity_multiplier'] ?? 1));

        if ($validated['discount_kind'] === Promo::KIND_QTY_BREAK) {
            $validated['qty_breaks'] = collect($validated['qty_breaks'])
                ->map(fn (array $break, int $index) => [
                    'min_qty' => (int) $break['min_qty'],
                    'discount_type' => $break['discount_type'] ?? Promo::TYPE_PERCENTAGE,
                    'discount_value' => (float) $break['discount_value'],
                    'sort_order' => (int) ($break['sort_order'] ?? $index),
                ])
                ->sortBy('min_qty')
                ->values()
                ->all();
        }

        $validated['bundle_items'] = collect($validated['bundle_items'])
            ->map(fn (array $item, int $index) => [
                'product_id' => (int) $item['product_id'],
                'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
                'sort_order' => (int) ($item['sort_order'] ?? $index),
            ])
            ->values()
            ->all();

        $validated['buy_get_items'] = collect($validated['buy_get_items'])
            ->map(fn (array $item, int $index) => [
                'product_id' => (int) $item['product_id'],
                'role' => $item['role'] ?? PromoBuyGetItem::ROLE_BUY,
                'quantity' => max(1, (int) ($item['quantity'] ?? 1)),
                'sort_order' => (int) ($item['sort_order'] ?? $index),
            ])
            ->values()
            ->all();

        if ($validated['discount_kind'] === Promo::KIND_BUNDLE_PRICE) {
            $validated['discount_type'] = Promo::TYPE_FIXED_PRICE;
        }

        if ($validated['discount_kind'] === Promo::KIND_BUY_X_GET_Y) {
            $validated['discount_type'] = Promo::TYPE_FIXED_AMOUNT;
            $validated['discount_value'] = 0;
        }

        return [
            'promo' => collect($validated)
                ->only([
                    'name',
                    'code',
                    'notes',
                    'is_active',
                    'priority',
                    'activation_mode',
                    'discount_kind',
                    'discount_type',
                    'discount_value',
                    'target_type',
                    'product_id',
                    'category_id',
                    'customer_scope',
                    'eligible_loyalty_tiers',
                    'customer_id',
                    'max_uses',
                    'minimum_order',
                    'buy_qty',
                    'get_qty',
                    'get_product_id',
                    'get_discount_percent',
                    'preview_quantity_multiplier',
                    'starts_at',
                    'ends_at',
                ])
                ->all(),
            'relations' => [
                'qty_breaks' => $validated['discount_kind'] === Promo::KIND_QTY_BREAK ? $validated['qty_breaks'] : [],
                'bundle_items' => $validated['discount_kind'] === Promo::KIND_BUNDLE_PRICE ? $validated['bundle_items'] : [],
                'buy_get_items' => $validated['discount_kind'] === Promo::KIND_BUY_X_GET_Y ? $validated['buy_get_items'] : [],
            ],
        ];
    }

    private function syncPromoRelations(Promo $promo, array $relations): void
    {
        $promo->qtyBreaks()->delete();
        foreach ($relations['qty_breaks'] as $payload) {
            $promo->qtyBreaks()->create($payload);
        }

        $promo->bundleItems()->delete();
        foreach ($relations['bundle_items'] as $payload) {
            $promo->bundleItems()->create($payload);
        }

        $promo->buyGetItems()->delete();
        foreach ($relations['buy_get_items'] as $payload) {
            $promo->buyGetItems()->create($payload);
        }
    }

    private function auditPayload(Promo $promo): array
    {
        return [
            'name' => $promo->name,
            'code' => $promo->code,
            'activation_mode' => $promo->activation_mode,
            'discount_kind' => $promo->discount_kind,
            'is_active' => (bool) $promo->is_active,
            'priority' => (int) $promo->priority,
            'target_type' => $promo->target_type,
            'product_id' => $promo->product_id,
            'category_id' => $promo->category_id,
            'customer_scope' => $promo->customer_scope,
            'eligible_loyalty_tiers' => $promo->eligible_loyalty_tiers,
            'customer_id' => $promo->customer_id,
            'max_uses' => $promo->max_uses,
            'used_count' => $promo->used_count,
            'discount_type' => $promo->discount_type,
            'discount_value' => (float) $promo->discount_value,
            'minimum_order' => (int) $promo->minimum_order,
            'buy_qty' => $promo->buy_qty,
            'get_qty' => $promo->get_qty,
            'get_product_id' => $promo->get_product_id,
            'get_discount_percent' => $promo->get_discount_percent,
            'preview_quantity_multiplier' => (int) $promo->preview_quantity_multiplier,
            'starts_at' => optional($promo->starts_at)?->toIso8601String(),
            'ends_at' => optional($promo->ends_at)?->toIso8601String(),
            'notes' => $promo->notes,
            'qty_breaks' => $promo->qtyBreaks->map->only(['min_qty', 'discount_type', 'discount_value', 'sort_order'])->values()->all(),
            'bundle_items' => $promo->bundleItems->map->only(['product_id', 'quantity', 'sort_order'])->values()->all(),
            'buy_get_items' => $promo->buyGetItems->map->only(['product_id', 'role', 'quantity', 'sort_order'])->values()->all(),
        ];
    }

    private function buildPreviewCarts(Promo $promo): Collection
    {
        $products = match (true) {
            $promo->discount_kind === Promo::KIND_BUNDLE_PRICE => Product::query()
                ->whereIn('id', $promo->bundleItems->pluck('product_id'))
                ->get(),
            $promo->discount_kind === Promo::KIND_BUY_X_GET_Y && $promo->buyGetItems->isNotEmpty() => Product::query()
                ->whereIn('id', $promo->buyGetItems->pluck('product_id'))
                ->get(),
            $promo->target_type === Promo::TARGET_PRODUCT => Product::query()
                ->whereKey($promo->product_id)
                ->get(),
            $promo->target_type === Promo::TARGET_CATEGORY => Product::query()
                ->where('category_id', $promo->category_id)
                ->orderBy('title')
                ->limit(3)
                ->get(),
            default => Product::query()->orderBy('title')->limit(3)->get(),
        };

        return $products
            ->values()
            ->map(function (Product $product, int $index) use ($promo) {
                $qty = $this->previewQuantityForPromo($promo, $product);
                $cart = new Cart([
                    'id' => -($index + 1),
                    'product_id' => $product->id,
                    'qty' => $qty,
                    'price' => (int) $product->sell_price * $qty,
                ]);
                $cart->setRelation('product', $product);

                return $cart;
            });
    }

    private function previewQuantityForPromo(Promo $promo, Product $product): int
    {
        if ($promo->discount_kind === Promo::KIND_QTY_BREAK) {
            return max(1, (int) ($promo->preview_quantity_multiplier ?: $promo->qtyBreaks->max('min_qty') ?: 1));
        }

        if ($promo->discount_kind === Promo::KIND_BUNDLE_PRICE) {
            return (int) ($promo->bundleItems->firstWhere('product_id', $product->id)?->quantity ?? 1);
        }

        if ($promo->discount_kind === Promo::KIND_BUY_X_GET_Y) {
            if ($promo->buyGetItems->isNotEmpty()) {
                return (int) ($promo->buyGetItems->where('product_id', $product->id)->sum('quantity') ?: 1);
            }

            // BOGO generik: contoh qty = buy_qty + get_qty supaya syaratnya
            // langsung terpenuhi di preview.
            return max(1, (int) $promo->buy_qty) + max(1, (int) $promo->get_qty);
        }

        return max(1, (int) ($promo->preview_quantity_multiplier ?: 1));
    }

    private function generatePromoCode(): string
    {
        do {
            $code = 'PROMO-'.Str::upper(Str::random(8));
        } while (Promo::query()->where('code', $code)->exists());

        return $code;
    }
}
