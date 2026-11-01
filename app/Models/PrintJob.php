<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrintJob extends Model
{
    public const QUEUED = 'queued';

    public const PRINTING = 'printing';

    public const PRINTED = 'printed';

    public const FAILED = 'failed';

    /** Perintah yang belum diambil PC manapun lebih lama dari ini tidak dieksekusi lagi (struk basi). */
    public const PENDING_WINDOW_MINUTES = 5;

    /** Perintah yang diklaim tapi tidak pernah dilaporkan selesai (PC mati / tab ditutup) dianggap gagal. */
    public const STUCK_AFTER_MINUTES = 2;

    protected $fillable = [
        'invoice', 'size', 'requested_by', 'status', 'error', 'printed_by', 'claimed_at', 'finished_at',
    ];

    protected $casts = [
        'claimed_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    /** Riwayat di-reset tiap hari: buang semua yang dibuat sebelum hari ini (tanpa perlu scheduler). */
    public static function pruneOld(): int
    {
        return static::where('created_at', '<', now()->startOfDay())->delete();
    }

    /** Yang sedang "printing" terlalu lama berarti pelaksananya hilang: tandai gagal supaya tidak menggantung. */
    public static function failStuck(): int
    {
        return static::where('status', self::PRINTING)
            ->where('claimed_at', '<', now()->subMinutes(self::STUCK_AFTER_MINUTES))
            ->update([
                'status' => self::FAILED,
                'error' => 'Tidak ada laporan dari PC kasir (tab ditutup / QZ Tray terputus).',
                'finished_at' => now(),
            ]);
    }
}
