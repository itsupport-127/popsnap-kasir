<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * opening_stock ("Stock Awal"): otomatis diisi dari system_stock milik
     * sesi Stock Opname SEBELUMNYA (buat produk yang sama), biar tiap
     * periode nyambung otomatis tanpa perlu diketik manual. NULL kalau
     * produk itu baru pertama kali masuk Stock Opname (belum ada histori).
     */
    public function up(): void
    {
        Schema::table('stock_opname_items', function (Blueprint $table) {
            $table->integer('opening_stock')->nullable()->after('stock_opname_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_opname_items', function (Blueprint $table) {
            $table->dropColumn('opening_stock');
        });
    }
};
