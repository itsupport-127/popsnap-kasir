<?php

namespace App\Services;

use App\Models\PhotoboxTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Ringkasan Photobox untuk satu rentang waktu. Hanya transaksi status "paid" yang dihitung
 * (konsisten dengan omzet kasir). "Produk terjual" = jumlah sesi per template/paket.
 * Voucher yang dibayar di kasir (amount 0 di booth) ikut dihitung sebagai sesi, tapi tidak menambah pendapatan
 * (pendapatannya sudah tercatat di transaksi kasir).
 */
class PhotoboxStats
{
    public static function summary(Carbon $start, Carbon $end, ?int $studioId = null): array
    {
        $base = fn () => PhotoboxTransaction::query()
            ->where('status', 'paid')
            ->whereBetween('paid_at', [$start, $end])
            ->when($studioId, fn ($q) => $q->where('studio_id', $studioId));

        $byStudio = $base()
            ->join('photobox_studios', 'photobox_studios.id', '=', 'photobox_transactions.studio_id')
            ->select('photobox_studios.id', 'photobox_studios.name', DB::raw('COUNT(*) as sessions'), DB::raw('SUM(photobox_transactions.amount) as revenue'),
                DB::raw('SUM(CASE WHEN photobox_transactions.amount > 0 THEN 1 ELSE 0 END) as transactions'))
            ->groupBy('photobox_studios.id', 'photobox_studios.name')
            ->orderByDesc('revenue')
            ->get()
            ->map(fn ($r) => ['id' => (int) $r->id, 'name' => $r->name, 'sessions' => (int) $r->sessions, 'transactions' => (int) $r->transactions, 'revenue' => (int) $r->revenue])
            ->values();

        $byTemplate = $base()
            ->select(DB::raw("COALESCE(template_name, 'Tanpa nama') as name"), DB::raw('COUNT(*) as qty'), DB::raw('SUM(amount) as revenue'))
            ->groupBy('name')
            ->orderByDesc('qty')
            ->get()
            ->map(fn ($r) => ['name' => $r->name, 'qty' => (int) $r->qty, 'revenue' => (int) $r->revenue])
            ->values();

        $byMethod = $base()
            ->select('method', DB::raw('COUNT(*) as count'), DB::raw('SUM(amount) as total'))
            ->groupBy('method')
            ->get()
            ->map(fn ($r) => ['method' => $r->method, 'count' => (int) $r->count, 'total' => (int) $r->total])
            ->values();

        return [
            'revenue' => (int) $byStudio->sum('revenue'),
            'sessions' => (int) $byStudio->sum('sessions'),
            // "Transaksi" = sesi yang benar-benar membayar di booth (amount > 0). Sesi voucher bernilai 0 sudah tercatat
            // sebagai transaksi kasir saat voucher dibeli, jadi tidak dihitung lagi supaya tidak dobel.
            'transactions' => (int) $byStudio->sum('transactions'),
            'byStudio' => $byStudio,
            'byTemplate' => $byTemplate,
            'byMethod' => $byMethod,
        ];
    }

    /**
     * Pendapatan photobox per bucket waktu, dengan kunci/label yang SAMA dengan tren pendapatan dashboard kasir
     * (year/all = per bulan, month/custom/week = per hari, today = per jam). Dikelompokkan di PHP supaya tidak bergantung dialek SQL.
     *
     * @return array<string, array{date:string,label:string,total:int}>
     */
    public static function trend(string $period, Carbon $start, Carbon $end, ?int $studioId = null): array
    {
        $rows = PhotoboxTransaction::query()
            ->where('status', 'paid')->where('amount', '>', 0)
            ->whereBetween('paid_at', [$start, $end])
            ->when($studioId, fn ($q) => $q->where('studio_id', $studioId))
            ->get(['paid_at', 'amount']);

        $out = [];
        foreach ($rows as $r) {
            $t = $r->paid_at->copy()->setTimezone(config('app.timezone'));
            if ($period === 'year' || $period === 'all') {
                $key = $t->year.'-'.$t->month;
                $label = $t->copy()->startOfMonth()->isoFormat('MMM YY');
            } elseif ($period === 'month' || $period === 'custom') {
                $key = $t->toDateString();
                $label = $t->isoFormat('D MMM');
            } elseif ($period === 'week') {
                $key = $t->toDateString();
                $label = $t->isoFormat('ddd D');
            } else {
                $key = $t->hour.':00';
                $label = str_pad((string) $t->hour, 2, '0', STR_PAD_LEFT).':00';
            }
            $out[$key] ??= ['date' => $key, 'label' => $label, 'total' => 0];
            $out[$key]['total'] += (int) $r->amount;
        }

        return $out;
    }

    /** Rentang waktu (zona Asia/Jakarta, sama dengan Dashboard): today | week | month | year | all | custom */
    public static function range(string $period, ?string $customStart = null, ?string $customEnd = null): array
    {
        $now = Carbon::now()->setTimezone('Asia/Jakarta');

        return match ($period) {
            'week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'all' => [Carbon::parse('2000-01-01')->startOfDay(), $now->copy()->endOfDay()],
            'custom' => self::customRange($customStart, $customEnd, $now),
            default => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
        };
    }

    private static function customRange(?string $from, ?string $to, Carbon $now): array
    {
        try {
            $s = Carbon::parse($from ?: $now->toDateString())->startOfDay();
            $e = Carbon::parse($to ?: $now->toDateString())->endOfDay();
        } catch (\Throwable) {
            return [$now->copy()->startOfDay(), $now->copy()->endOfDay()];
        }

        return $e->lt($s) ? [$e->copy()->startOfDay(), $s->copy()->endOfDay()] : [$s, $e];
    }
}
