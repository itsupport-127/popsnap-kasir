<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sebelum ini, `transaction_details.discount_total` cuma 1 angka gabungan
 * (promo otomatis + voucher + diskon manual per item), jadi struk/riwayat
 * nggak bisa nampilin breakdown-nya masing2 secara akurat — cuma bisa
 * nampilin total gabungan tanpa tau asalnya dari mana.
 *
 * Migration ini nambahin 2 kolom baru supaya breakdown-nya bisa dipisah:
 * - transaction_details.voucher_discount       -> porsi dari voucher kode
 * - transaction_details.item_manual_discount   -> porsi dari diskon manual per item
 * - transactions.item_manual_discount_total     -> total diskon manual per item se-transaksi
 *   (mirip `customer_voucher_discount` & `loyalty_discount_total` yang sudah ada)
 *
 * `transaction_details.discount_total` TETAP dipertahankan sebagai TOTAL
 * gabungan (dipakai di banyak tempat lain), tapi sekarang porsi promo
 * otomatis murni bisa dihitung sebagai:
 *   discount_total - voucher_discount - item_manual_discount
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_details', function (Blueprint $table) {
            $table->bigInteger('voucher_discount')->default(0)->after('discount_total');
            $table->bigInteger('item_manual_discount')->default(0)->after('voucher_discount');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('item_manual_discount_total')->default(0)->after('customer_voucher_discount');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_details', function (Blueprint $table) {
            $table->dropColumn(['voucher_discount', 'item_manual_discount']);
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('item_manual_discount_total');
        });
    }
};
