<?php

namespace App\Support;

use App\Models\User;

/**
 * Halaman "rumah" pertama yang BOLEH dibuka user. Dipakai setelah login dan di tombol halaman error,
 * supaya kasir tanpa izin `dashboard-access` tidak dilempar ke Dashboard lalu kena 403 berulang.
 */
class HomeRoute
{
    /** izin => nama route, urut dari prioritas tertinggi. */
    public const PRIORITY = [
        'transactions-access' => 'transactions.index',
        'receivables-access' => 'receivables.index',
        'payables-access' => 'payables.index',
        'customers-access' => 'customers.index',
        'suppliers-access' => 'suppliers.index',
        'reports-access' => 'reports.sales.index',
        'dashboard-access' => 'dashboard',
    ];

    public static function routeName(?User $user): string
    {
        if ($user) {
            foreach (self::PRIORITY as $permission => $routeName) {
                if ($user->can($permission)) {
                    return $routeName;
                }
            }
        }

        return 'dashboard.access';
    }
}
