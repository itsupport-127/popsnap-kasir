<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * Backup database kasir ke storage/app/backups (gzip), simpan N backup terakhir.
 * Dijadwalkan harian di routes/console.php (butuh cron `php artisan schedule:run` yang sama dengan crm:*).
 *
 *   php artisan db:backup                 # backup sekarang
 *   php artisan db:backup --keep=30       # simpan 30 backup terakhir
 *   php artisan db:backup --bin=/usr/bin/mariadb-dump
 *
 * Catatan: backup di server yang sama BUKAN pengganti salinan di tempat lain, copy foldernya sesekali (cloud / flashdisk).
 */
class BackupDatabase extends Command
{
    protected $signature = 'db:backup
        {--keep=14 : Jumlah backup terakhir yang disimpan}
        {--dir= : Folder tujuan (bawaan storage/app/backups)}
        {--bin= : Program dump (bawaan env DB_DUMP_BINARY atau mysqldump)}';

    protected $description = 'Backup database ke file .sql.gz dan hapus backup lama';

    public function handle(): int
    {
        $driver = config('database.default');
        $conn = config("database.connections.{$driver}");
        $dir = $this->option('dir') ?: storage_path('app/backups');
        $keep = max(1, (int) $this->option('keep'));

        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            $this->error("Folder backup tidak bisa dibuat: {$dir}");

            return self::FAILURE;
        }

        $stamp = now()->format('Ymd-His');

        if ($driver === 'sqlite') {
            $db = (string) ($conn['database'] ?? '');
            if ($db === '' || $db === ':memory:' || ! is_file($db)) {
                $this->error('Database sqlite tidak ditemukan / berupa memori, tidak ada yang bisa di-backup.');

                return self::FAILURE;
            }
            $file = "{$dir}/kasir-{$stamp}.sqlite";
            if (! copy($db, $file)) {
                $this->error('Gagal menyalin file sqlite.');

                return self::FAILURE;
            }
        } elseif (in_array($driver, ['mysql', 'mariadb'], true)) {
            $file = "{$dir}/kasir-{$stamp}.sql.gz";
            if (! $this->dumpMysql($conn, $file)) {
                return self::FAILURE;
            }
        } else {
            $this->error("Driver database '{$driver}' belum didukung untuk backup otomatis.");

            return self::FAILURE;
        }

        $this->info(sprintf('Backup selesai: %s (%s KB)', basename($file), number_format(filesize($file) / 1024, 1)));
        $this->prune($dir, $keep);

        return self::SUCCESS;
    }

    private function dumpMysql(array $conn, string $file): bool
    {
        $bin = $this->option('bin') ?: env('DB_DUMP_BINARY', 'mysqldump');
        $args = [$bin, '--single-transaction', '--routines', '--triggers', '--no-tablespaces', '--default-character-set=utf8mb4'];
        if (! empty($conn['unix_socket'])) {
            $args[] = '--socket='.$conn['unix_socket'];
        } else {
            $args[] = '--host='.($conn['host'] ?? '127.0.0.1');
            $args[] = '--port='.($conn['port'] ?? 3306);
        }
        $args[] = '--user='.($conn['username'] ?? 'root');
        $args[] = (string) ($conn['database'] ?? '');

        // Sandi lewat environment (bukan argumen) supaya tidak terlihat di daftar proses.
        $process = new Process($args, null, ['MYSQL_PWD' => (string) ($conn['password'] ?? '')], null, 1800);

        $gz = @gzopen($file, 'wb6');
        if (! $gz) {
            $this->error("File backup tidak bisa ditulis: {$file}");

            return false;
        }

        $tail = '';
        $err = '';
        try {
            $process->run(function ($type, $buffer) use ($gz, &$tail, &$err) {
                if ($type === Process::OUT) {
                    gzwrite($gz, $buffer);
                    $tail = substr($tail.$buffer, -300);
                } else {
                    $err .= $buffer;
                }
            });
        } catch (\Throwable $e) {
            $err .= $e->getMessage();
        }
        gzclose($gz);

        // mysqldump menutup dump yang utuh dengan baris "-- Dump completed": kalau tidak ada, backup dianggap gagal
        if (! $process->isSuccessful() || ! str_contains($tail, 'Dump completed')) {
            @unlink($file);
            $this->error('Backup gagal: '.trim($err !== '' ? $err : 'dump tidak lengkap. Pastikan mysqldump terpasang (atau isi DB_DUMP_BINARY / --bin).'));

            return false;
        }

        return true;
    }

    private function prune(string $dir, int $keep): void
    {
        $files = array_values(array_filter(scandir($dir) ?: [], fn ($f) => preg_match('/^kasir-\d{8}-\d{6}\.(sql\.gz|sqlite)$/', $f)));
        sort($files);
        foreach (array_slice($files, 0, max(0, count($files) - $keep)) as $old) {
            @unlink("{$dir}/{$old}");
            $this->line("Backup lama dihapus: {$old}");
        }
    }
}
