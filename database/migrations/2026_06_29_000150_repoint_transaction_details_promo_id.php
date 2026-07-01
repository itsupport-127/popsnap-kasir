<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $table = 'transaction_details';
        $column = 'pricing_rule_id';
        $connection = Schema::getConnection()->getDatabaseName();

        // ── 1. Drop FOREIGN KEY kalau memang benar-benar ada ────────────
        // Di sebagian database lama, kolom ini cuma punya INDEX biasa
        // bernama mirip FK (mis. transaction_details_pricing_rule_id_foreign)
        // tanpa constraint FOREIGN KEY sungguhan, jadi dropForeign() bawaan
        // Laravel akan gagal. Kita cek dulu lewat information_schema.
        $foreignKeyName = DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', $connection)
            ->where('TABLE_NAME', $table)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->where('CONSTRAINT_NAME', 'like', "%{$column}%")
            ->value('CONSTRAINT_NAME');

        if ($foreignKeyName) {
            DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$foreignKeyName}`");
        }

        // ── 2. Drop INDEX lama (FK maupun index biasa) kalau masih ada ──
        // supaya tidak bentrok / duplikat index saat constraint baru
        // ditambahkan di langkah akhir.
        $indexNames = DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', $connection)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->pluck('INDEX_NAME')
            ->unique();

        foreach ($indexNames as $indexName) {
            if ($indexName === 'PRIMARY') {
                continue;
            }
            DB::statement("ALTER TABLE `{$table}` DROP INDEX `{$indexName}`");
        }

        // ── 3. Kosongkan data lama (id dari pricing_rules tidak nyambung
        //      lagi ke promos — aman, cuma riwayat tampilan nama yang
        //      tetap ada lewat kolom snapshot pricing_rule_name) ───────
        DB::table($table)->update([$column => null]);

        // ── 4. Rename kolom pricing_rule_id → promo_id ──────────────────
        Schema::table($table, function (Blueprint $blueprint) use ($column) {
            $blueprint->renameColumn($column, 'promo_id');
        });

        // ── 5. Tambah foreign key baru ke tabel promos ──────────────────
        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->foreign('promo_id')->references('id')->on('promos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('transaction_details', function (Blueprint $table) {
            $table->dropForeign(['promo_id']);
            $table->renameColumn('promo_id', 'pricing_rule_id');
        });
    }
};
