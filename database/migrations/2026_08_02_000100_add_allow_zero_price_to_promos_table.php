<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * allow_zero_price: mode toggle buat voucher persen/nominal.
     * - false (default, "jangan sampai gratis") = kalau beberapa voucher
     *   numpuk di 1 barang yang sama sampai hasilnya kurang dari Rp1,
     *   sistem otomatis nahan diskonnya supaya barang itu tetap ada
     *   harganya (minimal Rp1), nggak pernah kejual Rp0.
     * - true ("boleh sampai gratis") = admin sengaja izinkan diskon
     *   nempel sampai barang itu bisa jadi Rp0 (misal buat promo
     *   "gratis 1 barang" yang memang disengaja).
     */
    public function up(): void
    {
        Schema::table('promos', function (Blueprint $table) {
            $table->boolean('allow_zero_price')
                ->default(false)
                ->after('applies_to_qty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promos', function (Blueprint $table) {
            $table->dropColumn('allow_zero_price');
        });
    }
};
