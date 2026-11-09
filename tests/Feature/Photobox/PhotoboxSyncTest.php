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
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_10_26_000100_add_soft_delete_and_edit_to_photobox_transactions.php', '--force' => true]);
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_10_27_000100_add_gallery_url_to_photobox_transactions.php', '--force' => true]);
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_11_03_000100_add_sync_report_to_photobox_studios.php', '--force' => true]);
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

    public function test_test_transactions_from_simulation_or_sandbox_are_ignored(): void
    {
        [$studio, $token] = $this->studio();

        $res = $this->withToken($token)->postJson('/api/photobox/transactions', ['transactions' => [
            $this->row(['order_no' => 'PB-EMMA-261109-0001', 'is_test' => true]),
            $this->row(['order_no' => 'PB-EMMA-261109-0002']),
        ]])->assertOk();

        $res->assertJsonPath('results.0.ok', true)->assertJsonPath('results.0.kept', 'test');
        $this->assertDatabaseMissing('photobox_transactions', ['order_no' => 'PB-EMMA-261109-0001']);
        $this->assertDatabaseHas('photobox_transactions', ['order_no' => 'PB-EMMA-261109-0002', 'amount' => 35000]);
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

    public function test_deleted_transaction_is_not_counted_and_not_resurrected_by_resync(): void
    {
        [, $token] = $this->studio();
        $this->withToken($token)->postJson('/api/photobox/transactions', ['transactions' => [$this->row()]])->assertOk();
        [$s, $e] = [now()->startOfDay(), now()->endOfDay()];
        $this->assertSame(35000, PhotoboxStats::summary($s, $e)['revenue']);

        PhotoboxTransaction::first()->delete();
        $this->assertSame(0, PhotoboxStats::summary($s, $e)['revenue']);

        // booth mengirim ulang order_no yang sama -> tetap terhapus, tidak dobel
        $this->withToken($token)->postJson('/api/photobox/transactions', ['transactions' => [$this->row()]])
            ->assertOk()->assertJsonPath('results.0.kept', 'deleted');
        $this->assertSame(0, PhotoboxTransaction::count());
        $this->assertSame(1, PhotoboxTransaction::withTrashed()->count());
        $this->assertSame(0, PhotoboxStats::summary($s, $e)['revenue']);
    }

    public function test_gallery_url_is_stored_kept_and_refreshed_even_after_edit(): void
    {
        [, $token] = $this->studio();
        $url = 'https://foto.example.com/s/K7M29QXH4B';
        $this->withToken($token)->postJson('/api/photobox/transactions', ['transactions' => [$this->row(['gallery_url' => $url])]])->assertOk();
        $this->assertSame($url, PhotoboxTransaction::first()->gallery_url);

        // kiriman berikutnya tanpa link tidak menghapus link yang sudah ada
        $this->withToken($token)->postJson('/api/photobox/transactions', ['transactions' => [$this->row()]])->assertOk();
        $this->assertSame($url, PhotoboxTransaction::first()->gallery_url);

        // sudah diedit di kasir: data tetap milik kasir, tapi link galeri tetap bisa diperbarui
        PhotoboxTransaction::first()->forceFill(['amount' => 20000, 'edited_at' => now()])->save();
        $new = 'https://foto.example.com/s/ZZZZ29QXH4';
        $this->withToken($token)->postJson('/api/photobox/transactions', ['transactions' => [$this->row(['gallery_url' => $new])]])
            ->assertOk()->assertJsonPath('results.0.kept', 'edited');
        $this->assertSame($new, PhotoboxTransaction::first()->gallery_url);
        $this->assertSame(20000, (int) PhotoboxTransaction::first()->amount);
    }

    public function test_gallery_url_must_be_http_or_https(): void
    {
        [, $token] = $this->studio();
        $this->withToken($token)->postJson('/api/photobox/transactions', ['transactions' => [$this->row(['gallery_url' => 'javascript:alert(1)'])]])
            ->assertOk()->assertJsonPath('results.0.ok', false);
        $this->assertSame(0, PhotoboxTransaction::count());
    }

    public function test_manually_edited_transaction_is_not_overwritten_by_resync(): void
    {
        [, $token] = $this->studio();
        $this->withToken($token)->postJson('/api/photobox/transactions', ['transactions' => [$this->row()]])->assertOk();
        PhotoboxTransaction::first()->forceFill(['amount' => 20000, 'edited_at' => now()])->save();

        $this->withToken($token)->postJson('/api/photobox/transactions', ['transactions' => [$this->row()]])
            ->assertOk()->assertJsonPath('results.0.kept', 'edited');
        $this->assertSame(20000, (int) PhotoboxTransaction::first()->amount);
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

    public function test_heartbeat_stores_report_and_summarizes_status(): void
    {
        [$studio, $token] = $this->studio();

        $this->postJson('/api/photobox/heartbeat', ['pending' => 1])->assertStatus(401);

        $this->withToken($token)->postJson('/api/photobox/heartbeat', [
            'pending' => 0, 'errors' => 0, 'galleryPending' => 0, 'galleryErrors' => 0, 'diskFreeGB' => 120.44, 'diskLevel' => 'ok', 'app' => 'photobox',
        ])->assertOk()->assertJson(['ok' => true]);
        $studio->refresh();
        $this->assertNotNull($studio->report_at);
        $this->assertSame('ok', $studio->reportSummary()['state']);
        $this->assertSame(120.4, $studio->sync_report['diskFreeGB']);

        $this->withToken($token)->postJson('/api/photobox/heartbeat', ['pending' => 2, 'galleryPending' => 3])->assertOk();
        $this->assertSame('queue', $studio->refresh()->reportSummary()['state']);

        $this->withToken($token)->postJson('/api/photobox/heartbeat', ['errors' => 1, 'lastError' => str_repeat('x', 900)])->assertOk();
        $studio->refresh();
        $this->assertSame('warn', $studio->reportSummary()['state']);
        $this->assertSame(300, mb_strlen($studio->sync_report['lastError']));

        $this->withToken($token)->postJson('/api/photobox/heartbeat', ['galleryErrors' => 2, 'galleryLastError' => 'Bucket 403'])->assertOk();
        $this->assertStringContainsString('Bucket 403', implode(' ', $studio->refresh()->reportSummary()['notes']));

        $this->withToken($token)->postJson('/api/photobox/heartbeat', ['diskLevel' => 'critical', 'diskFreeGB' => 0.5, 'errors' => -5, 'pending' => 'abc'])->assertOk();
        $studio->refresh();
        $this->assertSame('bad', $studio->reportSummary()['state']);
        $this->assertSame(0, $studio->sync_report['errors']);
        $this->assertSame(0, $studio->sync_report['pending']);

        $this->withToken($token)->postJson('/api/photobox/heartbeat', ['diskLevel' => '<script>'])->assertOk();
        $this->assertSame('ok', $studio->refresh()->sync_report['diskLevel']);
    }

    public function test_status_offline_unknown_and_inactive(): void
    {
        [$studio, $token] = $this->studio();
        $this->assertSame('unknown', $studio->reportSummary()['state']); // belum pernah terhubung

        $this->withToken($token)->getJson('/api/photobox/ping')->assertOk(); // online tapi belum kirim laporan (app lama)
        $this->assertSame('unknown', $studio->refresh()->reportSummary()['state']);
        $this->assertStringContainsString('perlu diperbarui', implode(' ', $studio->reportSummary()['notes']));

        $this->withToken($token)->postJson('/api/photobox/heartbeat', ['pending' => 0])->assertOk();
        Carbon::setTestNow(now()->addMinutes(6));
        $this->assertSame('offline', $studio->refresh()->reportSummary()['state']);
        Carbon::setTestNow();

        $studio->update(['is_active' => false]);
        $this->withToken($token)->postJson('/api/photobox/heartbeat', ['pending' => 0])->assertStatus(401);
    }
}
