<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Integrasi Photobox: tiap studio (booth) punya token sendiri, lalu mengirim riwayat
 * transaksinya ke kasir. Nomor transaksi (order_no) DIBUAT oleh app photobox dan dipakai
 * apa adanya di kasir, jadi di kedua app nomornya sama. Pengiriman idempoten per order_no.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photobox_studios', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('token_hash', 64)->unique();   // sha256 dari token; token asli hanya tampil sekali
            $table->string('token_hint', 8)->nullable();  // 4 karakter terakhir, untuk pengenal di layar
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();
        });

        Schema::create('photobox_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('order_no', 64)->unique();      // nomor transaksi dari photobox, mis. PB-261022-0007
            $table->foreignId('studio_id')->constrained('photobox_studios')->cascadeOnDelete();
            $table->string('template_id', 64)->nullable();
            $table->string('template_name')->nullable();   // "produk" yang terjual (template/paket)
            $table->string('category')->nullable();
            $table->string('mode', 24)->nullable();        // photo | gif | boomerang | video
            $table->string('method', 24)->default('qris'); // qris | voucher | free
            $table->string('voucher_code', 40)->nullable();
            $table->unsignedBigInteger('amount')->default(0);
            $table->string('status', 16)->default('pending'); // pending | paid | failed | expired | refunded
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('booth_created_at')->nullable();
            $table->string('session_id', 64)->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['studio_id', 'status', 'paid_at']);
            $table->index(['status', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photobox_transactions');
        Schema::dropIfExists('photobox_studios');
    }
};
