<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_vouchers', function (Blueprint $table) {
            // null = voucher personal (assign ke customer_id tertentu)
            // 'all' = voucher umum untuk semua pelanggan
            // 'member_only' = voucher umum khusus member
            // 'non_member_only' = voucher umum khusus non-member
            $table->string('voucher_type', 20)->default('personal')->after('customer_id');
            // customer_id boleh null untuk voucher umum
            $table->foreignId('customer_id')->nullable()->change();
            // Berapa kali voucher umum bisa dipakai (null = tidak terbatas)
            $table->unsignedInteger('max_uses')->nullable()->after('voucher_type');
            $table->unsignedInteger('used_count')->default(0)->after('max_uses');
        });
    }

    public function down(): void
    {
        Schema::table('customer_vouchers', function (Blueprint $table) {
            $table->dropColumn(['voucher_type', 'max_uses', 'used_count']);
            $table->foreignId('customer_id')->nullable(false)->change();
        });
    }
};
