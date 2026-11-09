<?php

namespace App\Services;

use App\Mail\CashierShiftClosingReportMail;
use App\Models\CashierShift;
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

    public function openShift(User $cashier, User $actor, int $openingCash, ?string $notes = null): CashierShift
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

        return CashierShift::create([
            'user_id' => $cashier->id,
            'opened_by' => $actor->id,
            'opened_at' => now(),
            'opening_cash' => $openingCash,
            'expected_cash' => $openingCash,
            'notes' => $notes,
            'status' => CashierShift::STATUS_OPEN,
        ]);
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
        return [$shift->opened_at, $shift->closed_at ?? now()];
    }

    /**
     * Penjualan photobox selama shift: hanya yang LUNAS dan bernilai (amount > 0), tanpa yang dihapus di kasir.
     * Aturannya sama dengan PhotoboxStats / dashboard supaya angkanya konsisten.
     * Transaksi uji (mode simulasi / sandbox) tidak pernah dikirim booth ke sini, jadi tidak ikut terhitung.
     *
     * @return array{photobox_sales_total: int, photobox_transactions_count: int}
     */
    public function calculatePhotoboxTotals(CashierShift $shift): array
    {
        if (! Schema::hasTable('photobox_transactions') || ! $shift->opened_at) {
            return ['photobox_sales_total' => 0, 'photobox_transactions_count' => 0];
        }

        [$start, $end] = $this->shiftWindow($shift);

        $row = PhotoboxTransaction::query()
            ->where('status', 'paid')
            ->where('amount', '>', 0)
            ->whereBetween('paid_at', [$start, $end])
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
     * Rekap sesi photobox per template/paket selama shift (untuk email & halaman shift).
     * "sessions" termasuk sesi voucher bernilai 0 (pendapatannya sudah tercatat di transaksi kasir saat voucher dibeli).
     *
     * @return array{sessions: int, transactions: int, revenue: int, items: array<int, array{name: string, qty: int, revenue: int}>}
     */
    public function calculatePhotoboxRecap(CashierShift $shift): array
    {
        if (! Schema::hasTable('photobox_transactions') || ! $shift->opened_at) {
            return ['sessions' => 0, 'transactions' => 0, 'revenue' => 0, 'items' => []];
        }

        [$start, $end] = $this->shiftWindow($shift);
        $stats = PhotoboxStats::summary($start, $end);

        return [
            'sessions' => (int) $stats['sessions'],
            'transactions' => (int) $stats['transactions'],
            'revenue' => (int) $stats['revenue'],
            'items' => collect($stats['byTemplate'])->all(),
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
