<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Studio (booth photobox) yang dipegang sebuah shift kasir.
 *
 * - Tidak ada baris untuk sebuah shift  = shift memegang SEMUA studio (perilaku lama; shift lama tetap begitu).
 * - Ada baris = transaksi photobox hanya dikaitkan ke shift ini kalau studio-nya termasuk di sini.
 * - photobox_studio_id sengaja TANPA foreign key: kalau sebuah studio dihapus, shift yang sudah dibatasi tidak boleh
 *   diam-diam berubah jadi "semua studio" (baris yatim tetap menandai shift ini terbatas).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('cashier_shift_studios')) {
            return;
        }

        Schema::create('cashier_shift_studios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cashier_shift_id')->constrained('cashier_shifts')->cascadeOnDelete();
            $table->unsignedBigInteger('photobox_studio_id');
            $table->timestamps();

            $table->unique(['cashier_shift_id', 'photobox_studio_id'], 'css_shift_studio_unique');
            $table->index('photobox_studio_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashier_shift_studios');
    }
};
