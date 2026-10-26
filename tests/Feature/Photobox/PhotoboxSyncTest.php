<?php

namespace Tests\Feature\Photobox;

use App\Models\PhotoboxStudio;
use App\Models\PhotoboxTransaction;
use App\Services\PhotoboxStats;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class PhotoboxSyncTest extends TestCase
{
    /** Migrasi lama proyek memakai sintaks khusus MySQL, jadi tes ini hanya menjalankan migrasi tabel Photobox di SQLite memori. */
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_10_22_000100_create_photobox_tables.php', '--force' => true]);
    }

    private function studio(string $name = 'Studio Emma'): array
    {
        $studio = new PhotoboxStudio(['name' => $name, 'is_active' => true, 'token_hash' => bin2hex(random_bytes(32))]);
        $studio->save();

        return [$studio, $studio->issueToken()];
    }

    private function row(array $over = []): array
    {
        return array_merge([
            'order_no' => 'PB-EMMA-261022-0001', 'template_id' => 't1', 'template_name' => 'Emma Room', 'category' => 'PhotoBox',
            'mode' => 'photo', 'method' => 'qris', 'amount' => 35000, 'status' => 'paid',
            'paid_at' => now()->toIso8601String(), 'created_at' => now()->toIso8601String(), 'session_id' => 'PB-EMMA-261022-0001',
        ], $over);
    }

    public function test_rejects_missing_or_wrong_token(): void
    {
        $this->postJson('/api/photobox/transactions', ['transactions' => [$this->row()]])->assertStatus(401);
        $this->withToken('pbx_salah')->getJson('/api/photobox/ping')->assertStatus(401);
    }

    public function test_ping_and_sync_store_transactions_with_same_order_number(): void
    {
        [$studio, $token] = $this->studio();
        $this->withToken($token)->getJson('/api/photobox/ping')->assertOk()->assertJson(['ok' => true, 'studio' => 'Studio Emma']);

        $res = $this->withToken($token)->postJson('/api/photobox/transactions', ['transactions' => [
            $this->row(),
            $this->row(['order_no' => 'PB-EMMA-261022-0002', 'method' => 'voucher', 'amount' => 0, 'voucher_code' => 'abc12345', 'meta' => ['kasir_reference' => 'TRX-9']]),
        ]]);
        $res->assertOk()->assertJsonPath('results.0.ok', true)->assertJsonPath('results.1.ok', true);

        $this->assertDatabaseHas('photobox_transactions', ['order_no' => 'PB-EMMA-261022-0001', 'studio_id' => $studio->id, 'amount' => 35000, 'status' => 'paid']);
        $this->assertSame('ABC12345', PhotoboxTransaction::where('order_no', 'PB-EMMA-261022-0002')->value('voucher_code'));
    }

    public function test_resending_is_idempotent_and_paid_status_never_goes_back(): void
    {
        [, $token] = $this->studio();
        $this->withToken($token)->postJson('/api/photobox/transactions', ['transactions' => [$this->row()]])->assertOk();
        $this->withToken($token)->postJson('/api/photobox/transactions', ['transactions' => [$this->row()]])->assertOk();
        $this->withToken($token)->postJson('/api/photobox/transactions', ['transactions' => [$this->row(['status' => 'pending'])]])
            ->assertOk()->assertJsonPath('results.0.kept', 'paid');

        $this->assertSame(1, PhotoboxTransaction::count());
        $this->assertSame('paid', PhotoboxTransaction::first()->status);
    }

    public function test_order_number_cannot_be_taken_over_by_another_studio(): void
    {
        [, $tokenA] = $this->studio('A');
        [, $tokenB] = $this->studio('B');
        $this->withToken($tokenA)->postJson('/api/photobox/transactions', ['transactions' => [$this->row()]])->assertOk();
        $this->withToken($tokenB)->postJson('/api/photobox/transactions', ['transactions' => [$this->row(['amount' => 1])]])
            ->assertOk()->assertJsonPath('results.0.ok', false);

        $this->assertSame(35000, (int) PhotoboxTransaction::first()->amount);
    }

    public function test_invalid_rows_are_reported_but_do_not_block_valid_ones(): void
    {
        [, $token] = $this->studio();
        $res = $this->withToken($token)->postJson('/api/photobox/transactions', ['transactions' => [
            $this->row(['method' => 'cash']),
            $this->row(['order_no' => 'PB-EMMA-261022-0003']),
        ]]);
        $res->assertOk()->assertJsonPath('results.0.ok', false)->assertJsonPath('results.1.ok', true);
        $this->assertSame(1, PhotoboxTransaction::count());
    }

    public function test_inactive_studio_token_is_rejected(): void
    {
        [$studio, $token] = $this->studio();
        $studio->update(['is_active' => false]);
        $this->withToken($token)->getJson('/api/photobox/ping')->assertStatus(401);
    }

    public function test_stats_count_only_paid_and_group_per_studio_and_period(): void
    {
        [, $tokenA] = $this->studio('A');
        [, $tokenB] = $this->studio('B');
        $this->withToken($tokenA)->postJson('/api/photobox/transactions', ['transactions' => [
            $this->row(['order_no' => 'PB-A-1']),
            $this->row(['order_no' => 'PB-A-2', 'method' => 'voucher', 'amount' => 0]),
            $this->row(['order_no' => 'PB-A-3', 'status' => 'pending', 'paid_at' => null]),
            $this->row(['order_no' => 'PB-A-4', 'paid_at' => now()->subYear()->toIso8601String()]),
        ]])->assertOk();
        $this->withToken($tokenB)->postJson('/api/photobox/transactions', ['transactions' => [$this->row(['order_no' => 'PB-B-1', 'amount' => 20000])]])->assertOk();

        [$s, $e] = PhotoboxStats::range('today');
        $today = PhotoboxStats::summary($s, $e);
        $this->assertSame(55000, $today['revenue']);
        $this->assertSame(3, $today['sessions']); // pending & tahun lalu tidak dihitung
        $this->assertCount(2, $today['byStudio']);

        [$s, $e] = PhotoboxStats::range('year');
        $this->assertSame(3, PhotoboxStats::summary($s, $e)['sessions']);
        [$s, $e] = PhotoboxStats::range('all');
        $this->assertSame(4, PhotoboxStats::summary($s, $e)['sessions']);
    }

    public function test_transactions_count_excludes_zero_value_voucher_sessions(): void
    {
        [, $token] = $this->studio();
        $this->withToken($token)->postJson('/api/photobox/transactions', ['transactions' => [
            $this->row(['order_no' => 'PB-T-1']),
            $this->row(['order_no' => 'PB-T-2', 'method' => 'voucher', 'amount' => 0]),
            $this->row(['order_no' => 'PB-T-3', 'method' => 'free', 'amount' => 0]),
        ]])->assertOk();

        [$s, $e] = PhotoboxStats::range('today');
        $sum = PhotoboxStats::summary($s, $e);
        $this->assertSame(3, $sum['sessions']);
        $this->assertSame(1, $sum['transactions']); // hanya yang membayar di booth
        $this->assertSame(1, $sum['byStudio'][0]['transactions']);
    }

    public function test_trend_buckets_match_dashboard_keys_and_merge_in_time_order(): void
    {
        [, $token] = $this->studio();
        $day = Carbon::parse('2026-10-10 09:30:00', config('app.timezone'));
        $this->withToken($token)->postJson('/api/photobox/transactions', ['transactions' => [
            $this->row(['order_no' => 'PB-D-1', 'amount' => 10000, 'paid_at' => $day->toIso8601String()]),
            $this->row(['order_no' => 'PB-D-2', 'amount' => 5000, 'paid_at' => $day->copy()->addHour()->toIso8601String()]),
            $this->row(['order_no' => 'PB-D-3', 'amount' => 7000, 'paid_at' => $day->copy()->addDays(2)->toIso8601String()]),
        ]])->assertOk();

        $start = Carbon::parse('2026-10-01')->startOfDay();
        $end = Carbon::parse('2026-10-31')->endOfDay();
        $photo = PhotoboxStats::trend('month', $start, $end);
        $this->assertSame(15000, $photo['2026-10-10']['total']);
        $this->assertNotEmpty($photo['2026-10-10']['label']);
        $this->assertSame(7000, $photo['2026-10-12']['total']);

        $year = PhotoboxStats::trend('year', Carbon::parse('2026-01-01'), Carbon::parse('2026-12-31')->endOfDay());
        $this->assertSame(22000, $year['2026-10']['total']);
        $today = PhotoboxStats::trend('today', $day->copy()->startOfDay(), $day->copy()->endOfDay());
        $this->assertSame(10000, $today['9:00']['total']);
        $this->assertSame(5000, $today['10:00']['total']);

        $merge = new \ReflectionMethod(\App\Http\Controllers\DashboardController::class, 'mergeTrend');
        $merge->setAccessible(true);
        $store = [['date' => '2026-10-10', 'label' => '10 Okt', 'total' => 20000], ['date' => '2026-10-11', 'label' => '11 Okt', 'total' => 3000]];
        $out = $merge->invoke(new \App\Http\Controllers\DashboardController(), $store, $photo, 'month');
        $this->assertSame(['2026-10-10', '2026-10-11', '2026-10-12'], array_column($out, 'date'));
        $this->assertSame([35000, 3000, 7000], array_column($out, 'total'));

        $this->assertSame($store, $merge->invoke(new \App\Http\Controllers\DashboardController(), $store, [], 'month'));
    }
}
