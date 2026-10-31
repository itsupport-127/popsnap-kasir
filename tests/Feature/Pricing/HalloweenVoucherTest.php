<?php

namespace Tests\Feature\Pricing;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Product;
use App\Models\Promo;
use App\Models\User;
use Database\Seeders\HalloweenPromoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

/**
 * Voucher HALLOWEEN20K: "Harga Final" Rp 20.000 untuk 1 paket kategori PhotoBox per pemakaian kode,
 * dipakai kasir lewat kolom voucher (otomatis termurah) atau ditempelkan ke 1 barang pilihan kasir (item_vouchers).
 */
class HalloweenVoucherTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    private Category $photobox;

    private Category $frames;

    protected function setUp(): void
    {
        parent::setUp();

        $perms = ['transactions-access', 'cashier-shifts-access', 'cashier-shifts-open', 'cashier-shifts-close'];
        foreach ($perms as $p) {
            Permission::firstOrCreate(['name' => $p, 'guard_name' => 'web']);
        }

        $this->cashier = User::factory()->create();
        $this->cashier->givePermissionTo($perms);
        \App\Models\CashierShift::create([
            'user_id' => $this->cashier->id, 'opened_by' => $this->cashier->id, 'opened_at' => now(),
            'opening_cash' => 100000, 'expected_cash' => 100000, 'status' => 'open',
        ]);

        $this->photobox = Category::create(['name' => 'PhotoBox', 'description' => 'Paket photobox', 'image' => 'c.png']);
        $this->frames = Category::create(['name' => 'Bingkai Foto', 'description' => 'Bingkai', 'image' => 'c.png']);

        // Di dalam masa promo, supaya kode aktif kecuali tes yang sengaja mengubah waktu.
        Carbon::setTestNow(Carbon::create(2026, 10, 31, 14, 0, 0, 'Asia/Jakarta'));
        (new HalloweenPromoSeeder)->run();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function product(Category $category, string $title, int $price): Product
    {
        return Product::create([
            'category_id' => $category->id, 'image' => 'p.png', 'barcode' => 'BRCD-'.Str::upper(Str::random(10)),
            'sku' => 'SKU-'.Str::upper(Str::random(8)), 'title' => $title, 'description' => $title,
            'buy_price' => 10000, 'sell_price' => $price, 'stock' => 50,
        ]);
    }

    private function cart(Product $product, int $qty = 1): Cart
    {
        return Cart::create([
            'cashier_id' => $this->cashier->id, 'product_id' => $product->id,
            'qty' => $qty, 'price' => $product->sell_price * $qty,
        ]);
    }

    private function voucher(): Promo
    {
        return Promo::where('code', HalloweenPromoSeeder::CODE)->firstOrFail();
    }

    private function preview(array $payload = []): array
    {
        $r = $this->actingAs($this->cashier)->postJson(route('transactions.pricing-preview'), $payload);
        $r->assertOk();

        return $r->json('data');
    }

    public function test_seeder_creates_the_halloween_promo_and_is_rerunnable(): void
    {
        (new HalloweenPromoSeeder)->run(); // dijalankan ulang: tidak boleh dobel

        $this->assertSame(1, Promo::where('code', 'HALLOWEEN20K')->count());
        $p = $this->voucher();
        $this->assertSame(Promo::ACTIVATION_MANUAL, $p->activation_mode);
        $this->assertSame(Promo::TYPE_FIXED_PRICE, $p->discount_type);
        $this->assertSame(20000, (int) $p->discount_value);
        $this->assertSame(Promo::TARGET_CATEGORY, $p->target_type);
        $this->assertSame($this->photobox->id, $p->category_id);
        $this->assertSame(1, (int) $p->applies_to_qty);
        $this->assertTrue($p->starts_at->equalTo(Carbon::create(2026, 10, 30, 0, 0, 0, 'Asia/Jakarta')));
        $this->assertTrue($p->ends_at->equalTo(Carbon::create(2026, 11, 1, 23, 59, 59, 'Asia/Jakarta')));
    }

    public function test_one_use_with_two_studios_discounts_only_the_cheapest_package(): void
    {
        $this->cart($this->product($this->photobox, 'Jason SnapBox', 30000));
        $this->cart($this->product($this->photobox, 'Record Room', 35000));

        $data = $this->preview(['customer_voucher_ids' => [$this->voucher()->id]]);

        $this->assertSame(10000, (int) $data['summary']['voucher_discount_total']); // 30K -> 20K
        $this->assertSame(55000, (int) $data['summary']['grand_total']);
    }

    public function test_two_uses_discount_both_studios_to_twenty_thousand_each(): void
    {
        $this->cart($this->product($this->photobox, 'Jason SnapBox', 30000));
        $this->cart($this->product($this->photobox, 'Record Room', 35000));
        $id = $this->voucher()->id;

        $data = $this->preview(['customer_voucher_ids' => [$id, $id]]);

        $this->assertSame(25000, (int) $data['summary']['voucher_discount_total']); // 10K + 15K
        $this->assertSame(40000, (int) $data['summary']['grand_total']);
    }

    public function test_one_use_on_two_units_of_the_same_studio_discounts_one_unit(): void
    {
        $this->cart($this->product($this->photobox, 'Jason SnapBox', 30000), 2);

        $data = $this->preview(['customer_voucher_ids' => [$this->voucher()->id]]);

        $this->assertSame(10000, (int) $data['summary']['voucher_discount_total']);
        $this->assertSame(50000, (int) $data['summary']['grand_total']);
    }

    public function test_two_uses_on_two_units_of_the_same_studio_discount_both_units(): void
    {
        $this->cart($this->product($this->photobox, 'Jason SnapBox', 30000), 2);
        $id = $this->voucher()->id;

        $data = $this->preview(['customer_voucher_ids' => [$id, $id]]);

        // Pemakaian ke-2 tidak boleh menghitung ulang dari harga yang sudah turun (bug proporsional lama).
        $this->assertSame(20000, (int) $data['summary']['voucher_discount_total']);
        $this->assertSame(40000, (int) $data['summary']['grand_total']);
    }

    public function test_extra_uses_never_push_a_package_below_twenty_thousand(): void
    {
        $this->cart($this->product($this->photobox, 'Jason SnapBox', 30000));
        $id = $this->voucher()->id;

        $data = $this->preview(['customer_voucher_ids' => [$id, $id, $id]]);

        $this->assertSame(10000, (int) $data['summary']['voucher_discount_total']);
        $this->assertSame(20000, (int) $data['summary']['grand_total']);
    }

    public function test_non_photobox_items_are_never_touched(): void
    {
        $this->cart($this->product($this->photobox, 'Jason SnapBox', 30000));
        $frame = $this->cart($this->product($this->frames, 'Frame 4R', 15000));
        $id = $this->voucher()->id;

        $data = $this->preview(['customer_voucher_ids' => [$id, $id]]);

        $this->assertSame(10000, (int) $data['summary']['voucher_discount_total']);
        $this->assertArrayNotHasKey($frame->id, $data['summary']['voucher_discount_by_cart_id']);
        $this->assertSame(35000, (int) $data['summary']['grand_total']);
    }

    public function test_cashier_can_pin_the_voucher_to_the_pricier_studio(): void
    {
        $cheap = $this->cart($this->product($this->photobox, 'Jason SnapBox', 30000));
        $pricier = $this->cart($this->product($this->photobox, 'Record Room', 35000));

        $data = $this->preview(['item_vouchers' => [['cart_id' => $pricier->id, 'promo_id' => $this->voucher()->id]]]);

        $this->assertSame(15000, (int) $data['summary']['voucher_discount_total']); // 35K -> 20K, bukan yang termurah
        $this->assertSame(15000, (int) $data['summary']['voucher_discount_by_cart_id'][$pricier->id]);
        $this->assertArrayNotHasKey($cheap->id, $data['summary']['voucher_discount_by_cart_id']);
        $this->assertSame(50000, (int) $data['summary']['grand_total']);
    }

    public function test_pinning_to_a_non_photobox_item_gives_no_discount(): void
    {
        $this->cart($this->product($this->photobox, 'Jason SnapBox', 30000));
        $frame = $this->cart($this->product($this->frames, 'Frame 4R', 15000));

        $data = $this->preview(['item_vouchers' => [['cart_id' => $frame->id, 'promo_id' => $this->voucher()->id]]]);

        $this->assertSame(0, (int) $data['summary']['voucher_discount_total']);
        $this->assertSame(45000, (int) $data['summary']['grand_total']);
    }

    public function test_pinning_to_someone_elses_cart_line_is_ignored(): void
    {
        $this->cart($this->product($this->photobox, 'Jason SnapBox', 30000));
        $other = User::factory()->create();
        $foreign = Cart::create([
            'cashier_id' => $other->id, 'product_id' => $this->product($this->photobox, 'Lain', 35000)->id,
            'qty' => 1, 'price' => 35000,
        ]);

        $data = $this->preview(['item_vouchers' => [['cart_id' => $foreign->id, 'promo_id' => $this->voucher()->id]]]);

        $this->assertSame(0, (int) $data['summary']['voucher_discount_total']);
    }

    public function test_pinned_voucher_and_manual_item_discount_stack_on_the_same_line(): void
    {
        $line = $this->cart($this->product($this->photobox, 'Record Room', 35000));

        $data = $this->preview([
            'item_vouchers' => [['cart_id' => $line->id, 'promo_id' => $this->voucher()->id]],
            'item_discounts' => [['cart_id' => $line->id, 'mode' => 'nominal', 'value' => 5000]],
        ]);

        $this->assertSame(15000, (int) $data['summary']['voucher_discount_total']);
        $this->assertSame(5000, (int) $data['summary']['item_manual_discount_total']);
        $this->assertSame(15000, (int) $data['summary']['grand_total']); // 35K - 15K - 5K
    }

    public function test_checkout_with_a_pinned_voucher_saves_the_discount_on_the_chosen_line(): void
    {
        $cheapProduct = $this->product($this->photobox, 'Jason SnapBox', 30000);
        $pricierProduct = $this->product($this->photobox, 'Record Room', 35000);
        $this->cart($cheapProduct);
        $pricier = $this->cart($pricierProduct);
        $promo = $this->voucher();

        $response = $this->actingAs($this->cashier)->post(route('transactions.store'), [
            'item_vouchers' => [['cart_id' => $pricier->id, 'promo_id' => $promo->id]],
            'shipping_cost' => 0,
            'grand_total' => 999999, // diabaikan: server menghitung ulang
            'cash' => 60000,
            'change' => 0,
        ]);

        $transaction = \App\Models\Transaction::with('details')->latest('id')->first();
        $response->assertRedirect(route('transactions.print', $transaction->invoice));

        $this->assertSame(50000, (int) $transaction->grand_total); // 30K + 20K
        $this->assertSame(1, (int) $promo->fresh()->used_count);

        $byProduct = $transaction->details->keyBy('product_id');
        $this->assertSame(15000, (int) $byProduct[$pricierProduct->id]->discount_total);
        $this->assertSame(0, (int) $byProduct[$cheapProduct->id]->discount_total);
        $this->assertSame($promo->id, (int) $byProduct[$pricierProduct->id]->promo_id);
    }

    public function test_checkout_ignores_a_pinned_voucher_when_the_promo_period_is_over(): void
    {
        $line = $this->cart($this->product($this->photobox, 'Record Room', 35000));
        Carbon::setTestNow(Carbon::create(2026, 11, 2, 9, 0, 0, 'Asia/Jakarta'));
        \App\Models\CashierShift::where('user_id', $this->cashier->id)->update(['opened_at' => now()]);

        $this->actingAs($this->cashier)->post(route('transactions.store'), [
            'item_vouchers' => [['cart_id' => $line->id, 'promo_id' => $this->voucher()->id]],
            'shipping_cost' => 0, 'cash' => 35000, 'change' => 0,
        ]);

        $transaction = \App\Models\Transaction::latest('id')->first();
        $this->assertSame(35000, (int) $transaction->grand_total); // kode kedaluwarsa: harga normal
        $this->assertSame(0, (int) $this->voucher()->used_count);
    }

    public function test_pinned_voucher_consumes_the_quota(): void
    {
        $promo = $this->voucher();
        $promo->update(['max_uses' => 1]);

        $first = $this->cart($this->product($this->photobox, 'Record Room', 35000));
        $this->actingAs($this->cashier)->post(route('transactions.store'), [
            'item_vouchers' => [['cart_id' => $first->id, 'promo_id' => $promo->id]],
            'shipping_cost' => 0, 'cash' => 20000, 'change' => 0,
        ]);

        $promo->refresh();
        $this->assertSame(1, (int) $promo->used_count);
        $this->assertTrue((bool) $promo->is_used);

        // Kuota habis: transaksi berikutnya tidak boleh mendapat potongan lagi.
        $second = $this->cart($this->product($this->photobox, 'Jason SnapBox', 30000));
        $data = $this->preview(['item_vouchers' => [['cart_id' => $second->id, 'promo_id' => $promo->id]]]);
        $this->assertSame(0, (int) $data['summary']['voucher_discount_total']);
    }

    public function test_code_only_works_inside_the_promo_period(): void
    {
        $this->cart($this->product($this->photobox, 'Jason SnapBox', 30000));
        $id = $this->voucher()->id;

        foreach ([
            [Carbon::create(2026, 10, 29, 23, 59, 0, 'Asia/Jakarta'), 0],       // sehari sebelum mulai
            [Carbon::create(2026, 10, 30, 0, 1, 0, 'Asia/Jakarta'), 10000],      // hari pertama
            [Carbon::create(2026, 11, 1, 23, 58, 0, 'Asia/Jakarta'), 10000],    // menit-menit terakhir
            [Carbon::create(2026, 11, 2, 0, 1, 0, 'Asia/Jakarta'), 0],           // sudah lewat
        ] as [$now, $expected]) {
            Carbon::setTestNow($now);
            $this->flushSession(); // loncat waktu berhari-hari: jangan kena batas sesi login (idle timeout)
            // shift kasir ikut "dibuka" pada waktu simulasi, supaya yang diuji murni masa berlaku kode
            \App\Models\CashierShift::where('user_id', $this->cashier->id)->update(['opened_at' => $now]);
            $data = $this->preview(['customer_voucher_ids' => [$id]]);
            $this->assertSame($expected, (int) $data['summary']['voucher_discount_total'], 'pada '.$now->toDateTimeString());
        }
    }
}
