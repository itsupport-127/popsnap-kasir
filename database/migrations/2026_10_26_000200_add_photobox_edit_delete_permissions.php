<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * photobox-edit   : mengubah transaksi Photobox (produk, metode, jumlah, status, waktu bayar)
 * photobox-delete : menghapus transaksi Photobox
 * Super-admin otomatis dapat; role lain bisa diberi lewat menu Roles & Permissions.
 */
return new class extends Migration
{
    private const PERMISSIONS = ['photobox-edit', 'photobox-delete'];

    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        foreach (self::PERMISSIONS as $name) {
            $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']);
            if ($superAdmin && ! $superAdmin->hasPermissionTo($permission)) {
                $superAdmin->givePermissionTo($permission);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }
        Permission::whereIn('name', self::PERMISSIONS)->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
