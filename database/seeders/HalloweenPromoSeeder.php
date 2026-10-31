<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Promo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Voucher Halloween: "Special Halloween 20K — 1 Paket Photobox All Studio".
 *
 *   php artisan db:seed --class=HalloweenPromoSeeder
 *
 * - Kode        : HALLOWEEN20K (diketik kasir di kolom voucher saat transaksi)
 * - Harga       : 1 paket Photobox jadi Rp 20.000 (harga tetap per paket, bukan potongan persen)
 * - Berlaku     : semua produk kategori "PhotoBox" = semua studio. Kategori "Paket PhotoBox" TIDAK ikut.
 * - Periode     : 30 Okt 2026 00:00 s/d 1 Nov 2026 23:59:59 WIB, di luar itu kode otomatis ditolak
 * - Jatah       : 1 paket per pemakaian kode (applies_to_qty = 1). Satu kode boleh dipakai banyak pengunjung.
 * - Verifikasi  : MANUAL. Kasir cek kostum Halloween dulu, baru memasukkan kode. Kode tidak aman kalau disebar
 *                 ke umum, jadi jangan dicantumkan di postingan promo.
 *
 * Aman dijalankan ulang (updateOrCreate by kode). Kategori dicari lewat nama; kalau namanya beda di database
 * kamu, jalankan dengan: HALLOWEEN_PROMO_CATEGORY="Nama Kategori" php artisan db:seed --class=HalloweenPromoSeeder
 */
class HalloweenPromoSeeder extends Seeder
{
    public const CODE = 'HALLOWEEN20K';

    public function run(): void
    {
        $categoryName = env('HALLOWEEN_PROMO_CATEGORY', 'PhotoBox');
        $category = Category::whereRaw('LOWER(name) = ?', [mb_strtolower($categoryName)])->first();

        if (! $category) {
            $this->command?->error("Kategori \"{$categoryName}\" tidak ditemukan. Kategori yang ada: ".Category::pluck('name')->implode(', '));
            $this->command?->line('Jalankan ulang dengan HALLOWEEN_PROMO_CATEGORY="<nama kategori photobox>" php artisan db:seed --class=HalloweenPromoSeeder');

            return;
        }

        $tz = config('app.timezone');
        $startsAt = Carbon::create(2026, 10, 30, 0, 0, 0, 'Asia/Jakarta')->setTimezone($tz);
        $endsAt = Carbon::create(2026, 11, 1, 23, 59, 59, 'Asia/Jakarta')->setTimezone($tz);

        $promo = Promo::updateOrCreate(['code' => self::CODE], [
            'name' => 'Special Halloween 20K — 1 Paket Photobox All Studio',
            'notes' => "Khusus pengunjung berkostum Halloween (kasir cek kostum sebelum memasukkan kode).\n"
                ."Periode promo & kompetisi kostum: 30 Oktober–1 November 2026. Pemenang diumumkan 2 November lewat Instagram Story Popsnap.",
            'is_active' => true,
            'priority' => 0,
            'activation_mode' => Promo::ACTIVATION_MANUAL,
            'discount_kind' => Promo::KIND_STANDARD_DISCOUNT,
            'discount_type' => Promo::TYPE_FIXED_PRICE,
            'discount_value' => 20000,
            'discount_scope' => Promo::DISCOUNT_SCOPE_PER_ITEM,
            'applies_to_qty' => 1,
            'allow_zero_price' => false,
            'usage_limit_mode' => Promo::USAGE_LIMIT_PER_TRANSACTION,
            'target_type' => Promo::TARGET_CATEGORY,
            'product_id' => null,
            'category_id' => $category->id,
            'customer_scope' => Promo::SCOPE_ALL,
            'payment_method_restriction' => null,
            'customer_id' => null,
            'max_uses' => null,
            'minimum_order' => 0,
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
        ]);

        $this->command?->info(sprintf(
            'Voucher %s siap: %s → Rp 20.000 untuk kategori "%s", berlaku %s s/d %s.',
            $promo->code,
            $promo->name,
            $category->name,
            $startsAt->timezone('Asia/Jakarta')->format('d M Y H:i'),
            $endsAt->timezone('Asia/Jakarta')->format('d M Y H:i')
        ));
    }
}
