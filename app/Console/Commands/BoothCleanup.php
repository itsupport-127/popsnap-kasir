<?php

namespace App\Console\Commands;

use App\Models\BoothMedia;
use Illuminate\Console\Command;
use App\Services\Booth\BoothStorage;

class BoothCleanup extends Command
{
    protected $signature = 'booth:cleanup';
    protected $description = 'Hapus media booth yang lebih lama dari BOOTH_RETENTION_DAYS.';

    public function handle(): int
    {
        $disk = BoothStorage::disk();
        $n = 0;
        BoothMedia::whereNull('deleted_at')->where('created_at', '<', now()->subDays(config('booth.retention_days')))
            ->chunkById(200, function ($rows) use ($disk, &$n) {
                foreach ($rows as $m) {
                    try { $disk->delete($m->path); } catch (\Throwable) {}
                    $m->update(['deleted_at' => now()]);
                    $n++;
                }
            });
        $this->info("$n file dihapus.");

        return self::SUCCESS;
    }
}
