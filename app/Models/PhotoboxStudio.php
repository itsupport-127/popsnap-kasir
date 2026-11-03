<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PhotoboxStudio extends Model
{
    protected $fillable = ['name', 'token_hash', 'token_hint', 'is_active', 'last_seen_at', 'sync_report', 'report_at'];

    protected $casts = ['is_active' => 'boolean', 'last_seen_at' => 'datetime', 'sync_report' => 'array', 'report_at' => 'datetime'];

    protected $hidden = ['token_hash'];

    public function transactions()
    {
        return $this->hasMany(PhotoboxTransaction::class, 'studio_id');
    }

    /** Buat token baru: simpan hash-nya, kembalikan token asli (tampil sekali). */
    public function issueToken(): string
    {
        $token = 'pbx_'.Str::random(40);
        $this->forceFill(['token_hash' => hash('sha256', $token), 'token_hint' => substr($token, -4)])->save();

        return $token;
    }

    /** Jendela online: booth melapor tiap ±1 menit, jadi lewat 5 menit tanpa kabar dianggap offline. */
    public const ONLINE_MINUTES = 5;

    /**
     * Ringkasan status dari laporan terakhir booth.
     * state: offline (tidak ada kabar) | unknown (belum pernah melapor) | bad (disk hampir penuh) | warn (ada error kirim) | queue (ada antrean) | ok
     *
     * @return array{state:string,notes:array<int,string>}
     */
    public function reportSummary(): array
    {
        $seen = $this->report_at ?? $this->last_seen_at;
        if (! $seen) {
            return ['state' => 'unknown', 'notes' => ['Belum pernah terhubung.']];
        }
        if ($seen->lt(now()->subMinutes(self::ONLINE_MINUTES))) {
            return ['state' => 'offline', 'notes' => ['Tidak ada kabar dari booth lebih dari '.self::ONLINE_MINUTES.' menit.']];
        }
        $r = $this->sync_report;
        if (! is_array($r)) {
            return ['state' => 'unknown', 'notes' => ['Terhubung, tetapi app Photobox belum mengirim laporan status (perlu diperbarui).']];
        }

        $notes = [];
        $state = 'ok';
        $raise = function (string $to) use (&$state) {
            $rank = ['ok' => 0, 'queue' => 1, 'warn' => 2, 'bad' => 3];
            if ($rank[$to] > $rank[$state]) {
                $state = $to;
            }
        };
        if (($r['diskLevel'] ?? 'ok') === 'critical') {
            $raise('bad');
            $notes[] = 'Ruang disk hampir habis'.(isset($r['diskFreeGB']) ? ' ('.$r['diskFreeGB'].' GB)' : '').'; booth menolak sesi baru.';
        } elseif (($r['diskLevel'] ?? 'ok') === 'low') {
            $raise('warn');
            $notes[] = 'Ruang disk menipis'.(isset($r['diskFreeGB']) ? ' ('.$r['diskFreeGB'].' GB)' : '').'.';
        }
        if (($r['errors'] ?? 0) > 0) {
            $raise('warn');
            $notes[] = $r['errors'].' transaksi gagal dikirim ke kasir'.(($r['lastError'] ?? '') !== '' ? ': '.$r['lastError'] : '.');
        }
        if (($r['galleryErrors'] ?? 0) > 0) {
            $raise('warn');
            $notes[] = $r['galleryErrors'].' sesi gagal diunggah ke galeri'.(($r['galleryLastError'] ?? '') !== '' ? ': '.$r['galleryLastError'] : '.');
        }
        $queue = (int) ($r['pending'] ?? 0) + (int) ($r['galleryPending'] ?? 0);
        if ($queue > 0) {
            $raise('queue');
            $notes[] = ($r['pending'] ?? 0).' transaksi dan '.($r['galleryPending'] ?? 0).' sesi galeri masih antre.';
        }

        return ['state' => $state, 'notes' => $notes];
    }

    public static function findByToken(?string $token): ?self
    {
        if (! $token) {
            return null;
        }

        return static::where('token_hash', hash('sha256', $token))->where('is_active', true)->first();
    }
}
