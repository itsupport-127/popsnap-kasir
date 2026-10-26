<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * is_sellable: true (default) = produk ini boleh dijual/ditambahkan ke
     * keranjang di kasir, seperti biasa. false = produk internal doang
     * (bahan/aksesoris buat dirakit ke produk lain, atau stok cadangan) —
     * tetap dilacak stoknya & bisa masuk Stock Opname, tapi disembunyikan
     * dari pencarian produk di halaman kasir supaya nggak nyampur sama
     * barang yang beneran dijual.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_sellable')->default(true)->after('stock');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('is_sellable');
        });
    }
};
