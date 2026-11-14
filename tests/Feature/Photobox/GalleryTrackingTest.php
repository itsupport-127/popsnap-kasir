<?php

namespace Tests\Feature\Photobox;

use App\Services\GalleryStatus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Pelacakan unggahan galeri: status per transaksi, kode link, dan pencarian lewat nomor transaksi. */
class GalleryTrackingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.popsnap_gallery.url' => 'https://foto.example.com']);
        Cache::flush();
    }

    public function test_state_distinguishes_not_uploaded_partial_and_complete(): void
    {
        $this->assertNull(GalleryStatus::state(false, null));                                              // belum ada link
        $this->assertSame('unknown', GalleryStatus::state(true, null));                                    // galeri tak menjawab
        $this->assertSame('pending', GalleryStatus::state(true, ['exists' => false, 'files' => 0]));       // belum terunggah sama sekali
        $this->assertSame('pending', GalleryStatus::state(true, ['exists' => true, 'files' => 0, 'pending' => 3]));
        $this->assertSame('partial', GalleryStatus::state(true, ['exists' => true, 'files' => 2, 'pending' => 3]));
        $this->assertSame('uploaded', GalleryStatus::state(true, ['exists' => true, 'files' => 5, 'pending' => 0]));
        $this->assertSame('uploaded', GalleryStatus::state(true, ['exists' => true, 'files' => 5]));       // galeri versi lama (tanpa "pending")
        $this->assertSame('expired', GalleryStatus::state(true, ['exists' => true, 'files' => 5, 'expired' => true]));
    }

    public function test_alternative_finds_other_link_with_same_transaction_but_ignores_own_empty_and_expired(): void
    {
        $alt = GalleryStatus::alternative('AAAAAAAAAA', [
            ['token' => 'AAAAAAAAAA', 'files' => 9, 'pending' => 0, 'expired' => false], // kode sendiri
            ['token' => 'KOSONGKOSO', 'files' => 0, 'pending' => 2, 'expired' => false], // belum ada file
            ['token' => 'LAWASLAWAS', 'files' => 4, 'pending' => 0, 'expired' => true],  // kedaluwarsa
            ['token' => 'BBBBBBBBBB', 'files' => 6, 'pending' => 0, 'expired' => false],
        ]);
        $this->assertSame(['code' => 'BBBBBBBBBB', 'files' => 6, 'url' => 'https://foto.example.com/s/BBBBBBBBBB'], $alt);
        $this->assertNull(GalleryStatus::alternative('AAAAAAAAAA', [['token' => 'AAAAAAAAAA', 'files' => 3, 'expired' => false]]));
        $this->assertNull(GalleryStatus::alternative(null, []));
    }

    public function test_fetch_by_orders_asks_gallery_with_order_numbers(): void
    {
        Http::fake(['foto.example.com/api/v1/status' => Http::response(['ok' => true, 'sessions' => [], 'orders' => [
            'PB-EMMA-261114-0001' => [['token' => 'BBBBBBBBBB', 'files' => 6, 'pending' => 0, 'expired' => false]],
        ]])]);

        $r = GalleryStatus::fetchByOrders(['PB-EMMA-261114-0001', 'PB-EMMA-261114-0001', '']);

        $this->assertSame('BBBBBBBBBB', $r['PB-EMMA-261114-0001'][0]['token']);
        Http::assertSent(fn ($req) => $req['orders'] === ['PB-EMMA-261114-0001'] && $req['tokens'] === []);
    }

    public function test_fetch_by_orders_is_empty_when_gallery_is_down_or_off(): void
    {
        Http::fake(['*' => Http::response('x', 500)]);
        $this->assertSame([], GalleryStatus::fetchByOrders(['PB-EMMA-261114-0002']));
        $this->assertSame([], GalleryStatus::fetchByOrders([]));
        config(['services.popsnap_gallery.url' => '']);
        Cache::flush();
        $this->assertSame([], GalleryStatus::fetchByOrders(['PB-EMMA-261114-0003']));
    }
}
