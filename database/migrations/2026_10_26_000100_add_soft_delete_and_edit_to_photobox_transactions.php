<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Edit & hapus transaksi Photobox dari kasir.
 * - deleted_at : hapus = soft delete. Barisnya tetap ada (order_no tetap "terpakai"), jadi kalau app booth
 *                mengirim ulang order_no yang sama, data TIDAK muncul lagi dan pendapatan tidak terhitung dobel.
 *                Semua ringkasan/dashboard otomatis mengabaikan baris yang sudah dihapus.
 * - edited_at  : penanda transaksi pernah diubah manual di kasir. Sync dari booth tidak akan menimpanya.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Aman dijalankan ulang: tiap kolom hanya ditambah kalau belum ada.
        Schema::table('photobox_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('photobox_transactions', 'edited_at')) {
                $table->timestamp('edited_at')->nullable()->after('meta');
            }
            if (! Schema::hasColumn('photobox_transactions', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    public function down(): void
    {
        Schema::table('photobox_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('photobox_transactions', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
            if (Schema::hasColumn('photobox_transactions', 'edited_at')) {
                $table->dropColumn('edited_at');
            }
        });
    }
};
