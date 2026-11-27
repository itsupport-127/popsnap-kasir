<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\HomeRoute;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class HomeRouteTest extends TestCase
{
    use RefreshDatabase;

    private function userWith(array $permissions): User
    {
        foreach ($permissions as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    public function test_home_route_goes_to_first_page_the_user_may_open_not_always_dashboard(): void
    {
        $cashier = $this->userWith(['transactions-access']);
        $this->assertSame('transactions.index', HomeRoute::routeName($cashier)); // kasir tanpa dashboard-access tidak dilempar ke 403

        $reportOnly = $this->userWith(['reports-access']);
        $this->assertSame('reports.sales.index', HomeRoute::routeName($reportOnly));

        $dashboardOnly = $this->userWith(['dashboard-access']);
        $this->assertSame('dashboard', HomeRoute::routeName($dashboardOnly));

        $nothing = $this->userWith([]);
        $this->assertSame('dashboard.access', HomeRoute::routeName($nothing));
        $this->assertSame('dashboard.access', HomeRoute::routeName(null));
    }
}
