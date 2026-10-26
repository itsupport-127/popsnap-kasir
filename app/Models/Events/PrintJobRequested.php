<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Queue\SerializesModels;

/**
 * Dipicu dari HP (browser) ketika user menekan tombol "Print dari HP".
 * PC kasir yang sedang membuka halaman /print-listener akan menerima event
 * ini lewat websocket (Reverb) dan otomatis mengirim struk ke QZ Tray.
 */
class PrintJobRequested implements ShouldBroadcastNow
{
    use InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $invoice,
        public string $size,
        public string $requestedBy,
    ) {}

    /**
     * Channel per-store (private) supaya cuma user yang login & auth ke
     * channel ini yang bisa dengar / kirim perintah print.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('print-jobs'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'PrintJobRequested';
    }

    public function broadcastWith(): array
    {
        return [
            'invoice' => $this->invoice,
            'size' => $this->size,
            'requested_by' => $this->requestedBy,
            'requested_at' => now()->toIso8601String(),
        ];
    }
}
