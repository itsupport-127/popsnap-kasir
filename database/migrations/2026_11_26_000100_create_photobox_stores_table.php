<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * "Toko" = pengelompokan studio photobox (mis. toko POPSNAP berisi 3 studio, toko CCM berisi 2 studio).
 *
 * - photobox_studios.store_id: toko tempat studio berada. NULL = belum dikelompokkan ("Tanpa toko").
 *   Hapus toko -> studionya jadi "Tanpa toko" (nullOnDelete), transaksi tidak tersentuh.
 * - cashier_shifts.photobox_store_id: shift memegang SEMUA studio di toko itu (termasuk studio yang ditambah belakangan).
 *   SENGAJA tanpa foreign key: kalau toko terhapus, shift yang masih terbuka tidak boleh diam-diam melebar jadi "semua studio".
 *   (Penghapusan toko juga ditolak selama ada shift terbuka yang memegangnya.)
 * Shift lama (photobox_store_id NULL dan tanpa baris studio) tetap berarti "semua studio".
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('photobox_stores')) {
            Schema::create('photobox_stores', function (Blueprint $table) {
                $table->id();
                $table->string('name', 100)->unique();
                $table->timestamps();
            });
        }

        if (! Schema::hasColumn('photobox_studios', 'store_id')) {
            Schema::table('photobox_studios', function (Blueprint $table) {
                $table->foreignId('store_id')->nullable()->after('name')->constrained('photobox_stores')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('cashier_shifts', 'photobox_store_id')) {
            Schema::table('cashier_shifts', function (Blueprint $table) {
                $table->unsignedBigInteger('photobox_store_id')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('cashier_shifts', 'photobox_store_id')) {
            Schema::table('cashier_shifts', function (Blueprint $table) {
                $table->dropIndex(['photobox_store_id']);
                $table->dropColumn('photobox_store_id');
            });
        }
        if (Schema::hasColumn('photobox_studios', 'store_id')) {
            Schema::table('photobox_studios', function (Blueprint $table) {
                $table->dropConstrainedForeignId('store_id');
            });
        }
        Schema::dropIfExists('photobox_stores');
    }
};
