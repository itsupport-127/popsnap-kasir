<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat perintah print dari HP -> PC kasir (halaman "Print Hari Ini").
 * Hanya menyimpan data hari ini: baris hari sebelumnya dihapus otomatis (lihat PrintJob::pruneOld()).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('print_jobs')) {
            return;
        }

        Schema::create('print_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('invoice', 64);
            $table->string('size', 4)->default('58');
            $table->string('requested_by', 120)->nullable();
            // queued = menunggu PC yang siap | printing = sedang dikirim ke printer | printed = selesai | failed = gagal
            $table->string('status', 16)->default('queued');
            $table->text('error')->nullable();
            $table->string('printed_by', 120)->nullable(); // PC / user yang mengeksekusi
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['created_at', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('print_jobs');
    }
};
