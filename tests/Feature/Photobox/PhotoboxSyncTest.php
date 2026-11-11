<?php

namespace Tests\Feature\Photobox;

use App\Models\PhotoboxStudio;
use App\Models\PhotoboxTransaction;
use App\Services\PhotoboxStats;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
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
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_11_09_000200_add_is_test_to_photobox_transactions_table.php', '--force' => true]);
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

    public function test_test_transactions_are_stored_for_history_but_not_counted_as_revenue(): void
    {
        [$studio, $token] = $this->studio();

        $this->withToken($token)->postJson('/api/photobox/transactions', ['transactions' => [
            $this->row(['order_no' => 'PB-EMMA-261109-0001', 'amount' => 99000, 'is_test' => true]),
            $this->row(['order_no' => 'PB-EMMA-261109-0002', 'amount' => 35000]),
        ]])->assertOk()->assertJsonPath('results.0.ok', true)->assertJsonPath('results.1.ok', true);

        // tersimpan (muncul di riwayat) dengan penanda test
        $this->assertDatabaseHas('photobox_transactions', ['order_no' => 'PB-EMMA-261109-0001', 'amount' => 99000, 'is_test' => true]);
        $this->assertDatabaseHas('photobox_transactions', ['order_no' => 'PB-EMMA-261109-0002', 'amount' => 35000, 'is_test' => false]);

        // tapi tidak masuk pendapatan / jumlah sesi / rincian paket
        $sum = PhotoboxStats::summary(now()->startOfDay(), now()->endOfDay());
        $this->assertSame(35000, $sum['revenue']);
        $this->assertSame(1, $sum['sessions']);
        $this->assertSame(1, $sum['transactions']);

        // dikirim ulang tetap test (tidak berubah jadi asli)
        $this->withToken($token)->postJson('/api/photobox/transactions', ['transactions' => [
            $this->row(['order_no' => 'PB-EMMA-261109-0001', 'amount' => 99000, 'is_test' => true]),
        ]])->assertOk();
        $this->assertSame(35000, PhotoboxStats::summary(now()->startOfDay(), now()->endOfDay())['revenue']);
    }

    public function test_unmarking_test_counts_revenue_and_is_pushed_to_booth_as_a_change(): void
    {
        [$studio, $token] = $this->studio();
        $this->withToken($token)->postJson('/api/photobox/transactions', ['transactions' => [
            $this->row(['order_no' => 'PB-EMMA-261109-0003', 'amount' => 40000, 'is_test' => true]),
        ]])->assertOk();
        $this->assertSame(0, PhotoboxStats::summary(now()->startOfDay(), now()->endOfDay())['revenue']);

        // kasir melepas tanda TEST (sama seperti tombol "tandai sebagai transaksi asli")
        $tx = PhotoboxTransaction::where('order_no', 'PB-EMMA-261109-0003')->firstOrFail();
        $tx->forceFill(['is_test' => false, 'edited_at' => now()])->save();

        $this->assertSame(40000, PhotoboxStats::summary(now()->startOfDay(), now()->endOfDay())['revenue']);
        $this->withToken($token)->getJson('/api/photobox/changes')->assertOk()
            ->assertJsonPath('changes.0.order_no', 'PB-EMMA-261109-0003')
            ->assertJsonPath('changes.0.is_test', false);

        // booth mengirim ulang baris yang sama (masih is_test di sisinya): hasil edit kasir TIDAK ditimpa
        $this->withToken($token)->postJson('/api/photobox/transactions', ['transactions' => [
            $this->row(['order_no' => 'PB-EMMA-261109-0003', 'amount' => 40000, 'is_test' => true]),
        ]])->assertOk();
        $this->assertDatabaseHas('photobox_transactions', ['order_no' => 'PB-EMMA-261109-0003', 'is_test' => false]);
    }

    public function test_mark_test_command_flags_old_transactions_in_range_and_can_undo(): void
    {
        [$studio, $token] = $this->studio();
        $this->withToken($token)->postJson('/api/photobox/transactions', ['transactions' => [
            $this->row(['order_no' => 'PB-EMMA-261109-0004', 'amount' => 35000]),
        ]])->assertOk();
        $today = now()->toDateString();

        $this->artisan('photobox:mark-test', ['--from' => $today, '--to' => $today, '--dry-run' => true])->assertExitCode(0);
        $this->assertDatabaseHas('photobox_transactions', ['order_no' => 'PB-EMMA-261109-0004', 'is_test' => false]);

        $this->artisan('photobox:mark-test', ['--from' => $today, '--to' => $today, '--yes' => true])->assertExitCode(0);
        $this->assertDatabaseHas('photobox_transactions', ['order_no' => 'PB-EMMA-261109-0004', 'is_test' => true]);
        $this->assertSame(0, PhotoboxStats::summary(now()->startOfDay(), now()->endOfDay())['revenue']);

        $this->artisan('photobox:mark-test', ['--from' => $today, '--to' => $today, '--undo' => true, '--yes' => true])->assertExitCode(0);
        $this->assertDatabaseHas('photobox_transactions', ['order_no' => 'PB-EMMA-261109-0004', 'is_test' => false]);
    }

    public function test_resend_from_booth_keeps_whatsapp_contact_saved_in_kasir(): void
    {
        [$studio, $token] = $this->studio();
        $this->withToken($token)->postJson('/api/photobox/transactions', ['transactions' => [$this->row(['order_no' => 'PB-EMMA-261109-0005'])]])->assertOk();

        $tx = PhotoboxTransaction::where('order_no', 'PB-EMMA-261109-0005')->firstOrFail();
        $tx->meta = ['wa_number' => '6281234567890', 'wa_name' => 'Budi'];
        $tx->save();

        // booth mengirim ulang (mis. backfill link galeri) dengan meta sendiri: kontak WA dari kasir tidak boleh hilang
        $this->withToken($token)->postJson('/api/photobox/transactions', ['transactions' => [
            $this->row(['order_no' => 'PB-EMMA-261109-0005', 'gallery_url' => 'https://galeri.test/s/ABC', 'meta' => ['discount' => 0]]),
        ]])->assertOk();

        $fresh = PhotoboxTransaction::where('order_no', 'PB-EMMA-261109-0005')->firstOrFail();
        $this->assertSame('6281234567890', $fresh->meta['wa_number']);
        $this->assertSame('Budi', $fresh->meta['wa_name']);
        $this->assertSame('https://galeri.test/s/ABC', $fresh->gallery_url);
    }

    public function test_gallery_sessions_without_transaction_become_rows_and_booth_transaction_replaces_them_later(): void
    {
        config(['services.popsnap_gallery.url' => 'https://foto.example.com', 'services.popsnap_gallery.key' => 'kunci-rahasia']);
        [$studio, $token] = $this->studio('Studio Emma');
        $tok = 'ManualUpload12345';
        Http::fake(['foto.example.com/api/v1/recent*' => Http::response(['ok' => true, 'sessions' => [
            ['token' => $tok, 'studio' => 'studio emma', 'code' => 'EMA', 'mode' => 'photo', 'social' => 3, 'createdAt' => now()->subMinutes(5)->timestamp * 1000, 'files' => 2, 'uploaded' => 2],
            ['token' => 'StudioAsing1234567', 'studio' => 'Studio Lain', 'code' => 'LAIN', 'mode' => 'photo', 'social' => null, 'createdAt' => now()->timestamp * 1000, 'files' => 1, 'uploaded' => 1],
        ]])]);

        // 1) sesi unggah manual dibuatkan baris (Gratis, Rp 0, ada link galeri). Nama studio dicocokkan tanpa peduli huruf besar/kecil;
        //    kalau hanya ada satu studio aktif, nama yang tidak dikenal pun dianggap studio itu
        $this->artisan('photobox:pull-gallery')->assertExitCode(0);
        $row = PhotoboxTransaction::where('order_no', 'GAL-'.$tok)->firstOrFail();
        $this->assertSame(0, (int) $row->amount);
        $this->assertSame('free', $row->method);
        $this->assertSame('paid', $row->status);
        $this->assertSame('https://foto.example.com/s/'.$tok, $row->gallery_url);
        $this->assertSame(0, PhotoboxStats::summary(now()->startOfDay(), now()->endOfDay())['revenue'], 'baris sementara tidak boleh menambah pendapatan');

        // 2) dijalankan lagi: tidak dobel
        $this->artisan('photobox:pull-gallery')->assertExitCode(0);
        $this->assertSame(1, PhotoboxTransaction::where('gallery_url', 'https://foto.example.com/s/'.$tok)->count());

        // 3) CS mengisi nomor WA di kasir, lalu booth online dan mengirim transaksi aslinya (link yang sama)
        $row->meta = array_merge($row->meta ?? [], ['wa_number' => '6281234567890', 'wa_name' => 'Budi']);
        $row->save();
        $this->withToken($token)->postJson('/api/photobox/transactions', ['transactions' => [
            $this->row(['order_no' => 'PB-EMMA-261110-0007', 'amount' => 35000, 'gallery_url' => 'https://foto.example.com/s/'.$tok]),
        ]])->assertOk()->assertJsonPath('results.0.ok', true);

        $this->assertDatabaseMissing('photobox_transactions', ['order_no' => 'GAL-'.$tok]); // diganti, bukan dobel
        $real = PhotoboxTransaction::where('order_no', 'PB-EMMA-261110-0007')->firstOrFail();
        $this->assertSame('6281234567890', $real->meta['wa_number']);
        $this->assertSame('Budi', $real->meta['wa_name']);
        $this->assertSame(35000, PhotoboxStats::summary(now()->startOfDay(), now()->endOfDay())['revenue']);

        // 4) tarikan berikutnya tidak membuat baris sementara lagi (link sudah punya transaksi)
        $this->artisan('photobox:pull-gallery')->assertExitCode(0);
        $this->assertDatabaseMissing('photobox_transactions', ['order_no' => 'GAL-'.$tok]);
    }

    public function test_pull_gallery_is_off_without_key_and_respects_deleted_rows(): void
    {
        $this->studio('Studio Emma');
        Http::fake(['*' => Http::response(['ok' => true, 'sessions' => [['token' => 'TokenHapus12345', 'studio' => 'Studio Emma', 'mode' => 'photo', 'createdAt' => now()->timestamp * 1000]]])]);

        // tanpa kunci: tidak memanggil galeri sama sekali
        config(['services.popsnap_gallery.url' => 'https://foto.example.com', 'services.popsnap_gallery.key' => '']);
        $this->artisan('photobox:pull-gallery')->assertExitCode(0);
        Http::assertNothingSent();
        $this->assertSame(0, PhotoboxTransaction::count());

        // dengan kunci: dibuat; dihapus di kasir: tidak dibuat ulang
        config(['services.popsnap_gallery.key' => 'kunci-rahasia']);
        $this->artisan('photobox:pull-gallery')->assertExitCode(0);
        PhotoboxTransaction::where('order_no', 'GAL-TokenHapus12345')->firstOrFail()->delete();
        $this->artisan('photobox:pull-gallery')->assertExitCode(0);
        $this->assertSame(1, PhotoboxTransaction::withTrashed()->count());

        // kunci salah di galeri (401): gagal dengan pesan jelas
        Http::fake(['*' => Http::response(['ok' => false], 401)]);
        $this->artisan('photobox:pull-gallery')->assertExitCode(1);
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
