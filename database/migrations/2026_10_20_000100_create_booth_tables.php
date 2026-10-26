<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // owner_id disiapkan dari awal untuk multi-pemilik di masa depan (sekarang boleh null).
        Schema::create('booth_outlets', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('owner_id')->nullable()->index();
            $t->string('name');
            $t->string('wa_template')->nullable();
            $t->timestamps();
        });

        Schema::create('booth_devices', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('owner_id')->nullable()->index();
            $t->foreignId('outlet_id')->constrained('booth_outlets')->cascadeOnDelete();
            $t->string('name');
            $t->string('prefix', 6)->unique();      // awalan kode voucher buatan booth
            $t->string('token_hash', 64)->unique(); // sha256 dari token
            $t->boolean('is_active')->default(true);
            $t->timestamp('last_seen_at')->nullable();
            $t->string('last_ip', 64)->nullable();
            $t->timestamps();
        });

        Schema::create('booth_payments', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('owner_id')->nullable()->index();
            $t->foreignId('outlet_id')->constrained('booth_outlets')->cascadeOnDelete();
            $t->foreignId('device_id')->constrained('booth_devices')->cascadeOnDelete();
            $t->string('client_ref', 64);           // id unik dari booth (idempotensi)
            $t->unsignedBigInteger('transaction_id')->nullable()->index();
            $t->unsignedBigInteger('amount');
            $t->string('status', 20)->default('pending'); // pending|paid|failed|expired
            $t->text('qr_string')->nullable();
            $t->text('qr_url')->nullable();
            $t->string('gateway', 20)->default('midtrans');
            $t->string('gateway_ref')->nullable();
            $t->timestamp('expires_at')->nullable();
            $t->timestamp('paid_at')->nullable();
            $t->timestamps();
            $t->unique(['device_id', 'client_ref']);
        });

        Schema::create('booth_vouchers', function (Blueprint $t) {
            $t->id();
            $t->unsignedBigInteger('owner_id')->nullable()->index();
            $t->foreignId('outlet_id')->nullable()->constrained('booth_outlets')->nullOnDelete();
            $t->foreignId('device_id')->nullable()->constrained('booth_devices')->nullOnDelete();
            $t->string('code', 40)->unique();
            $t->string('source', 10)->default('server'); // server|booth
            $t->unsignedInteger('max_uses')->default(1);
            $t->unsignedInteger('used_count')->default(0);
            $t->timestamp('expires_at')->nullable();
            $t->boolean('is_active')->default(true);
            $t->string('note')->nullable();
            $t->timestamps();
        });

        Schema::create('booth_sessions', function (Blueprint $t) {
            $t->id();
            $t->uuid('uuid')->unique();
            $t->unsignedBigInteger('owner_id')->nullable()->index();
            $t->foreignId('outlet_id')->constrained('booth_outlets')->cascadeOnDelete();
            $t->foreignId('device_id')->nullable()->constrained('booth_devices')->nullOnDelete();
            $t->foreignId('payment_id')->nullable()->constrained('booth_payments')->nullOnDelete();
            $t->string('voucher_code', 40)->nullable();
            $t->string('template_name')->nullable();
            $t->unsignedInteger('photos_count')->default(0);
            $t->timestamp('started_at')->nullable();
            $t->timestamp('finished_at')->nullable();
            $t->string('share_token', 40)->unique();
            $t->string('customer_phone', 32)->nullable();
            $t->string('wa_status', 12)->default('none');   // none|queued|sent
            $t->boolean('was_offline')->default(false);
            $t->string('media_status', 12)->default('pending'); // pending|partial|complete
            $t->unsignedInteger('media_expected')->default(0);
            $t->json('meta')->nullable();
            $t->timestamps();
            $t->index(['outlet_id', 'created_at']);
        });

        Schema::create('booth_media', function (Blueprint $t) {
            $t->id();
            $t->foreignId('session_id')->constrained('booth_sessions')->cascadeOnDelete();
            $t->string('kind', 20);          // raw|result|gif|video|clip|other
            $t->string('name');
            $t->string('path');
            $t->unsignedBigInteger('size')->default(0);
            $t->string('mime', 80)->nullable();
            $t->timestamp('deleted_at')->nullable();
            $t->timestamps();
            $t->unique(['session_id', 'path']);
        });
    }

    public function down(): void
    {
        foreach (['booth_media', 'booth_sessions', 'booth_vouchers', 'booth_payments', 'booth_devices', 'booth_outlets'] as $t) {
            Schema::dropIfExists($t);
        }
    }
};
