<?php

namespace Tests\Feature\CashierShifts;

use App\Models\CashierShift;
use App\Models\Category;
use App\Mail\CashierShiftClosingReportMail;
use App\Models\Customer;
use App\Models\PhotoboxStudio;
use App\Models\PhotoboxTransaction;
use App\Models\Product;
use App\Models\SalesReturn;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CashierShiftTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'transactions-access',
            'cashier-shifts-access',
            'cashier-shifts-open',
            'cashier-shifts-close',
            'cashier-shifts-force-close',
        ] as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }

    public function test_cashier_can_open_shift(): void
    {
        $cashier = $this->createUserWithPermissions([
            'cashier-shifts-access',
            'cashier-shifts-open',
        ]);

        $response = $this
            ->actingAs($cashier)
            ->post(route('cashier-shifts.store'), [
                'opening_cash' => 150000,
                'notes' => 'Modal pagi',
            ]);

        $shift = CashierShift::first();

        $response->assertRedirect(route('cashier-shifts.show', $shift));
        $this->assertNotNull($shift);
        $this->assertSame($cashier->id, $shift->user_id);
        $this->assertSame(150000, (int) $shift->opening_cash);
        $this->assertSame(CashierShift::STATUS_OPEN, $shift->status);
    }

    public function test_cashier_cannot_open_second_shift_while_first_is_active(): void
    {
        $cashier = $this->createUserWithPermissions([
            'cashier-shifts-access',
            'cashier-shifts-open',
        ]);

        CashierShift::create([
            'user_id' => $cashier->id,
            'opened_by' => $cashier->id,
            'opened_at' => now(),
            'opening_cash' => 100000,
            'expected_cash' => 100000,
            'status' => CashierShift::STATUS_OPEN,
        ]);

        $response = $this
            ->from(route('cashier-shifts.index'))
            ->actingAs($cashier)
            ->post(route('cashier-shifts.store'), [
                'opening_cash' => 200000,
            ]);

        $response->assertInvalid(['opening_cash']);
        $this->assertDatabaseCount('cashier_shifts', 1);
    }

    public function test_transaction_page_receives_active_shift_shared_prop(): void
    {
        $cashier = $this->createUserWithPermissions([
            'transactions-access',
            'cashier-shifts-access',
        ]);

        $shift = CashierShift::create([
            'user_id' => $cashier->id,
            'opened_by' => $cashier->id,
            'opened_at' => now(),
            'opening_cash' => 90000,
            'expected_cash' => 90000,
            'status' => CashierShift::STATUS_OPEN,
        ]);

        $response = $this
            ->actingAs($cashier)
            ->get(route('transactions.index'));

        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Transactions/Index')
            ->where('activeCashierShift.id', $shift->id)
            ->where('activeCashierShift.opening_cash', 90000));
    }

    public function test_closing_shift_calculates_expected_cash_and_difference(): void
    {
        $cashier = $this->createUserWithPermissions([
            'cashier-shifts-access',
            'cashier-shifts-close',
        ]);

        $shift = CashierShift::create([
            'user_id' => $cashier->id,
            'opened_by' => $cashier->id,
            'opened_at' => now(),
            'opening_cash' => 100000,
            'expected_cash' => 100000,
            'status' => CashierShift::STATUS_OPEN,
        ]);

        $customer = Customer::create([
            'name' => 'Customer Shift',
            'no_telp' => '0812000000',
            'address' => 'Alamat Shift',
        ]);

        $category = Category::create([
            'name' => 'Shift',
            'description' => 'Shift',
            'image' => 'shift.png',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'image' => 'shift-product.png',
            'barcode' => 'BRCD-'.Str::upper(Str::random(8)),
            'sku' => 'SKU-'.Str::upper(Str::random(8)),
            'title' => 'Produk Shift',
            'description' => 'Produk Shift',
            'buy_price' => 40000,
            'sell_price' => 60000,
            'stock' => 10,
        ]);

        Transaction::create([
            'cashier_id' => $cashier->id,
            'cashier_shift_id' => $shift->id,
            'customer_id' => $customer->id,
            'invoice' => 'TRX-'.Str::upper(Str::random(8)),
            'cash' => 60000,
            'change' => 0,
            'discount' => 0,
            'shipping_cost' => 0,
            'grand_total' => 60000,
            'payment_method' => 'cash',
            'payment_status' => 'paid',
        ])->details()->create([
            'product_id' => $product->id,
            'qty' => 1,
            'price' => 60000,
        ]);

        Transaction::create([
            'cashier_id' => $cashier->id,
            'cashier_shift_id' => $shift->id,
            'customer_id' => $customer->id,
            'invoice' => 'TRX-'.Str::upper(Str::random(8)),
            'cash' => 0,
            'change' => 0,
            'discount' => 0,
            'shipping_cost' => 0,
            'grand_total' => 50000,
            'payment_method' => 'bank_transfer',
            'payment_status' => 'pending',
        ]);

        SalesReturn::create([
            'code' => 'SR-'.Str::upper(Str::random(6)),
            'transaction_id' => Transaction::first()->id,
            'customer_id' => $customer->id,
            'cashier_id' => $cashier->id,
            'cashier_shift_id' => $shift->id,
            'status' => 'completed',
            'return_type' => 'refund_cash',
            'refund_amount' => 10000,
            'credited_amount' => 0,
            'total_return_amount' => 10000,
            'completed_at' => now(),
        ]);

        $response = $this
            ->actingAs($cashier)
            ->post(route('cashier-shifts.close', $shift), [
                'actual_cash' => 155000,
                'close_notes' => 'Cash count final',
            ]);

        $response->assertRedirect(route('cashier-shifts.show', ['cashierShift' => $shift->id, 'justClosed' => 1]));
        $this->assertDatabaseHas('cashier_shifts', [
            'id' => $shift->id,
            'status' => CashierShift::STATUS_CLOSED,
            'expected_cash' => 150000,
            'actual_cash' => 155000,
            'cash_difference' => 5000,
            'cash_sales_total' => 60000,
            'non_cash_sales_total' => 50000,
            'cash_refund_total' => 10000,
        ]);
    }

    public function test_closing_shift_includes_paid_photobox_sales_in_shift_window_and_email(): void
    {
        $cashier = $this->createUserWithPermissions([
            'cashier-shifts-access',
            'cashier-shifts-close',
        ]);

        $shift = CashierShift::create([
            'user_id' => $cashier->id,
            'opened_by' => $cashier->id,
            'opened_at' => now()->subHours(3),
            'opening_cash' => 100000,
            'expected_cash' => 100000,
            'status' => CashierShift::STATUS_OPEN,
        ]);

        $studio = new PhotoboxStudio(['name' => 'Studio Emma', 'is_active' => true, 'token_hash' => bin2hex(random_bytes(32))]);
        $studio->save();

        $make = fn (string $no, int $amount, string $status, $paidAt) => PhotoboxTransaction::create([
            'order_no' => $no, 'studio_id' => $studio->id, 'template_name' => 'Emma Room', 'mode' => 'photo',
            'method' => 'qris', 'amount' => $amount, 'status' => $status, 'paid_at' => $paidAt,
        ]);

        $make('PB-T-0001', 35000, 'paid', now()->subHour());      // masuk
        $make('PB-T-0002', 40000, 'paid', now()->subMinutes(30)); // masuk
        $make('PB-T-0003', 99000, 'pending', null);                // belum lunas: tidak masuk
        $make('PB-T-0004', 77000, 'paid', now()->subHours(5));    // sebelum shift dibuka: tidak masuk
        PhotoboxTransaction::create([ // transaksi uji (simulasi / sandbox): tampil di riwayat tapi tidak dihitung
            'order_no' => 'PB-T-0006', 'studio_id' => $studio->id, 'template_name' => 'Emma Room', 'mode' => 'photo',
            'method' => 'qris', 'amount' => 88000, 'status' => 'paid', 'is_test' => true, 'paid_at' => now()->subMinutes(20),
        ]);
        $deleted = $make('PB-T-0005', 55000, 'paid', now()->subMinutes(10));
        $deleted->delete();                                         // dihapus di kasir: tidak masuk

        $this->actingAs($cashier)
            ->post(route('cashier-shifts.close', $shift), ['actual_cash' => 100000])
            ->assertRedirect(route('cashier-shifts.show', ['cashierShift' => $shift->id, 'justClosed' => 1]));

        $this->assertDatabaseHas('cashier_shifts', [
            'id' => $shift->id,
            'status' => CashierShift::STATUS_CLOSED,
            'photobox_sales_total' => 75000,
            'photobox_transactions_count' => 2,
            // photobox dibayar lewat QRIS ke gateway: tidak mengubah kas laci
            'expected_cash' => 100000,
            'cash_difference' => 0,
            'cash_sales_total' => 0,
            'non_cash_sales_total' => 0,
        ]);

        $html = (new CashierShiftClosingReportMail($shift->fresh()))->render();
        $this->assertStringContainsString('Photobox', $html);
        $this->assertStringContainsString('Rp 75.000', $html);
        $this->assertStringContainsString('Emma Room', $html);
    }

    public function test_photobox_sales_are_counted_in_only_one_of_overlapping_shifts(): void
    {
        $first = $this->createUserWithPermissions(['cashier-shifts-access']);
        $second = $this->createUserWithPermissions(['cashier-shifts-access']);

        $mk = fn ($user, $openedAt) => CashierShift::create([
            'user_id' => $user->id, 'opened_by' => $user->id, 'opened_at' => $openedAt,
            'opening_cash' => 0, 'expected_cash' => 0, 'status' => CashierShift::STATUS_OPEN,
        ]);
        $shiftA = $mk($first, now()->subHours(3));  // dibuka lebih awal
        $shiftB = $mk($second, now()->subHours(2)); // tumpang tindih dengan A

        $studio = new PhotoboxStudio(['name' => 'Studio Emma', 'is_active' => true, 'token_hash' => bin2hex(random_bytes(32))]);
        $studio->save();
        PhotoboxTransaction::create([
            'order_no' => 'PB-O-0001', 'studio_id' => $studio->id, 'template_name' => 'Emma Room', 'mode' => 'photo',
            'method' => 'qris', 'amount' => 35000, 'status' => 'paid', 'paid_at' => now()->subHour(),
        ]);

        $service = app(\App\Services\CashierShiftService::class);
        // hanya shift yang dibuka paling awal yang menghitungnya, jadi tidak dobel
        $this->assertSame(35000, $service->calculatePhotoboxTotals($shiftA)['photobox_sales_total']);
        $this->assertSame(0, $service->calculatePhotoboxTotals($shiftB)['photobox_sales_total']);
        $this->assertSame(1, $service->calculatePhotoboxRecap($shiftA)['sessions']);
        $this->assertSame(0, $service->calculatePhotoboxRecap($shiftB)['sessions']);
    }

    public function test_admin_with_force_close_permission_can_close_other_cashier_shift(): void
    {
        $cashier = $this->createUserWithPermissions([
            'cashier-shifts-access',
        ]);
        $admin = $this->createUserWithPermissions([
            'cashier-shifts-access',
            'cashier-shifts-close',
            'cashier-shifts-force-close',
        ]);

        $shift = CashierShift::create([
            'user_id' => $cashier->id,
            'opened_by' => $cashier->id,
            'opened_at' => now(),
            'opening_cash' => 80000,
            'expected_cash' => 80000,
            'status' => CashierShift::STATUS_OPEN,
        ]);

        $response = $this
            ->withSession($this->recentlyConfirmedSession())
            ->actingAs($admin)
            ->post(route('cashier-shifts.close', $shift), [
                'actual_cash' => 80000,
            ]);

        $response->assertRedirect(route('cashier-shifts.show', ['cashierShift' => $shift->id, 'justClosed' => 1]));
        $this->assertDatabaseHas('cashier_shifts', [
            'id' => $shift->id,
            'closed_by' => $admin->id,
            'status' => CashierShift::STATUS_FORCE_CLOSED,
        ]);
    }

    public function test_force_close_redirects_to_confirm_password_when_confirmation_is_stale(): void
    {
        $cashier = $this->createUserWithPermissions(['cashier-shifts-access']);
        $admin = $this->createUserWithPermissions([
            'cashier-shifts-access',
            'cashier-shifts-close',
            'cashier-shifts-force-close',
        ]);

        $shift = CashierShift::create([
            'user_id' => $cashier->id,
            'opened_by' => $cashier->id,
            'opened_at' => now(),
            'opening_cash' => 80000,
            'expected_cash' => 80000,
            'status' => CashierShift::STATUS_OPEN,
        ]);

        $this->actingAs($admin)
            ->from(route('cashier-shifts.show', $shift))
            ->post(route('cashier-shifts.close', $shift), [
                'actual_cash' => 80000,
            ])
            ->assertRedirect(route('password.confirm'));
    }

    private function createUserWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }
}
