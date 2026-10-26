<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * payment_method_restriction: NULL = tidak dibatasi (default, semua
     * metode bayar boleh), 'cashless_only' = tolak kalau kasir pilih cash,
     * 'cash_only' = tolak kalau kasir pilih metode selain cash.
     */
    public function up(): void
    {
        Schema::table('promos', function (Blueprint $table) {
            $table->string('payment_method_restriction', 20)
                ->nullable()
                ->default(null)
                ->after('customer_scope');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promos', function (Blueprint $table) {
            $table->dropColumn('payment_method_restriction');
        });
    }
};
