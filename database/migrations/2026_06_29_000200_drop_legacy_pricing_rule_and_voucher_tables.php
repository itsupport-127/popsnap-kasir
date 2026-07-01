<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('pricing_rule_buy_get_items');
        Schema::dropIfExists('pricing_rule_bundle_items');
        Schema::dropIfExists('pricing_rule_qty_breaks');
        Schema::dropIfExists('pricing_rules');

        Schema::dropIfExists('customer_voucher_qty_breaks');
        Schema::dropIfExists('customer_vouchers');
    }

    public function down(): void
    {
        // Tidak ada rollback — tabel lama digantikan permanen oleh `promos`.
        // Jalankan migration sebelum 2026_06_29_000100 dari awal jika perlu
        // mengembalikan struktur lama.
    }
};
