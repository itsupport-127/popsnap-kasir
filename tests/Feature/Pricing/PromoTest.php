<?php

namespace Tests\Feature\Pricing;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Promo;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PromoTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'promos-access',
            'promos-create',
            'promos-update',
            'promos-delete',
            'transactions-access',
            'cashier-shifts-access',
            'cashier-shifts-open',
            'cashier-shifts-close',
        ] as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }
    }

    public function test_authorized_user_can_create_auto_promo(): void
    {
        $user = $this->createUserWithPermissions([
            'promos-access',
            'promos-create',
        ]);
        $category = Category::create([
            'name' => 'Minuman',
            'description' => 'Kategori uji',
            'image' => 'category.png',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('promos.store'), [
                'name' => 'Promo Minuman Pagi',
                'discount_kind' => Promo::KIND_STANDARD_DISCOUNT,
                'activation_mode' => Promo::ACTIVATION_AUTO,
                'is_active' => true,
                'priority' => 120,
                'target_type' => 'category',
                'category_id' => $category->id,
                'customer_scope' => 'all',
                'discount_type' => 'percentage',
                'discount_value' => 10,
                'starts_at' => now()->subHour()->format('Y-m-d\TH:i'),
                'ends_at' => now()->addHour()->format('Y-m-d\TH:i'),
                'notes' => 'Promo aktif pagi ini',
            ]);

        $response->assertRedirect(route('promos.index'));
        $this->assertDatabaseHas('promos', [
            'name' => 'Promo Minuman Pagi',
            'activation_mode' => Promo::ACTIVATION_AUTO,
            'target_type' => 'category',
            'category_id' => $category->id,
            'discount_type' => 'percentage',
            'customer_scope' => 'all',
            'created_by' => $user->id,
        ]);
    }

    public function test_authorized_user_can_create_manual_bogo_promo_without_picking_products(): void
    {
        $user = $this->createUserWithPermissions([
            'promos-access',
            'promos-create',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('promos.store'), [
                'name' => 'Beli 2 Gratis 1 Semua Produk',
                'discount_kind' => Promo::KIND_BUY_X_GET_Y,
                'activation_mode' => Promo::ACTIVATION_MANUAL,
                'is_active' => true,
                'target_type' => 'all',
                'customer_scope' => 'all',
                'buy_qty' => 2,
                'get_qty' => 1,
                'get_discount_percent' => 100,
            ]);

        $response->assertRedirect(route('promos.index'));
        $this->assertDatabaseHas('promos', [
            'name' => 'Beli 2 Gratis 1 Semua Produk',
            'activation_mode' => Promo::ACTIVATION_MANUAL,
            'discount_kind' => Promo::KIND_BUY_X_GET_Y,
            'target_type' => 'all',
            'buy_qty' => 2,
            'get_qty' => 1,
        ]);
    }

    public function test_pricing_preview_respects_customer_scope(): void
    {
        $cashier = $this->createUserWithPermissions([
            'transactions-access',
            'cashier-shifts-access',
            'cashier-shifts-open',
            'cashier-shifts-close',
        ]);
        $this->openShiftFor($cashier);
        $product = $this->createProduct();
        $customer = Customer::create([
            'name' => 'Registered Customer',
            'no_telp' => '62812345678',
            'address' => 'Jl. Uji Pelanggan',
        ]);

        Cart::create([
            'cashier_id' => $cashier->id,
            'product_id' => $product->id,
            'qty' => 1,
            'price' => $product->sell_price,
        ]);

        Promo::create([
            'name' => 'Harga Member',
            'activation_mode' => Promo::ACTIVATION_AUTO,
            'discount_kind' => Promo::KIND_STANDARD_DISCOUNT,
            'is_active' => true,
            'priority' => 200,
            'target_type' => 'product',
            'product_id' => $product->id,
            'customer_scope' => 'registered',
            'discount_type' => 'fixed_amount',
            'discount_value' => 10000,
        ]);

        $walkInResponse = $this
            ->actingAs($cashier)
            ->postJson(route('transactions.pricing-preview'), []);

        $registeredResponse = $this
            ->actingAs($cashier)
            ->postJson(route('transactions.pricing-preview'), [
                'customer_id' => $customer->id,
            ]);

        $walkInResponse->assertOk();
        $registeredResponse->assertOk();
        $this->assertSame(
            0,
            data_get($walkInResponse->json(), 'data.summary.promo_discount_total')
        );
        $this->assertSame(
            10000,
            data_get($registeredResponse->json(), 'data.summary.promo_discount_total')
        );
    }

    public function test_qty_break_preview_applies_wholesale_rule(): void
    {
        $cashier = $this->createUserWithPermissions([
            'transactions-access',
            'cashier-shifts-access',
            'cashier-shifts-open',
            'cashier-shifts-close',
        ]);
        $this->openShiftFor($cashier);
        $product = $this->createProduct();

        Cart::create([
            'cashier_id' => $cashier->id,
            'product_id' => $product->id,
            'qty' => 3,
            'price' => $product->sell_price * 3,
        ]);

        $promo = Promo::create([
            'name' => 'Harga Grosir Produk',
            'discount_kind' => Promo::KIND_QTY_BREAK,
            'activation_mode' => Promo::ACTIVATION_AUTO,
            'is_active' => true,
            'priority' => 250,
            'target_type' => 'product',
            'product_id' => $product->id,
            'customer_scope' => 'all',
            'discount_type' => 'fixed_price',
            'discount_value' => 0,
        ]);
        $promo->qtyBreaks()->create([
            'min_qty' => 3,
            'discount_type' => 'fixed_price',
            'discount_value' => 50000,
            'sort_order' => 0,
        ]);

        $response = $this
            ->actingAs($cashier)
            ->postJson(route('transactions.pricing-preview'), []);

        $response->assertOk();
        $this->assertSame(
            30000,
            data_get($response->json(), 'data.summary.promo_discount_total')
        );
        $this->assertSame(
            'qty_break',
            data_get($response->json(), 'data.items.0.promo.discount_kind')
        );
    }

    public function test_bundle_price_preview_returns_applied_group(): void
    {
        $cashier = $this->createUserWithPermissions([
            'transactions-access',
            'cashier-shifts-access',
            'cashier-shifts-open',
            'cashier-shifts-close',
        ]);
        $this->openShiftFor($cashier);
        $productA = $this->createProduct('Produk Bundle A');
        $productB = $this->createProduct('Produk Bundle B');

        Cart::create([
            'cashier_id' => $cashier->id,
            'product_id' => $productA->id,
            'qty' => 1,
            'price' => $productA->sell_price,
        ]);
        Cart::create([
            'cashier_id' => $cashier->id,
            'product_id' => $productB->id,
            'qty' => 1,
            'price' => $productB->sell_price,
        ]);

        $promo = Promo::create([
            'name' => 'Bundle Hemat',
            'discount_kind' => Promo::KIND_BUNDLE_PRICE,
            'activation_mode' => Promo::ACTIVATION_AUTO,
            'is_active' => true,
            'priority' => 400,
            'target_type' => 'all',
            'customer_scope' => 'all',
            'discount_type' => 'fixed_price',
            'discount_value' => 100000,
        ]);
        $promo->bundleItems()->createMany([
            ['product_id' => $productA->id, 'quantity' => 1, 'sort_order' => 0],
            ['product_id' => $productB->id, 'quantity' => 1, 'sort_order' => 1],
        ]);

        $response = $this
            ->actingAs($cashier)
            ->postJson(route('transactions.pricing-preview'), []);

        $response->assertOk();
        $this->assertSame(
            20000,
            data_get($response->json(), 'data.summary.promo_discount_total')
        );
        $this->assertCount(1, data_get($response->json(), 'data.applied_groups', []));
    }

    public function test_buy_x_get_y_preview_discounts_reward_item(): void
    {
        $cashier = $this->createUserWithPermissions([
            'transactions-access',
            'cashier-shifts-access',
            'cashier-shifts-open',
            'cashier-shifts-close',
        ]);
        $this->openShiftFor($cashier);
        $buyProduct = $this->createProduct('Produk Buy');
        $getProduct = $this->createProduct('Produk Get');

        Cart::create([
            'cashier_id' => $cashier->id,
            'product_id' => $buyProduct->id,
            'qty' => 1,
            'price' => $buyProduct->sell_price,
        ]);
        Cart::create([
            'cashier_id' => $cashier->id,
            'product_id' => $getProduct->id,
            'qty' => 1,
            'price' => $getProduct->sell_price,
        ]);

        $promo = Promo::create([
            'name' => 'Buy 1 Get 1',
            'discount_kind' => Promo::KIND_BUY_X_GET_Y,
            'activation_mode' => Promo::ACTIVATION_AUTO,
            'is_active' => true,
            'priority' => 450,
            'target_type' => 'all',
            'customer_scope' => 'all',
            'discount_type' => 'fixed_amount',
            'discount_value' => 0,
        ]);
        $promo->buyGetItems()->createMany([
            ['product_id' => $buyProduct->id, 'role' => 'buy', 'quantity' => 1, 'sort_order' => 0],
            ['product_id' => $getProduct->id, 'role' => 'get', 'quantity' => 1, 'sort_order' => 1],
        ]);

        $response = $this
            ->actingAs($cashier)
            ->postJson(route('transactions.pricing-preview'), []);

        $response->assertOk();
        $this->assertSame(
            60000,
            data_get($response->json(), 'data.summary.promo_discount_total')
        );
        $this->assertSame(
            'buy_x_get_y',
            data_get($response->json(), 'data.items.1.promo.discount_kind')
        );
    }

    public function test_manual_bogo_generic_promo_applies_without_product_pivot(): void
    {
        $cashier = $this->createUserWithPermissions([
            'transactions-access',
            'cashier-shifts-access',
            'cashier-shifts-open',
            'cashier-shifts-close',
        ]);
        $this->openShiftFor($cashier);
        $product = $this->createProduct();

        Cart::create([
            'cashier_id' => $cashier->id,
            'product_id' => $product->id,
            'qty' => 3,
            'price' => $product->sell_price * 3,
        ]);

        $promo = Promo::create([
            'name' => 'Beli 2 Gratis 1 Generik',
            'discount_kind' => Promo::KIND_BUY_X_GET_Y,
            'activation_mode' => Promo::ACTIVATION_MANUAL,
            'is_active' => true,
            'target_type' => 'all',
            'customer_scope' => 'all',
            'discount_type' => 'fixed_amount',
            'discount_value' => 0,
            'buy_qty' => 2,
            'get_qty' => 1,
            'get_discount_percent' => 100,
        ]);

        $response = $this
            ->actingAs($cashier)
            ->postJson(route('transactions.pricing-preview'), [
                'customer_voucher_id' => $promo->id,
            ]);

        $response->assertOk();
        $this->assertSame(
            (int) $product->sell_price,
            data_get($response->json(), 'data.summary.voucher_discount_total')
        );
    }

    public function test_transaction_checkout_recalculates_grand_total_using_promos(): void
    {
        $cashier = $this->createUserWithPermissions([
            'transactions-access',
            'cashier-shifts-access',
            'cashier-shifts-open',
            'cashier-shifts-close',
        ]);
        $shift = $this->openShiftFor($cashier);
        $product = $this->createProduct();
        $customer = Customer::create([
            'name' => 'Customer Promo',
            'no_telp' => '628777888999',
            'address' => 'Jl. Promo No. 1',
        ]);

        Cart::create([
            'cashier_id' => $cashier->id,
            'product_id' => $product->id,
            'qty' => 2,
            'price' => $product->sell_price * 2,
        ]);

        Promo::create([
            'name' => 'Harga Spesial Produk',
            'discount_kind' => Promo::KIND_STANDARD_DISCOUNT,
            'activation_mode' => Promo::ACTIVATION_AUTO,
            'is_active' => true,
            'priority' => 300,
            'target_type' => 'product',
            'product_id' => $product->id,
            'customer_scope' => 'all',
            'discount_type' => 'fixed_price',
            'discount_value' => 50000,
        ]);

        $response = $this
            ->actingAs($cashier)
            ->post(route('transactions.store'), [
                'customer_id' => $customer->id,
                'discount' => 5000,
                'shipping_cost' => 0,
                'grand_total' => 999999,
                'cash' => 100000,
                'change' => 0,
            ]);

        $transaction = Transaction::with(['details', 'profits'])->latest('id')->first();

        $response->assertRedirect(route('transactions.print', $transaction->invoice));
        $this->assertNotNull($transaction);
        $this->assertSame($shift->id, $transaction->cashier_shift_id);
        $this->assertSame(95000, (int) $transaction->grand_total);
        $this->assertSame(5000, (int) $transaction->discount);
        $this->assertSame(100000, (int) $transaction->cash);
        $this->assertSame(5000, (int) $transaction->change);

        $detail = $transaction->details->first();
        $this->assertSame(60000, (int) $detail->base_unit_price);
        $this->assertSame(50000, (int) $detail->unit_price);
        $this->assertSame(100000, (int) $detail->price);
        $this->assertSame(20000, (int) $detail->discount_total);
        $this->assertSame('Harga Spesial Produk', $detail->pricing_rule_name);

        $profit = $transaction->profits->first();
        $this->assertSame(5000, (int) $profit->total);
        $this->assertDatabaseMissing('carts', [
            'cashier_id' => $cashier->id,
        ]);
        $this->assertSame(23, $product->fresh()->stock);
    }

    public function test_two_different_voucher_codes_coordinate_across_separate_units(): void
    {
        // Case 1 dari brief: customer beli 2 barang beda harga, menang 2 kode
        // BEDA (17% & 45%) di undian. Tiap kode harus nempel ke barang yang
        // beda (bukan dua-duanya numpuk di barang termurah).
        $cashier = $this->createUserWithPermissions([
            'transactions-access',
            'cashier-shifts-access',
            'cashier-shifts-open',
            'cashier-shifts-close',
        ]);
        $this->openShiftFor($cashier);

        $cheapProduct = $this->createProduct('Fisheye SnapBox');
        $cheapProduct->update(['sell_price' => 30000]);
        $pricierProduct = $this->createProduct('Vintage SnapBox');
        $pricierProduct->update(['sell_price' => 35000]);

        Cart::create([
            'cashier_id' => $cashier->id,
            'product_id' => $cheapProduct->id,
            'qty' => 1,
            'price' => $cheapProduct->sell_price,
        ]);
        Cart::create([
            'cashier_id' => $cashier->id,
            'product_id' => $pricierProduct->id,
            'qty' => 1,
            'price' => $pricierProduct->sell_price,
        ]);

        $voucher17 = Promo::create([
            'name' => 'Panjat 17%',
            'code' => 'PANJAT17',
            'discount_kind' => Promo::KIND_STANDARD_DISCOUNT,
            'activation_mode' => Promo::ACTIVATION_MANUAL,
            'is_active' => true,
            'target_type' => 'all',
            'customer_scope' => 'all',
            'discount_type' => 'percentage',
            'discount_value' => 17,
            'applies_to_qty' => 1,
        ]);
        $voucher45 = Promo::create([
            'name' => 'Panjat 45%',
            'code' => 'PANJAT45',
            'discount_kind' => Promo::KIND_STANDARD_DISCOUNT,
            'activation_mode' => Promo::ACTIVATION_MANUAL,
            'is_active' => true,
            'target_type' => 'all',
            'customer_scope' => 'all',
            'discount_type' => 'percentage',
            'discount_value' => 45,
            'applies_to_qty' => 1,
        ]);

        $response = $this
            ->actingAs($cashier)
            ->postJson(route('transactions.pricing-preview'), [
                'customer_voucher_ids' => [$voucher17->id, $voucher45->id],
            ]);

        $response->assertOk();

        // 17% harus kepakai ke barang termurah yang TERSISA (30.000) dan 45%
        // ke sisanya (35.000) — bukan dua-duanya kepotong dari barang yang sama.
        $expectedDiscount = (int) round(30000 * 0.17) + (int) round(35000 * 0.45);
        $this->assertSame(
            $expectedDiscount,
            (int) data_get($response->json(), 'data.summary.voucher_discount_total')
        );

        $byCartId = data_get($response->json(), 'data.summary.voucher_discount_by_cart_id');
        // Dua entri (2 cart line berbeda) masing2 dapat 1 diskon — bukan
        // 1 cart line dapat 2 diskon numpuk.
        $this->assertCount(2, $byCartId);
    }

    public function test_same_voucher_code_won_twice_applies_to_two_different_units(): void
    {
        // Case 2 dari brief: customer beli 2 barang IDENTIK dan menang kode
        // yang SAMA dua kali di undian. Kode itu harus kepakai 2x (ke 2
        // barang beda), bukan cuma terhitung 1x.
        $cashier = $this->createUserWithPermissions([
            'transactions-access',
            'cashier-shifts-access',
            'cashier-shifts-open',
            'cashier-shifts-close',
        ]);
        $this->openShiftFor($cashier);
        $product = $this->createProduct('Fisheye SnapBox');
        $product->update(['sell_price' => 30000]);

        Cart::create([
            'cashier_id' => $cashier->id,
            'product_id' => $product->id,
            'qty' => 2,
            'price' => $product->sell_price * 2,
        ]);

        $voucher17 = Promo::create([
            'name' => 'Panjat 17%',
            'code' => 'PANJAT17',
            'discount_kind' => Promo::KIND_STANDARD_DISCOUNT,
            'activation_mode' => Promo::ACTIVATION_MANUAL,
            'is_active' => true,
            'target_type' => 'all',
            'customer_scope' => 'all',
            'discount_type' => 'percentage',
            'discount_value' => 17,
            'applies_to_qty' => 1,
            'max_uses' => 5,
        ]);

        // Kasir klik "+" dua kali untuk kode yang sama (2 kemenangan).
        $response = $this
            ->actingAs($cashier)
            ->postJson(route('transactions.pricing-preview'), [
                'customer_voucher_ids' => [$voucher17->id, $voucher17->id],
            ]);

        $response->assertOk();

        $expectedDiscount = 2 * (int) round(30000 * 0.17);
        $this->assertSame(
            $expectedDiscount,
            (int) data_get($response->json(), 'data.summary.voucher_discount_total')
        );
        $this->assertCount(2, data_get($response->json(), 'data.vouchers'));
    }

    public function test_voucher_cannot_be_won_more_times_than_its_remaining_quota(): void
    {
        // Kalau kuota sisa cuma 1 (mis. max_uses=3, used_count=2), kasir
        // klik "+" 2x untuk kode yang sama harus tetap cuma kehitung 1x —
        // TIDAK boleh menembus kuota kode itu sendiri dalam 1 transaksi.
        $cashier = $this->createUserWithPermissions([
            'transactions-access',
            'cashier-shifts-access',
            'cashier-shifts-open',
            'cashier-shifts-close',
        ]);
        $this->openShiftFor($cashier);
        $product = $this->createProduct('Fisheye SnapBox');
        $product->update(['sell_price' => 30000]);

        Cart::create([
            'cashier_id' => $cashier->id,
            'product_id' => $product->id,
            'qty' => 2,
            'price' => $product->sell_price * 2,
        ]);

        $voucher = Promo::create([
            'name' => 'Panjat 81%',
            'code' => 'PANJAT81',
            'discount_kind' => Promo::KIND_STANDARD_DISCOUNT,
            'activation_mode' => Promo::ACTIVATION_MANUAL,
            'is_active' => true,
            'target_type' => 'all',
            'customer_scope' => 'all',
            'discount_type' => 'percentage',
            'discount_value' => 81,
            'applies_to_qty' => 1,
            'max_uses' => 3,
            'used_count' => 2,
        ]);

        $response = $this
            ->actingAs($cashier)
            ->postJson(route('transactions.pricing-preview'), [
                'customer_voucher_ids' => [$voucher->id, $voucher->id],
            ]);

        $response->assertOk();

        $expectedDiscount = (int) round(30000 * 0.81);
        $this->assertSame(
            $expectedDiscount,
            (int) data_get($response->json(), 'data.summary.voucher_discount_total')
        );
        $this->assertCount(1, data_get($response->json(), 'data.vouchers'));
    }


    public function test_manual_item_discount_percent_computes_from_remaining_after_voucher(): void
    {
        // Barang Rp100.000, menang voucher 10% (jadi sisa Rp90.000), lalu
        // kasir kasih diskon manual PER ITEM 50% ke barang yang sama.
        // 50% itu harus dihitung dari SISA (90.000), bukan dari harga asli
        // (100.000) — hasilnya harus Rp45.000, bukan Rp50.000.
        $cashier = $this->createUserWithPermissions([
            'transactions-access',
            'cashier-shifts-access',
            'cashier-shifts-open',
            'cashier-shifts-close',
        ]);
        $this->openShiftFor($cashier);
        $product = $this->createProduct('Fisheye SnapBox');
        $product->update(['sell_price' => 100000]);

        $cart = Cart::create([
            'cashier_id' => $cashier->id,
            'product_id' => $product->id,
            'qty' => 1,
            'price' => $product->sell_price,
        ]);

        $voucher = Promo::create([
            'name' => 'Panjat 10%',
            'code' => 'PANJAT10',
            'discount_kind' => Promo::KIND_STANDARD_DISCOUNT,
            'activation_mode' => Promo::ACTIVATION_MANUAL,
            'is_active' => true,
            'target_type' => 'all',
            'customer_scope' => 'all',
            'discount_type' => 'percentage',
            'discount_value' => 10,
            'applies_to_qty' => 1,
        ]);

        $response = $this
            ->actingAs($cashier)
            ->postJson(route('transactions.pricing-preview'), [
                'customer_voucher_ids' => [$voucher->id],
                'item_discounts' => [
                    ['cart_id' => $cart->id, 'mode' => 'percent', 'value' => 50],
                ],
            ]);

        $response->assertOk();

        $this->assertSame(10000, (int) data_get($response->json(), 'data.summary.voucher_discount_total'));
        $this->assertSame(45000, (int) data_get($response->json(), 'data.summary.item_manual_discount_total'));
        $this->assertSame(
            45000,
            (int) data_get($response->json(), "data.summary.item_manual_discount_by_cart_id.{$cart->id}")
        );
        // Subtotal sisa: 100.000 - 10.000 (voucher) - 45.000 (manual item) = 45.000
        $this->assertSame(45000, (int) data_get($response->json(), 'data.summary.grand_total'));
    }

    public function test_manual_item_discount_nominal_is_clamped_to_remaining_amount(): void
    {
        // Barang Rp30.000, kasir salah ketik diskon manual Rp999.999 —
        // harus di-clamp maksimal sebesar sisa harga barang itu sendiri
        // (Rp30.000), bukan bikin harga jadi minus.
        $cashier = $this->createUserWithPermissions([
            'transactions-access',
            'cashier-shifts-access',
            'cashier-shifts-open',
            'cashier-shifts-close',
        ]);
        $this->openShiftFor($cashier);
        $product = $this->createProduct('Fisheye SnapBox');
        $product->update(['sell_price' => 30000]);

        $cart = Cart::create([
            'cashier_id' => $cashier->id,
            'product_id' => $product->id,
            'qty' => 1,
            'price' => $product->sell_price,
        ]);

        $response = $this
            ->actingAs($cashier)
            ->postJson(route('transactions.pricing-preview'), [
                'item_discounts' => [
                    ['cart_id' => $cart->id, 'mode' => 'nominal', 'value' => 999999],
                ],
            ]);

        $response->assertOk();

        $this->assertSame(30000, (int) data_get($response->json(), 'data.summary.item_manual_discount_total'));
        $this->assertSame(0, (int) data_get($response->json(), 'data.summary.grand_total'));
    }

    public function test_kasir_can_manually_attach_a_voucher_code_to_one_specific_item(): void
    {
        // Kasir punya 2 barang beda harga di keranjang, dan MANUAL nempelin
        // kode PANJAT45 ke barang yang lebih MAHAL (bukan diserahkan ke
        // alokasi otomatis, yang defaultnya bakal milih barang termurah).
        $cashier = $this->createUserWithPermissions([
            'transactions-access',
            'cashier-shifts-access',
            'cashier-shifts-open',
            'cashier-shifts-close',
        ]);
        $this->openShiftFor($cashier);

        $cheapProduct = $this->createProduct('Fisheye SnapBox');
        $cheapProduct->update(['sell_price' => 30000]);
        $pricierProduct = $this->createProduct('Vintage SnapBox');
        $pricierProduct->update(['sell_price' => 35000]);

        Cart::create([
            'cashier_id' => $cashier->id,
            'product_id' => $cheapProduct->id,
            'qty' => 1,
            'price' => $cheapProduct->sell_price,
        ]);
        $pricierCart = Cart::create([
            'cashier_id' => $cashier->id,
            'product_id' => $pricierProduct->id,
            'qty' => 1,
            'price' => $pricierProduct->sell_price,
        ]);

        $voucher45 = Promo::create([
            'name' => 'Panjat 45%',
            'code' => 'PANJAT45',
            'discount_kind' => Promo::KIND_STANDARD_DISCOUNT,
            'activation_mode' => Promo::ACTIVATION_MANUAL,
            'is_active' => true,
            'target_type' => 'all',
            'customer_scope' => 'all',
            'discount_type' => 'percentage',
            'discount_value' => 45,
            'applies_to_qty' => 1,
            'max_uses' => 5,
        ]);

        $response = $this
            ->actingAs($cashier)
            ->postJson(route('transactions.pricing-preview'), [
                'item_vouchers' => [
                    ['cart_id' => $pricierCart->id, 'promo_id' => $voucher45->id],
                ],
            ]);

        $response->assertOk();

        // Harus nempel ke barang Rp35.000 (yang dipilih kasir), BUKAN ke
        // barang Rp30.000 yang lebih murah (yang dipilih alokasi otomatis
        // kalau kasir nggak nempelin manual).
        $expectedDiscount = (int) round(35000 * 0.45);
        $this->assertSame($expectedDiscount, (int) data_get($response->json(), 'data.summary.voucher_discount_total'));
        $this->assertSame(
            $expectedDiscount,
            (int) data_get($response->json(), "data.summary.voucher_discount_by_cart_id.{$pricierCart->id}")
        );
    }

    private function createUserWithPermissions(array $permissions): User
    {
        $user = User::factory()->create();
        $user->givePermissionTo($permissions);

        return $user;
    }

    private function openShiftFor(User $cashier)
    {
        return \App\Models\CashierShift::create([
            'user_id' => $cashier->id,
            'opened_by' => $cashier->id,
            'opened_at' => now(),
            'opening_cash' => 100000,
            'expected_cash' => 100000,
            'status' => 'open',
        ]);
    }

    private function createProduct(?string $title = null): Product
    {
        $category = Category::create([
            'name' => 'Snack Promo '.Str::upper(Str::random(4)),
            'description' => 'Kategori promo',
            'image' => 'category.png',
        ]);

        return Product::create([
            'category_id' => $category->id,
            'image' => 'product.png',
            'barcode' => 'BRCD-'.Str::upper(Str::random(10)),
            'sku' => 'SKU-'.Str::upper(Str::random(8)),
            'title' => $title ?? 'Produk Promo',
            'description' => 'Produk untuk pengujian promo.',
            'buy_price' => 45000,
            'sell_price' => 60000,
            'stock' => 25,
        ]);
    }
}
