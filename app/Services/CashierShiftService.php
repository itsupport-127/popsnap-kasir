<?php

namespace App\Services;

use App\Mail\CashierShiftClosingReportMail;
use App\Models\CashierShift;
use App\Models\SalesReturn;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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

        return [
            'cash_sales_total' => $cashSalesTotal,
            'non_cash_sales_total' => $nonCashSalesTotal,
            'cash_refund_total' => $cashRefundTotal,
            'non_cash_refund_total' => $nonCashRefundTotal,
            'transactions_count' => $transactionsCount,
            'sales_returns_count' => $salesReturnsCount,
            'expected_cash' => $expectedCash,
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
