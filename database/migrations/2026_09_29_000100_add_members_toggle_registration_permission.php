<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Permission baru: members-toggle-registration
 *
 * Mengatur siapa yang boleh menyalakan / mematikan opsi "Registrasi Member"
 * di form Tambah Pelanggan pada halaman kasir (switch di menu Member).
 * Dibuat lewat migration supaya langsung ada di database yang sudah berjalan
 * tanpa harus menjalankan ulang seeder. Super-admin otomatis dapat izin ini;
 * role lain bisa diberi lewat menu Roles & Permissions.
 */
return new class extends Migration
{
    private const PERMISSION = 'members-toggle-registration';

    public function up(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permission = Permission::firstOrCreate([
            'name' => self::PERMISSION,
            'guard_name' => 'web',
        ]);

        $superAdmin = Role::where('name', 'super-admin')->where('guard_name', 'web')->first();
        if ($superAdmin && ! $superAdmin->hasPermissionTo($permission)) {
            $superAdmin->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (! Schema::hasTable('permissions')) {
            return;
        }

        Permission::where('name', self::PERMISSION)->where('guard_name', 'web')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
