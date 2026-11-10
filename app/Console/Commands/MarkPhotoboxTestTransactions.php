<?php

namespace App\Console\Commands;

use App\Models\PhotoboxTransaction;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Menandai transaksi photobox LAMA yang sebenarnya hasil simulasi / sandbox sebagai TEST,
 * supaya tidak lagi dihitung sebagai pendapatan, shift, dan dashboard. Booth ikut menerima tandanya.
 *
 * Contoh:
 *   php artisan photobox:mark-test --from=2026-11-01 --to=2026-11-08 --dry-run
 *   php artisan photobox:mark-test --from=2026-11-01 --to=2026-11-08 --studio=2
 *   php artisan photobox:mark-test --from=2026-11-01 --to=2026-11-08 --undo   (kembalikan jadi transaksi asli)
 */
class MarkPhotoboxTestTransactions extends Command
{
    protected $signature = 'photobox:mark-test
        {--from= : Tanggal mulai (YYYY-MM-DD), wajib}
        {--to= : Tanggal akhir (YYYY-MM-DD), wajib}
        {--studio= : Batasi ke satu studio (id)}
        {--undo : Kebalikan: lepas tanda TEST}
        {--dry-run : Hanya tampilkan, tidak mengubah apa pun}
        {--yes : Lewati konfirmasi}';

    protected $description = 'Tandai (atau lepas tanda) TEST pada transaksi photobox dalam rentang tanggal';

    public function handle(): int
    {
        if (! $this->option('from') || ! $this->option('to')) {
            $this->error('Isi --from dan --to (format YYYY-MM-DD).');

            return self::INVALID;
        }

        try {
            $tz = config('app.timezone');
            $from = Carbon::parse($this->option('from'), $tz)->startOfDay();
            $to = Carbon::parse($this->option('to'), $tz)->endOfDay();
        } catch (\Throwable) {
            $this->error('Format tanggal tidak valid. Pakai YYYY-MM-DD.');

            return self::INVALID;
        }
        if ($to->lt($from)) {
            $this->error('--to tidak boleh lebih awal dari --from.');

            return self::INVALID;
        }

        $target = ! $this->option('undo'); // true = jadikan TEST
        $query = PhotoboxTransaction::query()
            ->where('is_test', ! $target)
            ->whereRaw('COALESCE(paid_at, booth_created_at, created_at) BETWEEN ? AND ?', [$from, $to])
            ->when($this->option('studio'), fn ($q, $id) => $q->where('studio_id', (int) $id));

        $count = (clone $query)->count();
        $paid = (int) (clone $query)->where('status', 'paid')->sum('amount');

        $this->info(sprintf('%d transaksi akan %s (pendapatan lunas yang %s: Rp %s).',
            $count, $target ? 'ditandai TEST' : 'dikembalikan jadi transaksi asli', $target ? 'tidak dihitung lagi' : 'dihitung lagi', number_format($paid, 0, ',', '.')));

        if ($count === 0 || $this->option('dry-run')) {
            if ($this->option('dry-run')) {
                $this->comment('Dry-run: tidak ada yang diubah.');
            }

            return self::SUCCESS;
        }
        if (! $this->option('yes') && ! $this->confirm('Lanjutkan?')) {
            return self::SUCCESS;
        }

        // edited_at ditandai: booth menarik perubahan ini lewat /changes, dan kiriman ulang dari booth tidak menimpanya.
        // Dibagi per 100 baris dengan waktu berselisih 1 detik: booth membaca perubahan per halaman (200 baris) memakai waktu sebagai
        // penanda, jadi banyak baris dengan waktu yang persis sama bisa membuatnya berhenti di tengah.
        $changed = 0;
        $base = now();
        $query->orderBy('id')->pluck('id')->chunk(100)->each(function ($ids, $i) use ($target, $base, &$changed) {
            $changed += PhotoboxTransaction::query()->whereIn('id', $ids->all())
                ->update(['is_test' => $target, 'edited_at' => $base->copy()->addSeconds($i)]);
        });
        $this->info("Selesai: {$changed} transaksi diubah.");

        return self::SUCCESS;
    }
}
