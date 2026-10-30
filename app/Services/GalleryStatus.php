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
