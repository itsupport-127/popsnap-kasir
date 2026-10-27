<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Link galeri (halaman QR foto/video) tiap sesi, dikirim app Photobox bersama transaksinya.
 * Di kasir dipakai untuk tombol buka / salin / kirim WhatsApp.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Aman dijalankan ulang: kalau kolom sudah ada (mis. dari percobaan migrate sebelumnya), dilewati.
        if (Schema::hasColumn('photobox_transactions', 'gallery_url')) {
            return;
        }

        Schema::table('photobox_transactions', function (Blueprint $table) {
            $table->string('gallery_url', 500)->nullable()->after('session_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('photobox_transactions', 'gallery_url')) {
            return;
        }

        Schema::table('photobox_transactions', function (Blueprint $table) {
            $table->dropColumn('gallery_url');
        });
    }
};
