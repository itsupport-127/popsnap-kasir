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
        $this->assertStringContainsString('Studio Emma', $html);
        $this->assertStringContainsString('QRIS Toko', $html);
        $this->assertStringContainsString('QRIS Photobox', $html);
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

    private function studioShift(User $user, $openedAt, array $studioIds = []): CashierShift
    {
        $shift = CashierShift::create([
            'user_id' => $user->id, 'opened_by' => $user->id, 'opened_at' => $openedAt,
            'opening_cash' => 0, 'expected_cash' => 0, 'status' => CashierShift::STATUS_OPEN,
        ]);
        foreach ($studioIds as $id) {
            \DB::table('cashier_shift_studios')->insert([
                'cashier_shift_id' => $shift->id, 'photobox_studio_id' => $id, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        return $shift;
    }

    private function studioWithSale(string $name, string $orderNo, int $amount): PhotoboxStudio
    {
        $studio = new PhotoboxStudio(['name' => $name, 'is_active' => true, 'token_hash' => bin2hex(random_bytes(32))]);
        $studio->save();
        PhotoboxTransaction::create([
            'order_no' => $orderNo, 'studio_id' => $studio->id, 'template_name' => 'T', 'mode' => 'photo',
            'method' => 'qris', 'amount' => $amount, 'status' => 'paid', 'paid_at' => now()->subHour(),
        ]);

        return $studio;
    }

    public function test_shifts_bound_to_different_studios_each_get_only_their_own_photobox_sales(): void
    {
        $a = $this->createUserWithPermissions(['cashier-shifts-access']);
        $b = $this->createUserWithPermissions(['cashier-shifts-access']);
        $s1 = $this->studioWithSale('Studio 1', 'PB-S-0001', 35000);
        $s2 = $this->studioWithSale('Studio 2', 'PB-S-0002', 50000);
        $shiftA = $this->studioShift($a, now()->subHours(3), [$s1->id]); // dibuka lebih awal, hanya studio 1
        $shiftB = $this->studioShift($b, now()->subHours(2), [$s2->id]); // tumpang tindih, hanya studio 2

        $service = app(\App\Services\CashierShiftService::class);
        $this->assertSame(35000, $service->calculatePhotoboxTotals($shiftA)['photobox_sales_total']);
        $this->assertSame(50000, $service->calculatePhotoboxTotals($shiftB)['photobox_sales_total']);
        $this->assertSame(1, $service->calculatePhotoboxRecap($shiftA)['sessions']);
        $this->assertSame(1, $service->calculatePhotoboxRecap($shiftB)['sessions']);
    }

    public function test_explicit_studio_shift_wins_over_an_earlier_all_studios_shift(): void
    {
        $a = $this->createUserWithPermissions(['cashier-shifts-access']);
        $b = $this->createUserWithPermissions(['cashier-shifts-access']);
        $s1 = $this->studioWithSale('Studio 1', 'PB-S-0003', 35000);
        $this->studioWithSale('Studio 2', 'PB-S-0004', 50000);
        $general = $this->studioShift($a, now()->subHours(3));                 // semua studio, paling awal
        $explicit = $this->studioShift($b, now()->subHours(2), [$s1->id]);     // studio 1 saja, belakangan

        $service = app(\App\Services\CashierShiftService::class);
        $this->assertSame(35000, $service->calculatePhotoboxTotals($explicit)['photobox_sales_total']); // transaksi studio 1 milik shift eksplisit
        $this->assertSame(50000, $service->calculatePhotoboxTotals($general)['photobox_sales_total']);  // sisanya (studio 2) milik shift umum
    }

    public function test_same_studio_overlap_still_goes_to_the_earliest_shift_only(): void
    {
        $a = $this->createUserWithPermissions(['cashier-shifts-access']);
        $b = $this->createUserWithPermissions(['cashier-shifts-access']);
        $s1 = $this->studioWithSale('Studio 1', 'PB-S-0005', 35000);
        $first = $this->studioShift($a, now()->subHours(3), [$s1->id]);
        $second = $this->studioShift($b, now()->subHours(2), [$s1->id]);

        $service = app(\App\Services\CashierShiftService::class);
        $this->assertSame(35000, $service->calculatePhotoboxTotals($first)['photobox_sales_total']);
        $this->assertSame(0, $service->calculatePhotoboxTotals($second)['photobox_sales_total']);
    }

    public function test_deleting_a_studio_does_not_widen_a_restricted_shift_to_all_studios(): void
    {
        $a = $this->createUserWithPermissions(['cashier-shifts-access']);
        $gone = $this->studioWithSale('Studio Hilang', 'PB-S-0006', 10000);
        $other = $this->studioWithSale('Studio Lain', 'PB-S-0007', 50000);
        $shift = $this->studioShift($a, now()->subHours(3), [$gone->id]);
        $gone->delete();

        $this->assertSame([$gone->id], $shift->studioIds());
        $this->assertSame('Studio dihapus #'.$gone->id, app(\App\Services\CashierShiftService::class)->studiosOf($shift)[0]['name']);
        $this->assertSame(0, app(\App\Services\CashierShiftService::class)->calculatePhotoboxTotals($shift)['photobox_sales_total']);
    }

    public function test_open_shift_saves_studios_and_rejects_unknown_studio(): void
    {
        $cashier = $this->createUserWithPermissions(['cashier-shifts-access', 'cashier-shifts-open']);
        $studio = new PhotoboxStudio(['name' => 'Studio X', 'is_active' => true, 'token_hash' => bin2hex(random_bytes(32))]);
        $studio->save();

        $this->actingAs($cashier)->post(route('cashier-shifts.store'), ['opening_cash' => 0, 'photobox_studio_ids' => [999999]])
            ->assertSessionHasErrors('photobox_studio_ids.0');
        $this->assertSame(0, CashierShift::count());

        $this->actingAs($cashier)->post(route('cashier-shifts.store'), ['opening_cash' => 0, 'photobox_studio_ids' => [$studio->id, $studio->id]])
            ->assertSessionHasErrors('photobox_studio_ids.1'); // duplikat ditolak

        $this->actingAs($cashier)->post(route('cashier-shifts.store'), ['opening_cash' => 0, 'photobox_studio_ids' => [$studio->id]]);
        $this->assertSame([$studio->id], CashierShift::first()->studioIds());
    }

    public function test_open_shift_without_studios_keeps_old_all_studios_behaviour(): void
    {
        $cashier = $this->createUserWithPermissions(['cashier-shifts-access', 'cashier-shifts-open']);
        $this->actingAs($cashier)->post(route('cashier-shifts.store'), ['opening_cash' => 0]);
        $this->assertSame([], CashierShift::first()->studioIds());
    }

    public function test_forgotten_open_shift_from_months_ago_does_not_swallow_photobox_sales(): void
    {
        $old = $this->createUserWithPermissions(['cashier-shifts-access']);
        $cashier = $this->createUserWithPermissions(['cashier-shifts-access']);

        // shift lama yang lupa ditutup (masih "open" berbulan-bulan)
        CashierShift::create([
            'user_id' => $old->id, 'opened_by' => $old->id, 'opened_at' => now()->subMonths(4),
            'opening_cash' => 0, 'expected_cash' => 0, 'status' => CashierShift::STATUS_OPEN,
        ]);
        $shift = CashierShift::create([
            'user_id' => $cashier->id, 'opened_by' => $cashier->id, 'opened_at' => now()->subHours(10), 'closed_at' => now()->subHour(),
            'opening_cash' => 0, 'expected_cash' => 0, 'status' => 'closed',
        ]);

        $studio = new PhotoboxStudio(['name' => 'Studio Emma', 'is_active' => true, 'token_hash' => bin2hex(random_bytes(32))]);
        $studio->save();
        PhotoboxTransaction::create([
            'order_no' => 'PB-O-0100', 'studio_id' => $studio->id, 'template_name' => 'Emma Room', 'mode' => 'photo',
            'method' => 'qris', 'amount' => 35000, 'status' => 'paid', 'paid_at' => now()->subHours(5),
        ]);

        $service = app(\App\Services\CashierShiftService::class);
        $this->assertSame(35000, $service->calculatePhotoboxTotals($shift)['photobox_sales_total']);
        $this->assertSame(1, $service->calculatePhotoboxRecap($shift)['sessions']);
    }

    public function test_photobox_recap_is_grouped_per_studio(): void
    {
        $cashier = $this->createUserWithPermissions(['cashier-shifts-access']);
        $shift = CashierShift::create([
            'user_id' => $cashier->id, 'opened_by' => $cashier->id, 'opened_at' => now()->subHours(10), 'closed_at' => now()->subHour(),
            'opening_cash' => 0, 'expected_cash' => 0, 'status' => 'closed',
        ]);
        $emma = new PhotoboxStudio(['name' => 'Studio Emma', 'is_active' => true, 'token_hash' => bin2hex(random_bytes(32))]);
        $emma->save();
        $jason = new PhotoboxStudio(['name' => 'Studio Jason', 'is_active' => true, 'token_hash' => bin2hex(random_bytes(32))]);
        $jason->save();
        $n = 0;
        foreach ([[$emma, 'Paket A', 35000], [$emma, 'Paket B', 35000], [$jason, 'Paket A', 35000]] as [$st, $tpl, $amt]) {
            PhotoboxTransaction::create([
                'order_no' => 'PB-G-'.(++$n), 'studio_id' => $st->id, 'template_name' => $tpl, 'mode' => 'photo',
                'method' => 'qris', 'amount' => $amt, 'status' => 'paid', 'paid_at' => now()->subHours(5),
            ]);
        }

        $recap = app(\App\Services\CashierShiftService::class)->calculatePhotoboxRecap($shift);
        $this->assertSame(['Studio Emma', 'Studio Jason'], array_column($recap['items'], 'name'));
        $this->assertSame([2, 1], array_column($recap['items'], 'qty'));
        $this->assertSame([70000, 35000], array_column($recap['items'], 'revenue'));
        $this->assertSame(105000, $recap['revenue']);
    }

    public function test_photobox_recap_separates_add_print_under_its_studio(): void
    {
        $cashier = $this->createUserWithPermissions(['cashier-shifts-access']);
        $shift = CashierShift::create([
            'user_id' => $cashier->id, 'opened_by' => $cashier->id, 'opened_at' => now()->subHours(10), 'closed_at' => now()->subHour(),
            'opening_cash' => 0, 'expected_cash' => 0, 'status' => 'closed',
        ]);
        $emma = new PhotoboxStudio(['name' => 'Studio Emma', 'is_active' => true, 'token_hash' => bin2hex(random_bytes(32))]);
        $emma->save();
        $n = 0;
        foreach ([['photo', 35000], ['photo', 35000], ['addprint', 10000]] as [$mode, $amt]) {
            PhotoboxTransaction::create([
                'order_no' => 'PB-AP-'.(++$n), 'studio_id' => $emma->id, 'template_name' => 'X', 'mode' => $mode,
                'method' => 'qris', 'amount' => $amt, 'status' => 'paid', 'paid_at' => now()->subHours(5),
            ]);
        }

        $recap = app(\App\Services\CashierShiftService::class)->calculatePhotoboxRecap($shift);
        $this->assertSame(['Studio Emma', 'Add Print (app)'], array_column($recap['items'], 'name'));
        $this->assertSame([2, 1], array_column($recap['items'], 'qty'));
        $this->assertSame([70000, 10000], array_column($recap['items'], 'revenue'));
        $this->assertSame(2, $recap['sessions']);   // add print bukan sesi foto
        $this->assertSame(80000, $recap['revenue']); // tapi nominalnya ikut total
    }

    public function test_photobox_recap_skips_zero_amount_voucher_sessions(): void
    {
        $cashier = $this->createUserWithPermissions(['cashier-shifts-access']);
        $shift = CashierShift::create([
            'user_id' => $cashier->id, 'opened_by' => $cashier->id, 'opened_at' => now()->subHours(10), 'closed_at' => now()->subHour(),
            'opening_cash' => 0, 'expected_cash' => 0, 'status' => 'closed',
        ]);
        $emma = new PhotoboxStudio(['name' => 'Studio Emma', 'is_active' => true, 'token_hash' => bin2hex(random_bytes(32))]);
        $emma->save();
        $jason = new PhotoboxStudio(['name' => 'Studio Jason', 'is_active' => true, 'token_hash' => bin2hex(random_bytes(32))]);
        $jason->save();
        $n = 0;
        foreach ([[$emma, 'qris', 35000], [$jason, 'voucher', 0]] as [$st, $method, $amt]) {
            PhotoboxTransaction::create([
                'order_no' => 'PB-Z-'.(++$n), 'studio_id' => $st->id, 'template_name' => 'X', 'mode' => 'photo',
                'method' => $method, 'amount' => $amt, 'status' => 'paid', 'paid_at' => now()->subHours(5),
            ]);
        }

        $recap = app(\App\Services\CashierShiftService::class)->calculatePhotoboxRecap($shift);
        $this->assertSame(['Studio Emma'], array_column($recap['items'], 'name')); // Studio Jason (voucher Rp 0) tidak tampil
        $this->assertSame(1, $recap['sessions']);
        $this->assertSame(35000, $recap['revenue']);
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

    private function storeShift(User $user, $openedAt, int $storeId, array $studioIds = []): CashierShift
    {
        $shift = $this->studioShift($user, $openedAt, $studioIds);
        $shift->update(['photobox_store_id' => $storeId]);

        return $shift->fresh();
    }

    private function inStore(PhotoboxStudio $studio, \App\Models\PhotoboxStore $store): PhotoboxStudio
    {
        $studio->update(['store_id' => $store->id]);

        return $studio;
    }

    public function test_store_shift_collects_all_studios_of_that_store_including_ones_added_later(): void
    {
        $a = $this->createUserWithPermissions(['cashier-shifts-access']);
        $popsnap = \App\Models\PhotoboxStore::create(['name' => 'POPSNAP']);
        $ccm = \App\Models\PhotoboxStore::create(['name' => 'CCM']);
        $p1 = $this->inStore($this->studioWithSale('P1', 'PB-T-0001', 10000), $popsnap);
        $this->inStore($this->studioWithSale('C1', 'PB-T-0002', 20000), $ccm);
        $shift = $this->storeShift($a, now()->subHours(3), $popsnap->id);
        $svc = app(\App\Services\CashierShiftService::class);

        $this->assertSame(10000, $svc->calculatePhotoboxTotals($shift)['photobox_sales_total']); // CCM tidak ikut

        $p2 = $this->inStore($this->studioWithSale('P2', 'PB-T-0003', 5000), $popsnap); // studio baru di toko yang sama
        $this->assertSame(15000, $svc->calculatePhotoboxTotals($shift)['photobox_sales_total']);
        $this->assertSame('Toko POPSNAP (semua studio)', $svc->scopeLabel($shift));
    }

    public function test_store_shift_with_chosen_studios_only_counts_those(): void
    {
        $a = $this->createUserWithPermissions(['cashier-shifts-access']);
        $popsnap = \App\Models\PhotoboxStore::create(['name' => 'POPSNAP']);
        $p1 = $this->inStore($this->studioWithSale('P1', 'PB-T-0004', 10000), $popsnap);
        $this->inStore($this->studioWithSale('P2', 'PB-T-0005', 5000), $popsnap);
        $shift = $this->storeShift($a, now()->subHours(3), $popsnap->id, [$p1->id]);
        $svc = app(\App\Services\CashierShiftService::class);

        $this->assertSame(10000, $svc->calculatePhotoboxTotals($shift)['photobox_sales_total']);
        $this->assertSame('Toko POPSNAP: P1', $svc->scopeLabel($shift));
    }

    public function test_rank_explicit_beats_store_beats_all_and_stores_do_not_leak(): void
    {
        $u1 = $this->createUserWithPermissions(['cashier-shifts-access']);
        $u2 = $this->createUserWithPermissions(['cashier-shifts-access']);
        $u3 = $this->createUserWithPermissions(['cashier-shifts-access']);
        $u4 = $this->createUserWithPermissions(['cashier-shifts-access']);
        $popsnap = \App\Models\PhotoboxStore::create(['name' => 'POPSNAP']);
        $ccm = \App\Models\PhotoboxStore::create(['name' => 'CCM']);
        $p1 = $this->inStore($this->studioWithSale('P1', 'PB-T-0006', 10000), $popsnap);
        $p2 = $this->inStore($this->studioWithSale('P2', 'PB-T-0007', 5000), $popsnap);
        $this->inStore($this->studioWithSale('C1', 'PB-T-0008', 20000), $ccm);

        $all = $this->studioShift($u1, now()->subHours(5));                                  // semua, paling awal
        $store = $this->storeShift($u2, now()->subHours(4), $popsnap->id);                   // toko POPSNAP
        $explicit = $this->storeShift($u3, now()->subHours(3), $popsnap->id, [$p1->id]);     // P1 saja
        $ccmShift = $this->storeShift($u4, now()->subHours(2), $ccm->id);                    // toko CCM
        $svc = app(\App\Services\CashierShiftService::class);
        $t = fn ($s) => $svc->calculatePhotoboxTotals($s)['photobox_sales_total'];

        $this->assertSame(10000, $t($explicit)); // P1 -> eksplisit
        $this->assertSame(5000, $t($store));     // P2 -> toko (P1 sudah milik eksplisit)
        $this->assertSame(20000, $t($ccmShift)); // C1 -> toko CCM, bukan shift "semua" yang lebih awal
        $this->assertSame(0, $t($all));          // tidak ada sisa
        $this->assertSame(35000, $t($explicit) + $t($store) + $t($ccmShift) + $t($all)); // tidak ada transaksi ganda/hilang
    }

    public function test_open_shift_with_store_validates_studios_belong_to_store(): void
    {
        $cashier = $this->createUserWithPermissions(['cashier-shifts-access', 'cashier-shifts-open']);
        $popsnap = \App\Models\PhotoboxStore::create(['name' => 'POPSNAP']);
        $p1 = $this->inStore($this->studioWithSale('P1', 'PB-T-0009', 1000), $popsnap);
        $other = $this->studioWithSale('Lepas', 'PB-T-0010', 1000); // tanpa toko

        $this->actingAs($cashier)->post(route('cashier-shifts.store'), ['opening_cash' => 0, 'photobox_store_id' => $popsnap->id, 'photobox_studio_ids' => [$other->id]])
            ->assertSessionHasErrors('photobox_studio_ids.0');
        $this->actingAs($cashier)->post(route('cashier-shifts.store'), ['opening_cash' => 0, 'photobox_store_id' => 999999])
            ->assertSessionHasErrors('photobox_store_id');
        $this->assertSame(0, CashierShift::count());

        $this->actingAs($cashier)->post(route('cashier-shifts.store'), ['opening_cash' => 0, 'photobox_store_id' => $popsnap->id]);
        $shift = CashierShift::first();
        $this->assertSame($popsnap->id, (int) $shift->photobox_store_id);
        $this->assertSame([], $shift->studioIds());
    }

    public function test_store_management_assign_rename_and_delete_guard(): void
    {
        Permission::firstOrCreate(['name' => 'photobox-manage', 'guard_name' => 'web']);
        $admin = $this->createUserWithPermissions(['cashier-shifts-access', 'cashier-shifts-open', 'photobox-manage']);

        $this->actingAs($admin)->post(route('photobox-stores.store'), ['name' => 'POPSNAP'])->assertSessionHasNoErrors();
        $this->actingAs($admin)->post(route('photobox-stores.store'), ['name' => 'POPSNAP'])->assertSessionHasErrors('name'); // unik
        $store = \App\Models\PhotoboxStore::first();

        $studio = $this->studioWithSale('P1', 'PB-T-0011', 1000);
        $this->actingAs($admin)->put(route('photobox-studios.update', $studio), ['store_id' => $store->id]);
        $this->assertSame($store->id, (int) $studio->fresh()->store_id);
        $this->assertSame('P1', $studio->fresh()->name); // nama tidak ikut berubah

        $this->actingAs($admin)->put(route('photobox-stores.update', $store), ['name' => 'POPSNAP 2']);
        $this->assertSame('POPSNAP 2', $store->fresh()->name);

        $this->actingAs($admin)->post(route('cashier-shifts.store'), ['opening_cash' => 0, 'photobox_store_id' => $store->id]);
        $this->actingAs($admin)->delete(route('photobox-stores.destroy', $store))->assertSessionHasErrors('store'); // shift terbuka memegang toko
        $this->assertNotNull($store->fresh());

        CashierShift::query()->update(['status' => CashierShift::STATUS_CLOSED, 'closed_at' => now()]);
        $this->actingAs($admin)->delete(route('photobox-stores.destroy', $store))->assertSessionHasNoErrors();
        $this->assertNull(\App\Models\PhotoboxStore::find($store->id));
        $this->assertNull($studio->fresh()->store_id);                 // studio jadi "tanpa toko"
        $this->assertSame(1, PhotoboxTransaction::count());            // transaksi tidak ikut hilang
    }

    public function test_store_options_and_scope_label_for_deleted_store(): void
    {
        $a = $this->createUserWithPermissions(['cashier-shifts-access']);
        $store = \App\Models\PhotoboxStore::create(['name' => 'CCM']);
        $in = $this->inStore($this->studioWithSale('C1', 'PB-T-0012', 1000), $store);
        $free = $this->studioWithSale('Lepas', 'PB-T-0013', 1000);
        $svc = app(\App\Services\CashierShiftService::class);

        $opts = $svc->storeOptions();
        $this->assertSame('CCM', $opts['stores'][0]['name']);
        $this->assertSame([$in->id], array_column($opts['stores'][0]['studios'], 'id'));
        $this->assertSame([$free->id], array_column($opts['unassigned'], 'id'));

        $shift = $this->storeShift($a, now()->subHour(), $store->id);
        \DB::table('photobox_stores')->where('id', $store->id)->delete(); // simulasi dihapus paksa
        $this->assertSame('Toko dihapus #'.$store->id.' (semua studio)', $svc->scopeLabel($shift->fresh()));
        $this->assertSame('Semua studio', $svc->scopeLabel($this->studioShift($a, now()->subHours(2))));
    }

    /** Props halaman Transaksi Photobox untuk user tertentu (dipanggil langsung; render Inertia butuh manifest Vite). */
    private function photoboxIndexProps(User $user, array $query = []): array
    {
        $request = \Illuminate\Http\Request::create('/dashboard/photobox-transactions', 'GET', array_merge(['period' => 'today'], $query));
        $request->setUserResolver(fn () => $user);
        \Illuminate\Support\Facades\Http::fake(['*' => \Illuminate\Support\Facades\Http::response(['sessions' => [], 'orders' => []], 200)]);
        $resp = app(\App\Http\Controllers\Apps\PhotoboxTransactionController::class)->index($request);
        $ref = new \ReflectionProperty($resp, 'props');
        $ref->setAccessible(true);

        return $ref->getValue($resp);
    }

    public function test_cashier_sees_only_photobox_transactions_of_own_shift_scope_and_admin_sees_all(): void
    {
        Permission::firstOrCreate(['name' => 'photobox-access', 'guard_name' => 'web']);
        $cashier = $this->createUserWithPermissions(['cashier-shifts-access', 'photobox-access']);
        $admin = $this->createUserWithPermissions(['cashier-shifts-access', 'cashier-shifts-force-close', 'photobox-access']);
        $popsnap = \App\Models\PhotoboxStore::create(['name' => 'POPSNAP']);
        $ccm = \App\Models\PhotoboxStore::create(['name' => 'CCM']);
        $this->inStore($this->studioWithSale('P1', 'PB-V-0001', 10000), $popsnap);
        $this->inStore($this->studioWithSale('C1', 'PB-V-0002', 20000), $ccm);

        // tanpa shift aktif: tidak ada yang tampil
        $props = $this->photoboxIndexProps($cashier);
        $this->assertSame(0, $props['transactions']->total());
        $this->assertFalse($props['scope']['has_shift']);

        $this->storeShift($cashier, now()->subHours(3), $popsnap->id);
        $props = $this->photoboxIndexProps($cashier);
        $this->assertSame(['PB-V-0001'], $props['transactions']->pluck('order_no')->all());
        $this->assertSame(10000, $props['summary']['revenue']);
        $this->assertSame(['P1'], collect($props['studios'])->pluck('name')->all()); // CCM tidak bocor lewat daftar studio
        $this->assertSame(['POPSNAP'], collect($props['stores'])->pluck('name')->all());
        $this->assertTrue($props['scope']['restricted']);
        // minta studio toko lain lewat filter -> diabaikan
        $other = PhotoboxStudio::where('name', 'C1')->first();
        $props = $this->photoboxIndexProps($cashier, ['studio_id' => $other->id]);
        $this->assertSame(['PB-V-0001'], $props['transactions']->pluck('order_no')->all());

        // admin: semua, dan bisa memisah per toko
        $props = $this->photoboxIndexProps($admin);
        $this->assertSame(2, $props['transactions']->total());
        $this->assertFalse($props['scope']['restricted']);
        $props = $this->photoboxIndexProps($admin, ['store_id' => $ccm->id]);
        $this->assertSame(['PB-V-0002'], $props['transactions']->pluck('order_no')->all());
        $this->assertSame(20000, $props['summary']['revenue']);
        $props = $this->photoboxIndexProps($admin, ['store_id' => 'none']);
        $this->assertSame(0, $props['transactions']->total());
    }

    public function test_cashier_cannot_edit_photobox_transaction_outside_own_scope(): void
    {
        foreach (['photobox-access', 'photobox-edit', 'photobox-delete'] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }
        $cashier = $this->createUserWithPermissions(['cashier-shifts-access', 'photobox-access', 'photobox-edit', 'photobox-delete']);
        $popsnap = \App\Models\PhotoboxStore::create(['name' => 'POPSNAP']);
        $ccm = \App\Models\PhotoboxStore::create(['name' => 'CCM']);
        $this->inStore($this->studioWithSale('P1', 'PB-V-0003', 10000), $popsnap);
        $this->inStore($this->studioWithSale('C1', 'PB-V-0004', 20000), $ccm);
        $this->storeShift($cashier, now()->subHours(3), $popsnap->id);
        $mine = PhotoboxTransaction::where('order_no', 'PB-V-0003')->first();
        $theirs = PhotoboxTransaction::where('order_no', 'PB-V-0004')->first();

        $this->withoutExceptionHandling();
        foreach ([
            fn () => $this->actingAs($cashier)->put(route('photobox-transactions.test', $theirs), ['is_test' => true]),
            fn () => $this->actingAs($cashier)->delete(route('photobox-transactions.destroy', $theirs)),
        ] as $call) {
            try {
                $call();
                $this->fail('Harus ditolak 403');
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }
        }
        $this->assertNotNull($theirs->fresh());
        $this->assertFalse((bool) $theirs->fresh()->is_test);

        $this->actingAs($cashier)->put(route('photobox-transactions.test', $mine), ['is_test' => true])->assertRedirect();
        $this->assertTrue((bool) $mine->fresh()->is_test);
    }

    public function test_shift_recap_and_email_group_studios_by_store_and_stay_inside_scope(): void
    {
        $a = $this->createUserWithPermissions(['cashier-shifts-access']);
        $popsnap = \App\Models\PhotoboxStore::create(['name' => 'POPSNAP']);
        $ccm = \App\Models\PhotoboxStore::create(['name' => 'CCM']);
        $this->inStore($this->studioWithSale('Studio 1990', 'PB-V-0005', 35000), $popsnap);
        $this->inStore($this->studioWithSale('Studio Vintage', 'PB-V-0006', 35000), $popsnap);
        $this->inStore($this->studioWithSale('Studio CCM', 'PB-V-0007', 50000), $ccm);

        $shift = $this->storeShift($a, now()->subHours(3), $popsnap->id);
        $svc = app(\App\Services\CashierShiftService::class);
        $recap = $svc->calculatePhotoboxRecap($shift);
        $this->assertSame(['Studio 1990', 'Studio Vintage'], array_column($recap['items'], 'name'));
        $this->assertSame(70000, $recap['revenue']);
        $this->assertSame([['name' => 'POPSNAP', 'qty' => 2, 'revenue' => 70000]], $recap['stores']);

        $html = (new CashierShiftClosingReportMail($shift->fresh()))->render();
        $this->assertStringContainsString('Toko POPSNAP', $html);
        $this->assertStringContainsString('Studio 1990', $html);
        $this->assertStringNotContainsString('Studio CCM', $html); // toko lain tidak ikut di email shift ini
        $this->assertStringNotContainsString('Rp 50.000', $html);

        // shift "semua studio" menampilkan dua toko dengan subtotal masing-masing
        $all = $this->studioShift($this->createUserWithPermissions(['cashier-shifts-access']), now()->subHours(2));
        $recapAll = $svc->calculatePhotoboxRecap($all);
        // POPSNAP sudah dimiliki shift toko (cakupan lebih spesifik); sisanya, CCM, jatuh ke shift "semua studio"
        $this->assertSame([['name' => 'CCM', 'qty' => 1, 'revenue' => 50000]], $recapAll['stores']);
    }

    public function test_shift_still_works_when_store_migration_has_not_run_yet(): void
    {
        $a = $this->createUserWithPermissions(['cashier-shifts-access', 'cashier-shifts-open', 'cashier-shifts-close']);
        (require database_path('migrations/2026_11_26_000100_create_photobox_stores_table.php'))->down(); // server belum migrate
        $this->studioWithSale('P1', 'PB-V-0008', 12000); // dibuat sesudah down(): sqlite membangun ulang tabel studio dan mengosongkan transaksi (FK cascade)
        app()->forgetInstance(\App\Services\CashierShiftService::class);
        $svc = app(\App\Services\CashierShiftService::class);
        $this->assertFalse($svc->storeSchemaReady());

        $shift = $svc->openShift($a, $a, 205000, null, [], 99); // toko diabaikan, tidak error
        $shift->update(['opened_at' => now()->subHours(3)]);
        $this->assertSame(205000, (int) $shift->fresh()->opening_cash);
        $this->assertSame(12000, $svc->calculatePhotoboxTotals($shift->fresh())['photobox_sales_total']);
        $this->assertSame(1, $svc->calculatePhotoboxRecap($shift->fresh())['sessions']);
        $this->assertSame('Semua studio', $svc->scopeLabel($shift->fresh()));
        $this->assertSame([], $svc->storeOptions()['stores']);

        $this->actingAs($a)->post(route('cashier-shifts.close', $shift), ['actual_cash' => 205000])->assertRedirect();
        $this->assertSame(CashierShift::STATUS_CLOSED, $shift->fresh()->status);
    }

    private function createUserWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }
}
