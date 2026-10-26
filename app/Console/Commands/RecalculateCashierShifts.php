<?php

namespace App\Console\Commands;

use App\Services\CashierShiftService;
use Illuminate\Console\Command;
use App\Models\CashierShift;

class RecalculateCashierShifts extends Command
{
    protected $signature = 'cashier-shifts:recalculate
                            {--shift-id= : Recalculate hanya shift tertentu}
                            {--dry-run : Tampilkan perhitungan tanpa menyimpan}
                            {--chunk=200 : Jumlah shift per batch}';

    protected $description = 'Recalculate semua shift kasir dengan logika terbaru (termasuk dual payment).';

    public function handle(CashierShiftService $service): int
    {
        $chunk = (int) $this->option('chunk');
        $dryRun = (bool) $this->option('dry-run');
        $shiftId = $this->option('shift-id');

        if ($shiftId) {
            $shift = CashierShift::find($shiftId);

            if (! $shift) {
                $this->error("Shift #{$shiftId} tidak ditemukan.");

                return self::FAILURE;
            }

            $this->recalculateOne($service, $shift, $dryRun);

            return self::SUCCESS;
        }

        $total = CashierShift::count();

        if ($total === 0) {
            $this->info('Tidak ada data shift kasir yang perlu dihitung ulang.');

            return self::SUCCESS;
        }

        $this->info("Menghitung ulang {$total} shift kasir...");

        CashierShift::query()->chunk($chunk, function ($shifts) use ($service, $dryRun) {
            foreach ($shifts as $shift) {
                $this->recalculateOne($service, $shift, $dryRun);
            }
        });

        $this->newLine();
        $this->info('Selesai.');

        return self::SUCCESS;
    }

    private function recalculateOne(CashierShiftService $service, CashierShift $shift, bool $dryRun): void
    {
        $summary = $service->calculateSummary($shift);

        $this->line(
            sprintf(
                'Shift #%d | cash=%d | qris=%d | expected=%d | refund_cash=%d | refund_non_cash=%d',
                $shift->id,
                $summary['cash_sales_total'],
                $summary['non_cash_sales_total'],
                $summary['expected_cash'],
                $summary['cash_refund_total'],
                $summary['non_cash_refund_total']
            )
        );

        if ($dryRun) {
            return;
        }

        $shift->update([
            'cash_sales_total' => $summary['cash_sales_total'],
            'non_cash_sales_total' => $summary['non_cash_sales_total'],
            'cash_refund_total' => $summary['cash_refund_total'],
            'non_cash_refund_total' => $summary['non_cash_refund_total'],
            'transactions_count' => $summary['transactions_count'],
            'sales_returns_count' => $summary['sales_returns_count'],
            'expected_cash' => $summary['expected_cash'],
        ]);
    }
}
