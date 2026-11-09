<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Penjualan Photobox (booth) yang masuk selama shift ikut dihitung di tutup kasir.
     * Nullable: null = shift lama yang belum pernah dihitung (dihitung langsung dari transaksi photobox saat ditampilkan / dikirim email).
     */
    public function up(): void
    {
        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->unsignedBigInteger('photobox_sales_total')->nullable()->after('non_cash_sales_total');
            $table->unsignedInteger('photobox_transactions_count')->nullable()->after('photobox_sales_total');
        });
    }

    public function down(): void
    {
        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->dropColumn(['photobox_sales_total', 'photobox_transactions_count']);
        });
    }
};
