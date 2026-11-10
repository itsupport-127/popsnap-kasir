<?php

namespace App\Services;

use App\Models\Promo;
use Illuminate\Support\Collection;

/**
 * Diskon kasir untuk booth Photobox (bayar QRIS). Memakai promo yang sama dengan menu Promo/Voucher di kasir:
 *  - promo OTOMATIS: dipilih yang potongannya paling besar (satu saja, supaya tidak menumpuk liar);
 *  - kode MANUAL: diketik tamu, dihitung dari harga setelah promo otomatis (sama seperti kasir).
 *
 * Batasan (disengaja, supaya hasilnya bisa dipastikan benar): hanya jenis "Diskon biasa" (persen / nominal) yang berlaku untuk SEMUA produk.
 * Promo yang dibatasi produk/kategori, grosir, bundle, beli-X-gratis-Y, atau khusus pelanggan terdaftar/member dilewati, karena booth tidak
 * mengenal produk kasir dan tamu booth tidak punya identitas pelanggan.
 */
class PhotoboxDiscountService
{
    /** Harga akhir QRIS tidak boleh di bawah ini (batas minimum QRIS). Selisihnya yang dipotong, bukan ditolak. */
    public const MIN_FINAL = 1000;

    public function __construct(private PromoService $promos) {}

    private function usable(Promo $p, int $amount): bool
    {
        return $p->discount_kind === Promo::KIND_STANDARD_DISCOUNT
            && $p->target_type === Promo::TARGET_ALL
            && ! $p->isPersonal()
            && $p->matchesCustomerScope(null)
            && $p->matchesPaymentMethod('qris')
            && $p->usageAvailable()
            && $amount >= (int) $p->minimum_order
            && (float) $p->discount_value > 0;
    }

    private function cut(Promo $p, int $amount): int
    {
        $v = (float) $p->discount_value;
        $d = $p->discount_type === Promo::TYPE_PERCENTAGE ? (int) round($amount * min(100, $v) / 100) : (int) round($v);

        return max(0, min($d, $amount));
    }

    /** @return Collection<int, Promo> promo aktif (sudah disaring tanggal & aktif oleh PromoService) */
    private function active(): Collection
    {
        return $this->promos->getActivePromos();
    }

    private function bestAuto(int $amount): ?array
    {
        $best = null;
        foreach ($this->active() as $p) {
            if (! $p->isAuto() || ! $this->usable($p, $amount)) {
                continue;
            }
            $d = $this->cut($p, $amount);
            if ($d > 0 && (! $best || $d > $best['discount'])) {
                $best = ['id' => $p->id, 'name' => (string) $p->name, 'discount' => $d];
            }
        }

        return $best;
    }

    /**
     * @return array{ok:bool, error?:string, gross:int, discount:int, final:int, capped:bool,
     *               auto:?array{id:int,name:string,discount:int}, code:?array{id:int,code:string,name:string,discount:int}}
     */
    public function quote(int $gross, ?string $code = null): array
    {
        $gross = max(0, $gross);
        $out = ['ok' => true, 'gross' => $gross, 'discount' => 0, 'final' => $gross, 'capped' => false, 'auto' => null, 'code' => null];
        if ($gross <= self::MIN_FINAL) {
            return $code ? array_merge($out, ['ok' => false, 'error' => 'Harga terlalu kecil untuk diberi diskon.']) : $out;
        }

        $auto = $this->bestAuto($gross);
        $afterAuto = $gross - ($auto['discount'] ?? 0);

        $codeInfo = null;
        $code = strtoupper(trim((string) $code));
        if ($code !== '') {
            $promo = $this->active()->first(fn (Promo $p) => $p->isManual() && strtoupper((string) $p->code) === $code);
            if (! $promo) {
                return array_merge($out, ['ok' => false, 'error' => 'Kode diskon tidak ditemukan atau sudah tidak berlaku.']);
            }
            if (! $promo->usageAvailable()) {
                return array_merge($out, ['ok' => false, 'error' => 'Kuota kode diskon ini sudah habis.']);
            }
            if (! $this->usable($promo, $afterAuto)) {
                return array_merge($out, ['ok' => false, 'error' => 'Kode ini tidak bisa dipakai di photobox (atau belum memenuhi minimum belanja).']);
            }
            $d = $this->cut($promo, $afterAuto);
            if ($d <= 0) {
                return array_merge($out, ['ok' => false, 'error' => 'Kode ini tidak memberi potongan.']);
            }
            $codeInfo = ['id' => $promo->id, 'code' => strtoupper((string) $promo->code), 'name' => (string) $promo->name, 'discount' => $d];
        }

        $total = ($auto['discount'] ?? 0) + ($codeInfo['discount'] ?? 0);
        $maxCut = $gross - self::MIN_FINAL;
        if ($total > $maxCut) { // jangan sampai harga akhir di bawah batas QRIS: potongan terakhir dikecilkan
            $over = $total - $maxCut;
            $out['capped'] = true;
            if ($codeInfo) {
                $codeInfo['discount'] = max(0, $codeInfo['discount'] - $over);
            } elseif ($auto) {
                $auto['discount'] = max(0, $auto['discount'] - $over);
            }
            $total = $maxCut;
        }

        return array_merge($out, ['auto' => $auto, 'code' => $codeInfo, 'discount' => $total, 'final' => $gross - $total]);
    }

    /**
     * Catat satu pemakaian kode (dipanggil sekali saat transaksi booth lunas). Atomik & tidak melewati kuota.
     * Mengembalikan false kalau kode tidak ada / kuota sudah habis (transaksi tetap sah, hanya hitungan tidak bertambah).
     */
    public function redeem(string $code): bool
    {
        $promo = Promo::query()->whereRaw('UPPER(code) = ?', [strtoupper(trim($code))])->where('activation_mode', Promo::ACTIVATION_MANUAL)->first();
        if (! $promo || $promo->isPersonal()) {
            return false;
        }
        $q = Promo::query()->whereKey($promo->id);
        if ($promo->max_uses !== null) {
            $q->where('used_count', '<', $promo->max_uses);
        }

        return $q->increment('used_count') > 0;
    }

    /**
     * Kembalikan satu pemakaian kode (transaksi dihapus / di-refund / batal di kasir) supaya kuota tidak habis oleh transaksi yang tidak jadi.
     * Aman dipanggil berulang: hitungan tidak pernah di bawah 0.
     */
    public function release(string $code): bool
    {
        $promo = Promo::query()->whereRaw('UPPER(code) = ?', [strtoupper(trim($code))])->where('activation_mode', Promo::ACTIVATION_MANUAL)->first();
        if (! $promo || $promo->isPersonal()) {
            return false;
        }

        return Promo::query()->whereKey($promo->id)->where('used_count', '>', 0)->decrement('used_count') > 0;
    }
}
