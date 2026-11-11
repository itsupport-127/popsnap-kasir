<?php

namespace App\Console\Commands;

use App\Models\PhotoboxStudio;
use App\Models\PhotoboxTransaction;
use App\Services\GalleryStatus;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;

/**
 * Menarik sesi terbaru dari POPSNAP Galeri yang BELUM punya transaksi di kasir, mis. hasil unggah manual (website galeri) saat
 * internet booth mati. Tiap sesi dibuatkan baris di Transaksi Photobox (metode Gratis, Rp 0, nomor GAL-<kode>) lengkap dengan
 * link galeri, jadi tombol WhatsApp bekerja seperti biasa. Begitu booth online dan mengirim transaksi aslinya, baris sementara
 * ini diganti otomatis (nomor WA yang sudah diisi ikut terbawa), tanpa hitungan ganda: Rp 0 dan tidak dihitung sebagai pendapatan.
 *
 * Butuh kunci bersama: POPSNAP_GALLERY_KEY di kasir = KASIR_API_KEY di galeri. Tanpa kunci / alamat galeri, perintah ini tidak melakukan apa-apa.
 */
class PullGallerySessions extends Command
{
    protected $signature = 'photobox:pull-gallery {--days=3 : Ambil sesi galeri dari N hari terakhir}';

    protected $description = 'Tarik sesi galeri (mis. unggah manual) yang belum punya transaksi di kasir';

    public function handle(): int
    {
        $base = GalleryStatus::baseUrl();
        $key = (string) config('services.popsnap_gallery.key');
        if (! $base || $key === '' || ! Schema::hasTable('photobox_transactions')) {
            return self::SUCCESS; // fitur belum dinyalakan
        }

        $days = max(1, min(30, (int) $this->option('days')));
        try {
            $res = Http::timeout(10)->withToken($key)->acceptJson()->get($base.'/api/v1/recent', ['days' => $days]);
        } catch (\Throwable $e) {
            $this->warn('Galeri tidak terjangkau: '.$e->getMessage());

            return self::SUCCESS; // sementara: dicoba lagi pada putaran berikutnya
        }
        if ($res->status() === 401) {
            $this->error('Kunci ditolak galeri: samakan POPSNAP_GALLERY_KEY (kasir) dengan KASIR_API_KEY (galeri).');

            return self::FAILURE;
        }
        if (! $res->ok()) {
            $this->warn('Galeri menjawab HTTP '.$res->status().'.');

            return self::SUCCESS;
        }

        $sessions = $res->json('sessions') ?? [];
        if (! $sessions) {
            return self::SUCCESS;
        }

        // Kode link yang sudah punya baris (termasuk yang dihapus di kasir: jangan dibuat ulang)
        $known = [];
        PhotoboxTransaction::withTrashed()->whereNotNull('gallery_url')->where('created_at', '>=', now()->subDays($days + 14))
            ->pluck('gallery_url')->each(function ($u) use (&$known) {
                if ($t = GalleryStatus::tokenFromUrl($u)) {
                    $known[$t] = true;
                }
            });

        $studios = PhotoboxStudio::query()->where('is_active', true)->get();
        $byName = $studios->keyBy(fn ($s) => mb_strtolower(trim($s->name)));
        $tz = config('app.timezone');
        $made = 0;
        $unmatched = 0;

        foreach ($sessions as $s) {
            $token = (string) ($s['token'] ?? '');
            if (! preg_match('/^[A-Za-z0-9_-]{10,40}$/', $token) || isset($known[$token])) {
                continue;
            }
            // Studio dikenali dari nama (galeri mengikuti nama dari kasir); kalau hanya ada satu studio aktif, pakai itu
            $studio = $byName->get(mb_strtolower(trim((string) ($s['studio'] ?? ''))))
                ?? ($studios->count() === 1 ? $studios->first() : null);
            if (! $studio) {
                $unmatched++;

                continue;
            }
            $at = ! empty($s['createdAt']) ? Carbon::createFromTimestampMs((int) $s['createdAt'])->setTimezone($tz) : now();
            try {
                PhotoboxTransaction::create([
                    'order_no' => 'GAL-'.$token,
                    'studio_id' => $studio->id,
                    'template_name' => 'Unggah manual (galeri)',
                    'mode' => in_array($s['mode'] ?? '', ['photo', 'gif', 'boomerang', 'video'], true) ? $s['mode'] : 'photo',
                    'method' => 'free',
                    'amount' => 0,
                    'status' => 'paid',
                    'paid_at' => $at,
                    'booth_created_at' => $at,
                    'gallery_url' => $base.'/s/'.$token,
                    'meta' => ['source' => 'gallery'],
                ]);
                $made++;
            } catch (QueryException) {
                // nomor sudah ada (proses lain lebih dulu): aman dilewati
            }
        }

        if ($made) {
            $this->info("{$made} sesi galeri ditambahkan ke Transaksi Photobox.");
        }
        if ($unmatched) {
            $this->warn("{$unmatched} sesi galeri dilewati: nama studio tidak cocok dengan studio di kasir.");
        }

        return self::SUCCESS;
    }
}
