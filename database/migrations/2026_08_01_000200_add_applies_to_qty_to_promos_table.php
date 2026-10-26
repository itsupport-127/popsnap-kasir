<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * applies_to_qty: batas maksimal jumlah item yang kena diskon ini dalam
     * satu transaksi. NULL/0 = tidak dibatasi, berlaku untuk semua qty item
     * yang cocok target-nya (perilaku lama). Diisi angka (misal 1) = diskon
     * cuma berlaku untuk 1 item itu, sisanya (kalau ada item sejenis lain di
     * keranjang) tetap harga normal. Berlaku untuk promo Diskon Nominal/Persen
     * (standard_discount), baik otomatis maupun kode manual.
     */
    public function up(): void
    {
        Schema::table('promos', function (Blueprint $table) {
            $table->unsignedInteger('applies_to_qty')
                ->nullable()
                ->default(null)
                ->after('discount_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promos', function (Blueprint $table) {
            $table->dropColumn('applies_to_qty');
        });
    }
};
