<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Sinyal kecil "data Photobox berubah" (transaksi masuk/berubah, status booth) ke dashboard lewat websocket (Reverb).
 * Halaman Transaksi Photobox mendengarkan lalu memuat ulang datanya sendiri (tanpa polling, tanpa refresh halaman).
 * Payload sengaja tanpa data sensitif: hanya jenis perubahan + id studio.
 */
class PhotoboxUpdated implements ShouldBroadcastNow
{
    use InteractsWithSockets;

    public function __construct(public string $kind = 'tx', public ?int $studioId = null) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('photobox')];
    }

    public function broadcastAs(): string
    {
        return 'PhotoboxUpdated';
    }

    public function broadcastWith(): array
    {
        return ['kind' => $this->kind, 'studio_id' => $this->studioId, 'at' => now()->toIso8601String()];
    }

    /**
     * Kirim sinyal tanpa pernah menggagalkan proses utama (mis. sinkron dari booth) kalau server websocket mati / belum disetel.
     * $throttle (detik) > 0: lewati kalau sinyal sejenis baru dikirim (dipakai untuk ping booth yang sering).
     */
    public static function fire(string $kind = 'tx', ?int $studioId = null, int $throttle = 0): void
    {
        if (config('broadcasting.default') === 'null' || config('broadcasting.default') === 'log') {
            return;
        }
        try {
            if ($throttle > 0 && ! Cache::add("pbx.bc.$kind.$studioId", 1, $throttle)) {
                return;
            }
            event(new static($kind, $studioId));
        } catch (\Throwable $e) {
            Log::warning('PhotoboxUpdated gagal disiarkan: '.$e->getMessage());
        }
    }
}
