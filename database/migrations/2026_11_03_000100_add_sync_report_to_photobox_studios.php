<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Laporan status terakhir dari app Photobox (heartbeat): antrean kirim, error, galeri, ruang disk.
 * Dipakai di menu Transaksi Photobox untuk melihat tiap studio lancar atau bermasalah dari jauh.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('photobox_studios', 'sync_report')) {
            return;
        }

        Schema::table('photobox_studios', function (Blueprint $table) {
            $table->text('sync_report')->nullable();
            $table->timestamp('report_at')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('photobox_studios', 'sync_report')) {
            return;
        }

        Schema::table('photobox_studios', function (Blueprint $table) {
            $table->dropColumn(['sync_report', 'report_at']);
        });
    }
};
