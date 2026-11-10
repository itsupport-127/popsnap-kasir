<?php

namespace Tests\Feature\Photobox;

use App\Models\PhotoboxStudio;
use App\Models\PhotoboxTransaction;
use App\Services\PhotoboxDiscountService;
use App\Models\Promo;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PhotoboxDiscountTest extends TestCase
{
    /** Migrasi lama proyek memakai sintaks khusus MySQL, jadi hanya tabel yang dibutuhkan yang dimigrasi di SQLite memori. */
    protected function setUp(): void
    {
        parent::setUp();
        // tabel induk minimal untuk foreign key di tabel promos (migrasi aslinya khusus MySQL)
        foreach (['users', 'products', 'categories', 'customers', 'transactions'] as $t) {
            Schema::create($t, fn ($b) => $b->id());
        }
        foreach ([
            '2026_10_22_000100_create_photobox_tables', '2026_10_26_000100_add_soft_delete_and_edit_to_photobox_transactions',
            '2026_10_27_000100_add_gallery_url_to_photobox_transactions', '2026_11_03_000100_add_sync_report_to_photobox_studios',
            '2026_11_09_000200_add_is_test_to_photobox_transactions_table',
            '2026_06_29_000100_create_promos_table', '2026_08_01_000100_add_payment_method_restriction_to_promos_table',
            '2026_08_01_000200_add_applies_to_qty_to_promos_table', '2026_08_02_000100_add_allow_zero_price_to_promos_table',
            '2026_08_02_000200_add_discount_scope_to_promos_table', '2026_08_02_000200_add_usage_limit_mode_to_promos_table',
        ] as $m) {
            $this->artisan('migrate', ['--path' => "database/migrations/{$m}.php", '--force' => true]);
        }
    }

    private function token(): string
    {
        $studio = new PhotoboxStudio(['name' => 'Studio Emma', 'is_active' => true, 'token_hash' => bin2hex(random_bytes(32))]);
        $studio->save();

        return $studio->issueToken();
    }

    private function promo(array $o = []): Promo
    {
        return Promo::create(array_merge([
            'name' => 'Promo', 'is_active' => true, 'priority' => 0, 'activation_mode' => 'auto', 'discount_kind' => 'standard_discount',
            'discount_type' => 'fixed_amount', 'discount_value' => 2000, 'target_type' => 'all', 'customer_scope' => 'all', 'minimum_order' => 0,
        ], $o));
    }

    private function quote(string $token, int $amount, ?string $code = null)
    {
        return $this->withToken($token)->postJson('/api/photobox/discount/quote', array_filter(['amount' => $amount, 'code' => $code], fn ($v) => $v !== null));
    }

    public function test_requires_valid_token(): void
    {
        $this->postJson('/api/photobox/discount/quote', ['amount' => 35000])->assertStatus(401);
        $this->withToken('salah')->postJson('/api/photobox/discount/quote', ['amount' => 35000])->assertStatus(401);
    }

    public function test_no_promo_means_no_discount(): void
    {
        $r = $this->quote($this->token(), 35000)->assertOk()->json();
        $this->assertTrue($r['ok']);
        $this->assertSame(35000, $r['final']);
        $this->assertSame(0, $r['discount']);
    }

    public function test_best_auto_promo_wins_and_only_one_applies(): void
    {
        $this->promo(['name' => 'Kecil', 'discount_value' => 1000]);
        $this->promo(['name' => 'Persen 10', 'discount_type' => 'percentage', 'discount_value' => 10]); // 3.500
        $this->promo(['name' => 'Besar', 'discount_value' => 2500]);
        $r = $this->quote($this->token(), 35000)->assertOk()->json();
        $this->assertSame('Persen 10', $r['auto']['name']);
        $this->assertSame(3500, $r['discount']);
        $this->assertSame(31500, $r['final']);
    }

    public function test_ineligible_promos_are_skipped(): void
    {
        $this->promo(['name' => 'Per produk', 'target_type' => 'product', 'product_id' => null]);
        $this->promo(['name' => 'Member', 'customer_scope' => 'loyalty_member']);
        $this->promo(['name' => 'Tunai saja', 'payment_method_restriction' => 'cash_only']);
        $this->promo(['name' => 'Min 100rb', 'minimum_order' => 100000]);
        $this->promo(['name' => 'Mati', 'is_active' => false]);
        $this->promo(['name' => 'Kadaluarsa', 'ends_at' => now()->subDay()]);
        $this->promo(['name' => 'Belum mulai', 'starts_at' => now()->addDay()]);
        $this->promo(['name' => 'Bundle', 'discount_kind' => 'bundle_price']);
        $r = $this->quote($this->token(), 35000)->assertOk()->json();
        $this->assertSame(0, $r['discount'], json_encode($r));
        $this->assertNull($r['auto']);
    }

    public function test_manual_code_stacks_on_auto_and_is_case_insensitive(): void
    {
        $this->promo(['name' => 'Pagi', 'discount_value' => 2000]);
        $this->promo(['name' => 'Hemat 10%', 'activation_mode' => 'manual', 'code' => 'HEMAT10', 'discount_type' => 'percentage', 'discount_value' => 10]);
        $r = $this->quote($this->token(), 35000, 'hemat10')->assertOk()->json();
        $this->assertTrue($r['ok']);
        $this->assertSame(2000, $r['auto']['discount']);
        $this->assertSame(3300, $r['code']['discount']); // 10% dari 33.000 (setelah promo otomatis)
        $this->assertSame(5300, $r['discount']);
        $this->assertSame(29700, $r['final']);
    }

    public function test_bad_code_reports_reason_and_never_discounts_silently(): void
    {
        $this->promo(['name' => 'Pagi']);
        $t = $this->token();
        $r = $this->quote($t, 35000, 'NGAWUR')->assertOk()->json();
        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('tidak ditemukan', $r['error']);
        $this->promo(['activation_mode' => 'manual', 'code' => 'HABIS', 'max_uses' => 1, 'used_count' => 1]);
        $this->assertStringContainsString('Kuota', $this->quote($t, 35000, 'HABIS')->json('error'));
        $this->promo(['activation_mode' => 'manual', 'code' => 'MINIM', 'minimum_order' => 90000]);
        $this->assertFalse($this->quote($t, 35000, 'MINIM')->json('ok'));
        $this->promo(['activation_mode' => 'manual', 'code' => 'PRIBADI', 'customer_id' => null, 'discount_kind' => 'buy_x_get_y']);
        $this->assertFalse($this->quote($t, 35000, 'PRIBADI')->json('ok'));
    }

    public function test_final_price_never_goes_below_qris_minimum(): void
    {
        $this->promo(['name' => 'Gila', 'discount_value' => 99999]);
        $r = $this->quote($this->token(), 35000)->assertOk()->json();
        $this->assertSame(1000, $r['final']);
        $this->assertTrue($r['capped']);
        $this->assertSame(34000, $r['discount']);
        $this->promo(['activation_mode' => 'manual', 'code' => 'FULL', 'discount_type' => 'percentage', 'discount_value' => 100]);
        $r = $this->quote($this->token(), 35000, 'FULL')->assertOk()->json();
        $this->assertGreaterThanOrEqual(1000, $r['final']);
        $this->assertSame($r['gross'] - $r['discount'], $r['final']);
    }

    public function test_quote_does_not_consume_quota_but_paid_sync_does_once(): void
    {
        $p = $this->promo(['activation_mode' => 'manual', 'code' => 'SATU', 'max_uses' => 2, 'discount_value' => 5000]);
        $t = $this->token();
        $this->quote($t, 35000, 'SATU')->assertOk();
        $this->assertSame(0, (int) $p->fresh()->used_count, 'cek harga tidak boleh memakai kuota');

        $row = ['order_no' => 'PB-EMMA-261109-0001', 'template_id' => 't1', 'mode' => 'photo', 'method' => 'qris', 'amount' => 30000, 'status' => 'pending',
            'meta' => ['gross_amount' => 35000, 'discount' => 5000, 'promo_code' => 'SATU', 'promo_name' => 'Hemat']];
        $this->withToken($t)->postJson('/api/photobox/transactions', ['transactions' => [$row]])->assertOk();
        $this->assertSame(0, (int) $p->fresh()->used_count, 'belum lunas = belum terpakai');

        $paid = array_merge($row, ['status' => 'paid', 'paid_at' => now()->toIso8601String()]);
        $this->withToken($t)->postJson('/api/photobox/transactions', ['transactions' => [$paid]])->assertOk();
        $this->assertSame(1, (int) $p->fresh()->used_count);
        $this->withToken($t)->postJson('/api/photobox/transactions', ['transactions' => [$paid]])->assertOk(); // kirim ulang
        $this->assertSame(1, (int) $p->fresh()->used_count, 'kirim ulang tidak boleh menambah lagi');
        $tx = PhotoboxTransaction::where('order_no', 'PB-EMMA-261109-0001')->first();
        $this->assertSame(30000, (int) $tx->amount);
        $this->assertSame(5000, $tx->meta['discount']);
        $this->assertTrue($tx->meta['promo_redeemed']);

        // kuota tidak pernah melewati batas walau 2 transaksi lain lunas
        foreach (['0002', '0003'] as $n) {
            $this->withToken($t)->postJson('/api/photobox/transactions', ['transactions' => [array_merge($paid, ['order_no' => "PB-EMMA-261109-{$n}"])]])->assertOk();
        }
        $this->assertSame(2, (int) $p->fresh()->used_count, 'maks_uses=2 tidak boleh terlewati');
    }

    public function test_quota_is_only_marked_counted_when_it_really_increased_and_can_be_released(): void
    {
        $p = $this->promo(['activation_mode' => 'manual', 'code' => 'DUA', 'max_uses' => 1, 'discount_value' => 5000]);
        $t = $this->token();
        $row = fn (string $no) => ['order_no' => $no, 'template_id' => 't1', 'mode' => 'photo', 'method' => 'qris', 'amount' => 30000, 'status' => 'paid',
            'paid_at' => now()->toIso8601String(), 'meta' => ['gross_amount' => 35000, 'discount' => 5000, 'promo_code' => 'DUA', 'promo_name' => 'Hemat']];

        $this->withToken($t)->postJson('/api/photobox/transactions', ['transactions' => [$row('PB-EMMA-261109-1001')]])->assertOk();
        $this->withToken($t)->postJson('/api/photobox/transactions', ['transactions' => [$row('PB-EMMA-261109-1002')]])->assertOk(); // kuota 1 sudah habis
        $this->assertSame(1, (int) $p->fresh()->used_count);
        $this->assertTrue(PhotoboxTransaction::where('order_no', 'PB-EMMA-261109-1001')->first()->meta['promo_counted']);
        $second = PhotoboxTransaction::where('order_no', 'PB-EMMA-261109-1002')->first();
        $this->assertTrue($second->meta['promo_redeemed']);
        $this->assertFalse($second->meta['promo_counted'], 'kuota habis: tidak pernah menambah hitungan, jadi tidak boleh dikembalikan nanti');

        $svc = app(PhotoboxDiscountService::class);
        $this->assertTrue($svc->release('dua'));
        $this->assertSame(0, (int) $p->fresh()->used_count);
        $this->assertFalse($svc->release('DUA'), 'tidak boleh di bawah 0');
        $this->assertSame(0, (int) $p->fresh()->used_count);
    }
}
