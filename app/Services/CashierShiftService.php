<?php

namespace App\Services;

use App\Mail\CashierShiftClosingReportMail;
use App\Models\CashierShift;
use App\Models\PhotoboxStore;
use App\Models\PhotoboxStudio;
use App\Models\PhotoboxTransaction;
use App\Models\Product;
use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class CashierShiftService
{
    public function getActiveShiftForUser(int $userId): ?CashierShift
    {
        return CashierShift::query()
            ->with(['user:id,name', 'openedBy:id,name'])
            ->open()
            ->where('user_id', $userId)
            ->latest('opened_at')
            ->first();
    }

    public function requireActiveShiftForUser(int $userId, bool $lockForUpdate = false): CashierShift
    {
        $query = CashierShift::query()
            ->open()
            ->where('user_id', $userId)
            ->latest('opened_at');

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $shift = $query->first();

        if (! $shift) {
            throw ValidationException::withMessages([
                'shift' => 'Shift kasir belum dibuka.',
            ]);
        }

        return $shift;
    }

    /**
     * Fitur toko butuh migrasi 2026_11_26_000100. Selama belum dijalankan, semua logika toko dilewati
     * (shift berperilaku seperti "studio / semua studio") supaya halaman kasir tidak 500.
     */
    private ?bool $storeReady = null;

    public function storeSchemaReady(): bool
    {
        return $this->storeReady ??= Schema::hasTable('photobox_stores')
            && Schema::hasColumn('cashier_shifts', 'photobox_store_id')
            && Schema::hasColumn('photobox_studios', 'store_id');
    }

    public function openShift(User $cashier, User $actor, int $openingCash, ?string $notes = null, array $studioIds = [], ?int $storeId = null): CashierShift
    {
        $existing = CashierShift::query()
            ->open()
            ->where('user_id', $cashier->id)
            ->exists();

        if ($existing) {
            throw ValidationException::withMessages([
                'opening_cash' => 'Kasir ini masih memiliki shift aktif.',
            ]);
        }

        $studioIds = collect($studioIds)->map(fn ($id) => (int) $id)->filter(fn ($id) => $id > 0)->unique()->values()->all();

        $storeId = $storeId && $storeId > 0 && $this->storeSchemaReady() && PhotoboxStore::query()->whereKey($storeId)->exists() ? $storeId : null;
        if ($storeId !== null && $studioIds !== []) {
            // Studio yang dipilih harus milik toko itu; yang lain dibuang.
            $studioIds = PhotoboxStudio::query()->where('store_id', $storeId)->whereIn('id', $studioIds)->pluck('id')->map(fn ($id) => (int) $id)->all();
        }

        return DB::transaction(function () use ($cashier, $actor, $openingCash, $notes, $studioIds, $storeId) {
            $shift = CashierShift::create([
                'user_id' => $cashier->id,
                'opened_by' => $actor->id,
                'opened_at' => now(),
                'opening_cash' => $openingCash,
                'expected_cash' => $openingCash,
                'notes' => $notes,
                ...($this->storeSchemaReady() ? ['photobox_store_id' => $storeId] : []),
                'status' => CashierShift::STATUS_OPEN,
            ]);

            // Tanpa toko & tanpa studio = semua studio. Dengan toko = semua studio toko itu. Dengan studio = hanya studio itu.
            // Kosong = semua studio. Diisi = transaksi photobox hanya dari studio-studio ini yang dikaitkan ke shift ini.
            if ($studioIds !== [] && Schema::hasTable('cashier_shift_studios')) {
                DB::table('cashier_shift_studios')->insert(array_map(fn ($id) => [
                    'cashier_shift_id' => $shift->id,
                    'photobox_studio_id' => $id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ], $studioIds));
            }

            return $shift;
        });
    }

    /**
     * Studio photobox yang bisa dipilih saat membuka shift.
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function studioOptions(): array
    {
        if (! Schema::hasTable('photobox_studios')) {
            return [];
        }

        return PhotoboxStudio::query()->orderBy('name')->get(['id', 'name'])
            ->map(fn ($s) => ['id' => (int) $s->id, 'name' => $s->name])->all();
    }

    /**
     * Studio yang dipegang shift, untuk ditampilkan. Kosong = semua studio.
     * Studio yang sudah dihapus tetap muncul ("Studio dihapus #id") supaya jelas shift ini terbatas.
     *
     * @return array<int, array{id: int, name: string}>
     */
    public function studiosOf(CashierShift $shift): array
    {
        $ids = $shift->studioIds();
        if ($ids === []) {
            return [];
        }

        $names = Schema::hasTable('photobox_studios')
            ? PhotoboxStudio::query()->whereIn('id', $ids)->pluck('name', 'id')
            : collect();

        return collect($ids)->map(fn ($id) => ['id' => $id, 'name' => $names[$id] ?? 'Studio dihapus #'.$id])->all();
    }

    public function calculateSummary(CashierShift $shift): array
    {
        $transactions = Transaction::query()
            ->where('cashier_shift_id', $shift->id);

        $salesReturns = SalesReturn::query()
            ->where('cashier_shift_id', $shift->id)
            ->where('status', 'completed');

        $cashSalesTotal = (int) (clone $transactions)
            ->where('payment_method', 'cash')
            ->where('payment_status', 'paid')
            ->sum('grand_total');

        $cashSalesTotal += (int) (clone $transactions)
            ->where('payment_method', 'dual_cash_qris')
            ->sum('dual_cash');

        $nonCashSalesTotal = (int) (clone $transactions)
            ->where('payment_method', '!=', 'cash')
            ->sum('grand_total');

        $nonCashSalesTotal -= (int) (clone $transactions)
            ->where('payment_method', 'dual_cash_qris')
            ->sum('grand_total');

        $nonCashSalesTotal += (int) (clone $transactions)
            ->where('payment_method', 'dual_cash_qris')
            ->sum('dual_qris');

        $cashRefundTotal = (int) (clone $salesReturns)
            ->where('return_type', 'refund_cash')
            ->sum('refund_amount');

        $nonCashRefundTotal = (int) (clone $salesReturns)
            ->where('return_type', '!=', 'refund_cash')
            ->sum(DB::raw('COALESCE(credited_amount, 0)'));

        $transactionsCount = (int) (clone $transactions)->count();
        $salesReturnsCount = (int) (clone $salesReturns)->count();
        $expectedCash = (int) $shift->opening_cash + $cashSalesTotal - $cashRefundTotal;

        $photobox = $this->calculatePhotoboxTotals($shift);

        return [
            'cash_sales_total' => $cashSalesTotal,
            'non_cash_sales_total' => $nonCashSalesTotal,
            // Photobox dibayar lewat QRIS langsung ke gateway (tidak lewat laci kas), jadi TIDAK mengubah expected_cash.
            'photobox_sales_total' => $photobox['photobox_sales_total'],
            'photobox_transactions_count' => $photobox['photobox_transactions_count'],
            'cash_refund_total' => $cashRefundTotal,
            'non_cash_refund_total' => $nonCashRefundTotal,
            'transactions_count' => $transactionsCount,
            'sales_returns_count' => $salesReturnsCount,
            'expected_cash' => $expectedCash,
        ];
    }

    /**
     * Jendela waktu shift: dari dibuka sampai ditutup (atau sampai sekarang kalau masih buka).
     * Transaksi photobox tidak punya kasir, jadi dikaitkan ke shift lewat waktu bayarnya.
     *
     * @return array{0: \Illuminate\Support\Carbon, 1: \Illuminate\Support\Carbon}
     */
    private function shiftWindow(CashierShift $shift): array
    {
        $end = $shift->closed_at ?? now();
        // Shift tidak mungkin berlangsung berhari-hari. Shift yang lupa ditutup (berbulan-bulan "open") tidak boleh
        // menelan transaksi photobox semua shift sesudahnya, jadi jendelanya dibatasi.
        $cap = $shift->opened_at->copy()->addHours(self::MAX_SHIFT_HOURS);

        return [$shift->opened_at, $end->greaterThan($cap) ? $cap : $end];
    }

    /** Batas wajar durasi satu shift untuk mengaitkan transaksi photobox (jam). */
    public const MAX_SHIFT_HOURS = 48;

    /**
     * Transaksi photobox yang LUNAS, asli (bukan TEST), dan milik shift ini.
     * Photobox tidak punya kasir, jadi dikaitkan lewat waktu bayar DAN cakupan studio/toko shift.
     *
     * Cakupan sebuah shift (makin spesifik makin menang):
     *   0 = studio eksplisit  (ada baris di cashier_shift_studios)
     *   1 = toko              (photobox_store_id terisi, tanpa baris studio) -> semua studio di toko itu, termasuk yang ditambah belakangan
     *   2 = semua studio      (tanpa toko, tanpa baris studio; termasuk semua shift lama)
     * Aturan pemilik (tiap transaksi punya tepat satu pemilik):
     *   1. shift hanya memiliki transaksi dari studio dalam cakupannya;
     *   2. bila beberapa shift terbuka mencakup transaksi itu, yang cakupannya lebih spesifik menang;
     *   3. setingkat: yang dibuka paling awal menang (seri: id terkecil), supaya tidak dobel.
     * Shift lupa-tutup tidak ikut mengklaim di luar batas durasi wajar (MAX_SHIFT_HOURS).
     */
    private function ownedPhotoboxQuery(CashierShift $shift)
    {
        [$start, $end] = $this->shiftWindow($shift);
        $studioIds = $shift->studioIds();
        $ready = $this->storeSchemaReady();
        $storeId = $ready && $shift->photobox_store_id ? (int) $shift->photobox_store_id : null;
        $rank = $studioIds !== [] ? 0 : ($storeId !== null ? 1 : 2);
        $hasPivot = Schema::hasTable('cashier_shift_studios');

        $query = PhotoboxTransaction::query()
            ->real()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end]);

        if ($rank === 0) {
            $query->whereIn('studio_id', $studioIds);
        } elseif ($rank === 1) {
            $query->whereIn('studio_id', fn ($q) => $q->select('id')->from('photobox_studios')->where('store_id', $storeId));
        }

        return $query->whereNotExists(function ($q) use ($shift, $start, $rank, $hasPivot, $ready) {
            $q->select(DB::raw(1))->from('cashier_shifts as other')
                ->where('other.id', '!=', $shift->id)
                ->whereColumn('other.opened_at', '<=', 'photobox_transactions.paid_at')
                // shift lain hanya "mengklaim" transaksi dalam batas durasi wajar sejak dibuka (shift lupa-tutup tidak ikut)
                ->where('other.opened_at', '>=', $start->copy()->subHours(self::MAX_SHIFT_HOURS))
                ->where(fn ($w) => $w->whereNull('other.closed_at')->orWhereColumn('other.closed_at', '>=', 'photobox_transactions.paid_at'));

            $earlierSql = '(other.opened_at < ? OR (other.opened_at = ? AND other.id < ?))';
            $earlierBind = [$shift->opened_at, $shift->opened_at, $shift->id];

            if (! $hasPivot) {
                $q->whereRaw($earlierSql, $earlierBind);

                return;
            }

            // Tingkat cakupan shift lain terhadap studio transaksi ini; NULL = tidak mencakup.
            $otherRank = $ready
                ? 'CASE
                WHEN EXISTS (SELECT 1 FROM cashier_shift_studios css WHERE css.cashier_shift_id = other.id AND css.photobox_studio_id = photobox_transactions.studio_id) THEN 0
                WHEN NOT EXISTS (SELECT 1 FROM cashier_shift_studios c2 WHERE c2.cashier_shift_id = other.id)
                     AND other.photobox_store_id IS NOT NULL
                     AND other.photobox_store_id = (SELECT ps.store_id FROM photobox_studios ps WHERE ps.id = photobox_transactions.studio_id) THEN 1
                WHEN NOT EXISTS (SELECT 1 FROM cashier_shift_studios c3 WHERE c3.cashier_shift_id = other.id)
                     AND other.photobox_store_id IS NULL THEN 2
                ELSE NULL END'
                : 'CASE
                WHEN EXISTS (SELECT 1 FROM cashier_shift_studios css WHERE css.cashier_shift_id = other.id AND css.photobox_studio_id = photobox_transactions.studio_id) THEN 0
                WHEN NOT EXISTS (SELECT 1 FROM cashier_shift_studios c3 WHERE c3.cashier_shift_id = other.id) THEN 2
                ELSE NULL END';

            // shift lain merebut bila cakupannya lebih spesifik, atau sama dan dibuka lebih awal
            $q->whereRaw("($otherRank) IS NOT NULL AND (($otherRank) < ? OR (($otherRank) = ? AND $earlierSql))", array_merge([$rank, $rank], $earlierBind));
        });
    }

    /**
     * Toko beserta studionya untuk pemilih saat buka shift. 'unassigned' = studio yang belum punya toko.
     *
     * @return array{stores: array<int, array{id:int,name:string,studios:array<int,array{id:int,name:string}>}>, unassigned: array<int, array{id:int,name:string}>}
     */
    public function storeOptions(): array
    {
        if (! Schema::hasTable('photobox_studios') || ! $this->storeSchemaReady()) {
            return ['stores' => [], 'unassigned' => $this->studioOptions()];
        }

        $studios = PhotoboxStudio::query()->orderBy('name')->get(['id', 'name', 'store_id']);
        $stores = PhotoboxStore::query()->orderBy('name')->get(['id', 'name'])->map(fn ($st) => [
            'id' => (int) $st->id,
            'name' => $st->name,
            'studios' => $studios->where('store_id', $st->id)->map(fn ($s) => ['id' => (int) $s->id, 'name' => $s->name])->values()->all(),
        ])->all();

        return [
            'stores' => $stores,
            'unassigned' => $studios->whereNull('store_id')->map(fn ($s) => ['id' => (int) $s->id, 'name' => $s->name])->values()->all(),
        ];
    }

    /** Teks cakupan shift untuk layar dan email: "Semua studio", "Toko CCM (semua studio)", "Toko CCM: Studio 1, Studio 2", "Studio A". */
    public function scopeLabel(CashierShift $shift): string
    {
        $studios = $this->studiosOf($shift);
        $storeId = $this->storeSchemaReady() && $shift->photobox_store_id ? (int) $shift->photobox_store_id : null;
        $store = null;
        if ($storeId !== null) {
            $name = Schema::hasTable('photobox_stores') ? PhotoboxStore::query()->whereKey($storeId)->value('name') : null;
            $store = 'Toko '.($name ?? 'dihapus #'.$storeId);
        }
        $names = implode(', ', array_column($studios, 'name'));

        return match (true) {
            $store !== null && $names === '' => $store.' (semua studio)',
            $store !== null => $store.': '.$names,
            $names !== '' => $names,
            default => 'Semua studio',
        };
    }

    /**
     * Penjualan photobox selama shift: hanya yang bernilai (amount > 0), tanpa yang dihapus di kasir dan tanpa transaksi TEST.
     * Aturannya sama dengan PhotoboxStats / dashboard supaya angkanya konsisten.
     *
     * @return array{photobox_sales_total: int, photobox_transactions_count: int}
     */
    public function calculatePhotoboxTotals(CashierShift $shift): array
    {
        if (! Schema::hasTable('photobox_transactions') || ! $shift->opened_at) {
            return ['photobox_sales_total' => 0, 'photobox_transactions_count' => 0];
        }

        $row = $this->ownedPhotoboxQuery($shift)
            ->where('amount', '>', 0)
            ->selectRaw('COALESCE(SUM(amount), 0) as total, COUNT(*) as cnt')
            ->first();

        return [
            'photobox_sales_total' => (int) ($row->total ?? 0),
            'photobox_transactions_count' => (int) ($row->cnt ?? 0),
        ];
    }

    /**
     * Angka photobox untuk ditampilkan: shift terbuka = hitung langsung; shift tertutup = angka yang tersimpan saat tutup.
     * Shift lama yang kolomnya masih null (dibuat sebelum fitur ini) dihitung langsung dari transaksi photobox.
     *
     * @return array{photobox_sales_total: int, photobox_transactions_count: int}
     */
    public function photoboxTotalsFor(CashierShift $shift): array
    {
        if (! $shift->isOpen() && $shift->photobox_sales_total !== null && $shift->photobox_transactions_count !== null) {
            return [
                'photobox_sales_total' => (int) $shift->photobox_sales_total,
                'photobox_transactions_count' => (int) $shift->photobox_transactions_count,
            ];
        }

        return $this->calculatePhotoboxTotals($shift);
    }

    /**
     * Rekap sesi photobox per studio selama shift (untuk email & halaman shift).
     * "sessions" termasuk sesi voucher bernilai 0 (pendapatannya sudah tercatat di transaksi kasir saat voucher dibeli).
     *
     * @return array{sessions: int, transactions: int, revenue: int, items: array<int, array{name: string, qty: int, revenue: int}>}
     */
    public function calculatePhotoboxRecap(CashierShift $shift): array
    {
        if (! Schema::hasTable('photobox_transactions') || ! $shift->opened_at) {
            return ['sessions' => 0, 'transactions' => 0, 'revenue' => 0, 'items' => []];
        }

        // Dikelompokkan per STUDIO (bukan per paket), apa pun sumber transaksinya (app photobox atau diedit manual di kasir).
        // Add Print dari app photobox dipisah jadi baris sendiri di bawah studionya ("sub"), dan tidak dihitung sebagai sesi foto.
        // Transaksi Rp 0 (mis. voucher permanen untuk membuka photobox) tidak ikut rekap; yang dihitung hanya yang bernominal.
        $rows = $this->ownedPhotoboxQuery($shift)
            ->where('photobox_transactions.amount', '>', 0)
            ->leftJoin('photobox_studios', 'photobox_studios.id', '=', 'photobox_transactions.studio_id')
            ->when($this->storeSchemaReady(), fn ($q) => $q->leftJoin('photobox_stores', 'photobox_stores.id', '=', 'photobox_studios.store_id'))
            ->select(
                DB::raw("COALESCE(photobox_studios.name, 'Tanpa studio') as name"),
                DB::raw($this->storeSchemaReady() ? 'photobox_stores.name as store_name' : 'NULL as store_name'),
                DB::raw("CASE WHEN photobox_transactions.mode = 'addprint' THEN 1 ELSE 0 END as is_addprint"),
                DB::raw('COUNT(*) as qty'),
                DB::raw('SUM(photobox_transactions.amount) as revenue')
            )
            ->groupBy(array_merge(
                array_filter(['photobox_studios.name', $this->storeSchemaReady() ? 'photobox_stores.name' : null]),
                [DB::raw("CASE WHEN photobox_transactions.mode = 'addprint' THEN 1 ELSE 0 END")]
            ))
            ->get();

        $studios = [];
        foreach ($rows as $r) {
            $studios[$r->name] ??= ['name' => $r->name, 'store' => $r->store_name, 'qty' => 0, 'revenue' => 0, 'sub' => false, 'addprint' => null];
            if ((int) $r->is_addprint === 1) {
                $studios[$r->name]['addprint'] = ['name' => 'Add Print (app)', 'qty' => (int) $r->qty, 'revenue' => (int) $r->revenue, 'sub' => true];
            } else {
                $studios[$r->name]['qty'] = (int) $r->qty;
                $studios[$r->name]['revenue'] = (int) $r->revenue;
            }
        }
        // Dikelompokkan per TOKO (studio tanpa toko di akhir), di dalam toko menurut jumlah sesi terbanyak.
        usort($studios, function ($a, $b) {
            $sa = $a['store'] ?? null;
            $sb = $b['store'] ?? null;
            if ($sa !== $sb) {
                if ($sa === null) {
                    return 1;
                }
                if ($sb === null) {
                    return -1;
                }

                return strcasecmp($sa, $sb);
            }

            return ($b['qty'] + ($b['addprint']['qty'] ?? 0)) <=> ($a['qty'] + ($a['addprint']['qty'] ?? 0));
        });

        $items = collect();
        $stores = [];
        foreach ($studios as $st) {
            $items->push(['name' => $st['name'], 'store' => $st['store'], 'qty' => $st['qty'], 'revenue' => $st['revenue'], 'sub' => false]);
            $key = $st['store'] ?? '';
            $stores[$key] ??= ['name' => $st['store'], 'qty' => 0, 'revenue' => 0];
            $stores[$key]['qty'] += $st['qty'];
            $stores[$key]['revenue'] += $st['revenue'] + ($st['addprint']['revenue'] ?? 0);
            if ($st['addprint']) {
                $items->push($st['addprint'] + ['store' => $st['store']]);
            }
        }
        $totals = $this->calculatePhotoboxTotals($shift);

        return [
            'sessions' => (int) $items->where('sub', false)->sum('qty'),
            'transactions' => $totals['photobox_transactions_count'],
            'revenue' => $totals['photobox_sales_total'],
            'items' => $items->all(),
            // subtotal per toko; hanya berarti bila ada studio yang punya toko
            'stores' => collect($stores)->contains(fn ($x) => $x['name'] !== null) ? array_values($stores) : [],
        ];
    }

    /** Status pembayaran yang dihitung sebagai "terjual" di rekap item (paid + pay later). Gateway yang masih pending tidak. */
    public const RECAP_PAYMENT_STATUSES = ['paid', 'unpaid'];

    /**
     * Rekap item yang terjual selama satu shift, dikelompokkan per kategori:
     * mis. "Emma's Room 12 paket, Jason's SnapBox 8 paket" lengkap dengan nominalnya.
     *
     * - qty / returned / net_qty : jumlah terjual, diretur (retur selesai di shift ini), dan bersihnya.
     *   Retur untuk barang yang dijual di shift lain tetap muncul supaya laci kas dan rekap barang cocok.
     * - revenue        : nominal terjual setelah SEMUA diskon: promo, voucher, diskon manual per item, lalu
     *                    diskon manual level transaksi dan tebusan poin yang dibagi proporsional ke tiap item
     *                    (cara yang sama dengan perhitungan profit). Ongkir tidak ikut.
     * - returned_value : nilai retur (subtotal item retur)
     * - net_revenue    : revenue - returned_value
     *
     * @return array{groups: array<int, array<string, mixed>>, totals: array<string, int>}
     */
    public function calculateItemRecap(CashierShift $shift): array
    {
        $details = TransactionDetail::query()
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->where('transactions.cashier_shift_id', $shift->id)
            ->whereIn('transactions.payment_status', self::RECAP_PAYMENT_STATUSES)
            ->orderBy('transaction_details.transaction_id')
            ->orderBy('transaction_details.id')
            ->get([
                'transaction_details.transaction_id',
                'transaction_details.product_id',
                'transaction_details.qty',
                'transaction_details.price',
                'transaction_details.voucher_discount',
                'transaction_details.item_manual_discount',
                'transactions.discount as trx_discount',
                'transactions.loyalty_discount_total as trx_loyalty',
            ]);

        $soldQty = [];
        $soldValue = [];

        foreach ($details->groupBy('transaction_id') as $lines) {
            $lines = $lines->values();
            $after = $lines->map(fn ($line) => max(
                0,
                (int) $line->price - (int) $line->voucher_discount - (int) $line->item_manual_discount
            ))->all();
            $afterSum = array_sum($after);

            // Diskon level transaksi + tebusan poin dibagi proporsional; baris terakhir menampung sisa
            // pembulatan supaya jumlah semua item persis sama dengan yang dibayar pelanggan (di luar ongkir).
            $transactionDiscount = min($afterSum, max(0, (int) $lines[0]->trx_discount + (int) $lines[0]->trx_loyalty));
            $allocated = 0;
            $lastIndex = $lines->count() - 1;

            foreach ($lines as $index => $line) {
                $share = 0;
                if ($afterSum > 0 && $transactionDiscount > 0) {
                    $share = $index === $lastIndex
                        ? max(0, $transactionDiscount - $allocated)
                        : (int) round($transactionDiscount * $after[$index] / $afterSum);
                    $allocated += $share;
                }

                $productId = (int) $line->product_id;
                $soldQty[$productId] = ($soldQty[$productId] ?? 0) + (int) $line->qty;
                $soldValue[$productId] = ($soldValue[$productId] ?? 0) + max(0, $after[$index] - $share);
            }
        }

        $returned = SalesReturnItem::query()
            ->join('sales_returns', 'sales_returns.id', '=', 'sales_return_items.sales_return_id')
            ->where('sales_returns.cashier_shift_id', $shift->id)
            ->where('sales_returns.status', 'completed')
            ->groupBy('sales_return_items.product_id')
            ->selectRaw('sales_return_items.product_id as product_id, SUM(sales_return_items.qty_return) as qty, SUM(sales_return_items.subtotal) as value')
            ->get()
            ->keyBy('product_id');

        $productIds = collect(array_keys($soldQty))->merge($returned->keys())->map(fn ($id) => (int) $id)->unique()->values();

        $products = Product::withTrashed()
            ->with('category:id,name')
            ->whereIn('id', $productIds)
            ->get(['id', 'title', 'category_id'])
            ->keyBy('id');

        $items = $productIds->map(function ($productId) use ($soldQty, $soldValue, $returned, $products) {
            $product = $products->get($productId);
            $qty = (int) ($soldQty[$productId] ?? 0);
            $returnedQty = (int) ($returned->get($productId)->qty ?? 0);
            $revenue = (int) ($soldValue[$productId] ?? 0);
            $returnedValue = (int) ($returned->get($productId)->value ?? 0);

            return [
                'product_id' => $productId,
                'title' => $product?->title ?? 'Produk #'.$productId,
                'category' => $product?->category?->name ?? 'Tanpa kategori',
                'qty' => $qty,
                'returned' => $returnedQty,
                'net_qty' => $qty - $returnedQty,
                'revenue' => $revenue,
                'returned_value' => $returnedValue,
                'net_revenue' => $revenue - $returnedValue,
            ];
        });

        $sum = fn ($collection) => [
            'qty' => (int) $collection->sum('qty'),
            'returned' => (int) $collection->sum('returned'),
            'net_qty' => (int) $collection->sum('net_qty'),
            'revenue' => (int) $collection->sum('revenue'),
            'returned_value' => (int) $collection->sum('returned_value'),
            'net_revenue' => (int) $collection->sum('net_revenue'),
        ];

        $groups = $items
            ->groupBy('category')
            ->map(function ($categoryItems, $category) use ($sum) {
                $sorted = $categoryItems->sortBy([['net_qty', 'desc'], ['title', 'asc']])->values();

                return ['category' => (string) $category, ...$sum($sorted), 'items' => $sorted->all()];
            })
            ->sortBy([['net_qty', 'desc'], ['category', 'asc']])
            ->values()
            ->all();

        return [
            'groups' => $groups,
            'totals' => [...$sum($items), 'products' => $items->count()],
        ];
    }

    public function closeShift(
        CashierShift $shift,
        User $actor,
        int $actualCash,
        ?string $closeNotes = null,
        bool $forceClose = false,
        int $expenseTotal = 0,
        ?string $expenseNotes = null
    ): CashierShift {
        if (! $shift->isOpen()) {
            throw ValidationException::withMessages([
                'shift' => 'Shift yang sudah ditutup tidak dapat diubah.',
            ]);
        }

        return DB::transaction(function () use ($shift, $actor, $actualCash, $closeNotes, $forceClose, $expenseTotal, $expenseNotes) {
            $lockedShift = CashierShift::query()->lockForUpdate()->findOrFail($shift->id);

            if (! $lockedShift->isOpen()) {
                throw ValidationException::withMessages([
                    'shift' => 'Shift yang sudah ditutup tidak dapat diubah.',
                ]);
            }

            $summary = $this->calculateSummary($lockedShift);

            // Pengeluaran (mis. selisih kembalian atau belanja galon yang diambil
            // dari laci kas) mengurangi kas yang seharusnya ada secara fisik,
            // sehingga dinetkan ke ekspektasi kas sebelum menghitung selisih.
            $adjustedExpectedCash = $summary['expected_cash'] - $expenseTotal;
            $cashDifference = $actualCash - $adjustedExpectedCash;

            $lockedShift->update([
                ...$summary,
                'actual_cash' => $actualCash,
                'cash_difference' => $cashDifference,
                'expense_total' => $expenseTotal,
                'expense_notes' => $expenseNotes,
                'closed_at' => now(),
                'closed_by' => $actor->id,
                'close_notes' => $closeNotes,
                'status' => $forceClose
                    ? CashierShift::STATUS_FORCE_CLOSED
                    : CashierShift::STATUS_CLOSED,
            ]);

            return $lockedShift->fresh(['user:id,name', 'openedBy:id,name', 'closedBy:id,name']);
        });
    }

    public function updateExpenses(
        CashierShift $shift,
        User $actor,
        int $expenseTotal = 0,
        ?string $expenseNotes = null,
        ?int $actualCash = null
    ): CashierShift {
        return DB::transaction(function () use ($shift, $expenseTotal, $expenseNotes, $actualCash) {
            $lockedShift = CashierShift::query()->lockForUpdate()->findOrFail($shift->id);

            // actual_cash cuma diganti kalau memang dikirim (buat koreksi kas
            // fisik yang kepencet salah pas nutup shift). Kalau nggak dikirim,
            // pakai nilai yang sudah tersimpan — nggak boleh ke-null-kan
            // begitu aja.
            $newActualCash = $actualCash ?? $lockedShift->actual_cash;

            // Pengeluaran mengurangi kas yang diharapkan ada secara fisik, sehingga
            // selisih closing dihitung ulang agar kartu penutupan tetap konsisten
            // meskipun pengeluaran diedit setelah shift ditutup.
            $adjustedExpectedCash = (int) $lockedShift->expected_cash - $expenseTotal;
            $cashDifference = $newActualCash !== null
                ? (int) $newActualCash - $adjustedExpectedCash
                : null;

            $lockedShift->update([
                'actual_cash' => $newActualCash,
                'expense_total' => $expenseTotal,
                'expense_notes' => $expenseNotes,
                'cash_difference' => $cashDifference,
            ]);

            return $lockedShift->fresh(['user:id,name', 'openedBy:id,name', 'closedBy:id,name']);
        });
    }

    public function summarizeForDisplay(?CashierShift $shift): ?array
    {
        if (! $shift) {
            return null;
        }

        $summary = $this->calculateSummary($shift);

        return [
            'id' => $shift->id,
            'status' => $shift->status,
            'opening_cash' => (int) $shift->opening_cash,
            'opened_at' => optional($shift->opened_at)?->toISOString(),
            'notes' => $shift->notes,
            'photobox_scope_label' => $this->scopeLabel($shift),
            'user' => $shift->user ? [
                'id' => $shift->user->id,
                'name' => $shift->user->name,
            ] : null,
            ...$summary,
        ];
    }

    public function visibleToUser(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin() || $user->can('cashier-shifts-force-close')) {
            return $query;
        }

        return $query->where('user_id', $user->id);
    }

    /**
     * Admin / superadmin melihat dan mengelola transaksi photobox SEMUA toko dan studio.
     * Sengaja TIDAK memakai izin `photobox-manage`: role `cashier` bawaan sudah memegang izin itu,
     * jadi kasir akan ikut dianggap admin dan batasan toko tidak berlaku.
     */
    public function canSeeAllPhotobox(User $user): bool
    {
        return $user->isSuperAdmin() || $user->hasRole('admin') || $user->can('cashier-shifts-force-close');
    }

    /**
     * Batas transaksi photobox yang boleh dilihat user di halaman Transaksi Photobox.
     * null = tanpa batas (admin). Selain itu hanya yang masuk cakupan SHIFT AKTIF-nya:
     *   studio_ids : daftar studio (null = semua studio, bila shift memegang "semua studio"),
     *   since      : waktu shift dibuka (yang lebih lama tidak tampil),
     *   has_shift  : false bila tidak ada shift aktif -> tidak ada yang tampil.
     *
     * @return array{studio_ids: ?array<int,int>, since: ?\Illuminate\Support\Carbon, has_shift: bool, label: ?string}|null
     */
    public function photoboxScopeFor(User $user): ?array
    {
        if ($this->canSeeAllPhotobox($user)) {
            return null;
        }
        $shift = $this->getActiveShiftForUser($user->id);
        if (! $shift) {
            return ['studio_ids' => [], 'since' => null, 'has_shift' => false, 'label' => null];
        }
        $ids = $shift->studioIds();
        if ($ids === [] && $shift->photobox_store_id && Schema::hasTable('photobox_studios') && Schema::hasColumn('photobox_studios', 'store_id')) {
            $ids = DB::table('photobox_studios')->where('store_id', $shift->photobox_store_id)->pluck('id')->map(fn ($i) => (int) $i)->all();
        } elseif ($ids === []) {
            $ids = null; // shift "semua studio"
        }

        return ['studio_ids' => $ids, 'since' => $shift->opened_at, 'has_shift' => true, 'label' => $this->scopeLabel($shift)];
    }

    public function reportRecipients(): array
    {
        return config('cashier_shift_report.recipients', []);
    }

    public function sendClosingReportEmail(CashierShift $shift, User $actor): CashierShift
    {
        if ($shift->isOpen()) {
            throw ValidationException::withMessages([
                'shift' => 'Shift harus ditutup terlebih dahulu sebelum mengirim laporan email.',
            ]);
        }

        $recipients = $this->reportRecipients();

        if (empty($recipients)) {
            throw ValidationException::withMessages([
                'email' => 'Belum ada penerima email laporan tutup kasir yang dikonfigurasi.',
            ]);
        }

        Mail::to($recipients)->send(new CashierShiftClosingReportMail($shift));

        $shift->update([
            'report_email_sent_at' => now(),
            'report_email_sent_to' => implode(', ', $recipients),
            'report_email_sent_by' => $actor->id,
        ]);

        return $shift->fresh(['user:id,name', 'openedBy:id,name', 'closedBy:id,name', 'reportEmailSentBy:id,name']);
    }
}
