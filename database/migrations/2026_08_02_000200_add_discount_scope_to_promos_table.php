<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * discount_scope: cakupan diskon buat promo/voucher standard_discount
 * (persen/nominal) — dua mode:
 *
 * - 'per_item'         : diskon nempel ke barang SATU-SATU (pakai
 *                        applies_to_qty), bisa "saling koordinasi" antar
 *                        kemenangan/kode (Case 1 & 2 yang sudah dibangun).
 * - 'total_transaction': diskon dihitung sekaligus dari SISA subtotal
 *                        barang yang eligible (sesuai target_type), 1x per
 *                        pemakaian kode — bukan per barang satu-satu.
 *
 * Default 'per_item' supaya promo yang sudah ada (termasuk 6 kode Panjat
 * Promo) perilakunya TETAP SAMA seperti sebelum field ini ada.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promos', function (Blueprint $table) {
            $table->string('discount_scope', 20)
                ->default('per_item')
                ->after('applies_to_qty');
        });

        // PENTING — backfill supaya promo yang SUDAH ADA nggak diam2 berubah
        // perilakunya: sebelum field ini ada, promo standard_discount dengan
        // applies_to_qty KOSONG (null) itu artinya "diskon sekaligus dari
        // seluruh subtotal barang yang eligible" (persis semantik
        // 'total_transaction'). Kalau kita biarkan semuanya default
        // 'per_item', promo semacam itu tiba2 kepotong per-1-barang aja —
        // regresi diam2. Jadi:
        // - applies_to_qty NULL  -> discount_scope = 'total_transaction'
        // - applies_to_qty diisi -> discount_scope = 'per_item' (default, no-op)
        DB::table('promos')
            ->where('discount_kind', 'standard_discount')
            ->whereNull('applies_to_qty')
            ->update(['discount_scope' => 'total_transaction']);
    }

    public function down(): void
    {
        Schema::table('promos', function (Blueprint $table) {
            $table->dropColumn('discount_scope');
        });
    }
};
