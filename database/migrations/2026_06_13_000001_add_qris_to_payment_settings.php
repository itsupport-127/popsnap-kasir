<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_settings', function (Blueprint $table) {
            // QRIS Statis
            $table->boolean('qris_enabled')->default(false)->after('bank_transfer_enabled');
            $table->string('qris_image_path')->nullable()->after('qris_enabled');
            $table->string('qris_merchant_name')->nullable()->after('qris_image_path');
            $table->string('qris_nmid')->nullable()->after('qris_merchant_name');

            // DOKU
            $table->boolean('doku_enabled')->default(false)->after('qris_nmid');
            $table->string('doku_client_id')->nullable()->after('doku_enabled');
            $table->string('doku_secret_key')->nullable()->after('doku_client_id'); // akan di-encrypt
            $table->boolean('doku_production')->default(false)->after('doku_secret_key');

            // Flip.id
            $table->boolean('flip_enabled')->default(false)->after('doku_production');
            $table->string('flip_secret_key')->nullable()->after('flip_enabled'); // akan di-encrypt
            $table->string('flip_validation_token')->nullable()->after('flip_secret_key');
            $table->boolean('flip_production')->default(false)->after('flip_validation_token');
        });
    }

    public function down(): void
    {
        Schema::table('payment_settings', function (Blueprint $table) {
            $table->dropColumn([
                'qris_enabled', 'qris_image_path', 'qris_merchant_name', 'qris_nmid',
                'doku_enabled', 'doku_client_id', 'doku_secret_key', 'doku_production',
                'flip_enabled', 'flip_secret_key', 'flip_validation_token', 'flip_production',
            ]);
        });
    }
};
