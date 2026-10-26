<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menambahkan kolom untuk mencatat "Pengeluaran" saat tutup kasir (mis. selisih
 * kembalian atau belanja galon yang diambil dari laci kas) beserta catatannya,
 * dan kolom untuk melacak status pengiriman email laporan tutup kasir supaya
 * laporan bisa dikirim otomatis saat closing atau manual di lain waktu.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->unsignedBigInteger('expense_total')->default(0)->after('close_notes');
            $table->text('expense_notes')->nullable()->after('expense_total');
            $table->timestamp('report_email_sent_at')->nullable()->after('expense_notes');
            $table->text('report_email_sent_to')->nullable()->after('report_email_sent_at');
            $table->foreignId('report_email_sent_by')->nullable()->after('report_email_sent_to')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('cashier_shifts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('report_email_sent_by');
            $table->dropColumn([
                'expense_total',
                'expense_notes',
                'report_email_sent_at',
                'report_email_sent_to',
            ]);
        });
    }
};
