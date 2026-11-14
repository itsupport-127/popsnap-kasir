<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Status sesi di POPSNAP Galeri (sudah diunggah atau belum, izin foto dari tamu).
 * Dipakai di menu Transaksi Photobox: penanda "Belum terupload" supaya CS tahu harus unggah manual,
 * dan status izin sosmed / mading. Kalau galeri tidak terjangkau, hasilnya kosong (halaman tetap jalan).
 */
class GalleryStatus
{
    public static function baseUrl(): ?string
    {
        $u = rtrim((string) config('services.popsnap_gallery.url'), '/');

        return $u !== '' ? $u : null;
    }

    /** Ambil kode link dari URL galeri (…/s/<kode>), hanya kalau host-nya sama dengan galeri yang dikonfigurasi. */
    public static function tokenFromUrl(?string $url): ?string
    {
        $base = self::baseUrl();
        if (! $url || ! $base) {
            return null;
        }
        if (strcasecmp((string) parse_url($url, PHP_URL_HOST), (string) parse_url($base, PHP_URL_HOST)) !== 0) {
            return null;
        }

        return preg_match('#/s/([A-Za-z0-9_-]{10,40})/?(?:[?\#].*)?$#', $url, $m) ? $m[1] : null;
    }

    /**
     * @param  array<int,string>  $tokens
     * @return array<string,array{exists:bool,files:int,expired:bool,social:?int}>
     */
    public static function fetch(array $tokens): array
    {
        $base = self::baseUrl();
        $tokens = array_values(array_unique(array_filter($tokens)));
        if (! $base || ! $tokens) {
            return [];
        }
        $key = 'gallery-status:'.md5(implode(',', $tokens));

        return Cache::remember($key, 10, function () use ($base, $tokens) {
            try {
                $r = Http::timeout(4)->acceptJson()->post($base.'/api/v1/status', ['tokens' => $tokens]);

                return $r->ok() ? ($r->json('sessions') ?? []) : [];
            } catch (\Throwable) {
                return [];
            }
        });
    }

    /**
     * Status unggahan satu transaksi menurut galeri.
     * null = transaksi belum punya link galeri; unknown = galeri tidak menjawab; expired; pending = belum ada file;
     * partial = sebagian file sudah masuk, sebagian masih menunggu; uploaded = semua file yang terdaftar sudah masuk.
     *
     * @param  array<string,mixed>|null  $g  jawaban galeri untuk kode link transaksi ini
     */
    public static function state(bool $hasUrl, ?array $g): ?string
    {
        if (! $hasUrl) {
            return null;
        }
        if (! $g) {
            return 'unknown';
        }
        if (! empty($g['expired'])) {
            return 'expired';
        }
        if (($g['files'] ?? 0) <= 0) {
            return 'pending';
        }

        return ($g['pending'] ?? 0) > 0 ? 'partial' : 'uploaded';
    }

    /**
     * Sesi galeri LAIN dengan nomor transaksi yang sama dan sudah berisi file (mis. unggah manual lama memakai kode link berbeda).
     *
     * @param  array<int,array<string,mixed>>  $candidates  hasil fetchByOrders untuk nomor transaksi ini
     * @return array{code:string,files:int,url:string}|null
     */
    public static function alternative(?string $ownToken, array $candidates): ?array
    {
        foreach ($candidates as $c) {
            if (($c['token'] ?? null) !== $ownToken && ($c['files'] ?? 0) > 0 && empty($c['expired'])) {
                return ['code' => $c['token'], 'files' => (int) $c['files'], 'url' => (self::baseUrl() ?? '').'/s/'.$c['token']];
            }
        }

        return null;
    }

    /**
     * Cari sesi galeri lewat NOMOR TRANSAKSI (mis. unggah manual yang terlanjur memakai kode link lain).
     *
     * @param  array<int,string>  $orders
     * @return array<string,array<int,array{token:string,files:int,pending:int,expired:bool}>>
     */
    public static function fetchByOrders(array $orders): array
    {
        $base = self::baseUrl();
        $orders = array_values(array_unique(array_filter($orders)));
        if (! $base || ! $orders) {
            return [];
        }

        return Cache::remember('gallery-orders:'.md5(implode(',', $orders)), 10, function () use ($base, $orders) {
            try {
                $r = Http::timeout(4)->acceptJson()->post($base.'/api/v1/status', ['tokens' => [], 'orders' => $orders]);

                return $r->ok() ? ($r->json('orders') ?? []) : [];
            } catch (\Throwable) {
                return [];
            }
        });
    }

    /** Nomor WhatsApp Indonesia (tanpa +). Nomor di database bisa kehilangan 0 di depan (kolom angka). */
    public static function waNumber(null|int|string $phone): ?string
    {
        $d = preg_replace('/\D+/', '', (string) $phone);
        if ($d === '' || $d === null) {
            return null;
        }
        if (str_starts_with($d, '62')) {
            // sudah kode negara
        } elseif (str_starts_with($d, '0')) {
            $d = '62'.substr($d, 1);
        } else {
            $d = '62'.$d;
        }

        return strlen($d) >= 10 && strlen($d) <= 15 ? $d : null;
    }
}
