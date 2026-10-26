<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->timestamp('report_email_sent_at')->nullable()->after('finalized_at');
            $table->text('report_email_sent_to')->nullable()->after('report_email_sent_at');
            $table->foreignId('report_email_sent_by')->nullable()->after('report_email_sent_to')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('stock_opnames', function (Blueprint $table) {
            $table->dropConstrainedForeignId('report_email_sent_by');
            $table->dropColumn(['report_email_sent_at', 'report_email_sent_to']);
        });
    }
};
