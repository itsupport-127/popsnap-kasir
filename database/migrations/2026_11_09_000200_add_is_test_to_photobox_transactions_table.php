<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Transaksi uji dari booth (mode simulasi / gateway sandbox): tampil di riwayat dengan label TEST, tidak dihitung sebagai pendapatan. */
    public function up(): void
    {
        if (! Schema::hasColumn('photobox_transactions', 'is_test')) {
            Schema::table('photobox_transactions', function (Blueprint $table) {
                $table->boolean('is_test')->default(false)->after('status');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('photobox_transactions', 'is_test')) {
            Schema::table('photobox_transactions', function (Blueprint $table) {
                $table->dropColumn('is_test');
            });
        }
    }
};
