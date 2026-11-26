<?php

namespace App\Console\Commands;

use App\Models\CashierShift;
use App\Services\AuditLogService;
use App\Services\CashierShiftService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CloseForgottenCashierShifts extends Command
{
    protected $signature = 'cashier-shifts:close-forgotten {--dry-run : Tampilkan saja tanpa menutup / mengirim email}';

    protected $description = 'Kirim pengingat untuk shift kasir yang lupa ditutup (24 & 40 jam) dan tutup otomatis setelah 48 jam.';

    public function handle(CashierShiftService $service, AuditLogService $audit): int
    {
        $dry = (bool) $this->option('dry-run');
        $closed = 0;
        $reminded = 0;

        CashierShift::query()->open()->with('user:id,name,email')->get()->each(function (CashierShift $shift) use ($service, $audit, $dry, &$closed, &$reminded) {
            $hours = (int) $shift->opened_at->diffInHours(now());

            if ($hours >= CashierShiftService::MAX_SHIFT_HOURS) {
                $this->line("Shift #{$shift->id} ({$shift->user?->name}) terbuka {$hours} jam -> tutup otomatis");
                if (! $dry && ($done = $service->autoCloseForgottenShift($shift))) {
                    $audit->log(
                        event: 'cashier_shift.auto_closed',
                        module: 'cashier_shift',
                        auditable: $done,
                        description: "Shift #{$done->id} ditutup otomatis setelah {$hours} jam (lupa ditutup).",
                        meta: ['hours_open' => $hours, 'cashier' => $done->user?->name],
                    );
                    $closed++;
                }

                return;
            }

            // pengingat: tahap tertinggi yang sudah terlewati, sekali saja per shift
            $stage = collect(CashierShiftService::REMINDER_HOURS)->filter(fn ($h) => $hours >= $h)->max();
            if ($stage === null) {
                return;
            }
            $this->line("Shift #{$shift->id} ({$shift->user?->name}) terbuka {$hours} jam -> pengingat tahap {$stage} jam");
            if ($dry || ! Cache::add("shift-forgotten-remind:{$shift->id}:{$stage}", 1, now()->addDays(5))) {
                return;
            }
            $reminded += $this->remind($service, $shift, $hours) ? 1 : 0;
        });

        $this->info("Selesai: {$closed} shift ditutup otomatis, {$reminded} pengingat dikirim.");

        return self::SUCCESS;
    }

    private function remind(CashierShiftService $service, CashierShift $shift, int $hours): bool
    {
        $to = collect([$shift->user?->email, ...$service->reportRecipients()])
            ->filter(fn ($e) => $e && filter_var($e, FILTER_VALIDATE_EMAIL))
            ->unique()->values()->all();
        if ($to === []) {
            return false;
        }
        $deadline = $shift->opened_at->copy()->addHours(CashierShiftService::MAX_SHIFT_HOURS)->timezone(config('app.timezone'))->format('d M Y H:i');
        $body = "Shift kasir #{$shift->id} atas nama {$shift->user?->name} (cakupan: {$service->scopeLabel($shift)}) sudah terbuka {$hours} jam dan belum ditutup.\n\n"
            ."Mohon tutup shift dan hitung kas fisik sebelum {$deadline}. Setelah itu sistem menutupnya otomatis tanpa kas fisik, dan transaksi photobox sesudah batas itu tidak ikut shift ini.";

        try {
            Mail::raw($body, fn ($m) => $m->to($to)->subject("Pengingat: shift kasir #{$shift->id} belum ditutup ({$hours} jam)"));

            return true;
        } catch (\Throwable $e) {
            Log::warning('Gagal kirim pengingat shift lupa tutup', ['shift' => $shift->id, 'error' => $e->getMessage()]);

            return false;
        }
    }
}
