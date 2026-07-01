<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Database kamu (hasil import SQL lama) masih punya baris permission untuk
 * fitur "Pricing Rules" dan "Customer Vouchers" yang sudah digantikan total
 * oleh sistem "Promo" (tabel promos). Migration sebelumnya sudah men-drop
 * tabel pricing_rules & customer_vouchers, tapi baris di tabel `permissions`
 * (beserta turunannya di model_has_permissions & role_has_permissions) belum
 * ikut dibersihkan. Migration ini membersihkan sisa-sisa itu supaya halaman
 * Roles & Permissions tidak menampilkan permission yang sudah tidak relevan.
 */
return new class extends Migration
{
    private array $legacyPermissionPatterns = [
        'pricing-rules-%',
        'customer-vouchers-%',
    ];

    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        $legacyIds = collect($this->legacyPermissionPatterns)
            ->flatMap(function (string $pattern) {
                return DB::table('permissions')
                    ->where('name', 'like', $pattern)
                    ->pluck('id');
            })
            ->unique()
            ->values();

        if ($legacyIds->isEmpty()) {
            return;
        }

        if (Schema::hasTable('role_has_permissions')) {
            DB::table('role_has_permissions')->whereIn('permission_id', $legacyIds)->delete();
        }

        if (Schema::hasTable('model_has_permissions')) {
            DB::table('model_has_permissions')->whereIn('permission_id', $legacyIds)->delete();
        }

        DB::table('permissions')->whereIn('id', $legacyIds)->delete();
    }

    public function down(): void
    {
        // Tidak ada rollback — permission lama untuk Pricing Rules /
        // Customer Vouchers tidak relevan lagi karena fiturnya sudah
        // digantikan permanen oleh sistem Promo.
    }
};
