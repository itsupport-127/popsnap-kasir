<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * usage_limit_mode: NULL/'per_transaction' (default) = kode boleh
     * dipakai berkali-kali oleh pelanggan yang sama, di transaksi yang
     * beda-beda (cuma dibatasi wajar: gak bisa dipakai 2x dalam 1
     * transaksi yang sama). 'per_customer' = begitu 1 pelanggan pernah
     * berhasil pakai kode ini SEKALI (di transaksi manapun, kapanpun),
     * pelanggan itu gak bisa pakai kode yang sama lagi selamanya —
     * mencegah 1 orang klaim kode promo berkali-kali.
     */
    public function up(): void
    {
        Schema::table('promos', function (Blueprint $table) {
            $table->string('usage_limit_mode', 20)
                ->nullable()
                ->default(null)
                ->after('allow_zero_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promos', function (Blueprint $table) {
            $table->dropColumn('usage_limit_mode');
        });
    }
};
