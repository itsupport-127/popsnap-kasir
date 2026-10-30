<?php

namespace Tests\Unit;

use App\Services\GalleryStatus;
use Tests\TestCase;

class GalleryStatusTest extends TestCase
{
    public function test_wa_number_is_normalized_to_indonesian_format(): void
    {
        $this->assertSame('6281234567890', GalleryStatus::waNumber(81234567890)); // 0 di depan hilang (kolom angka)
        $this->assertSame('6281234567890', GalleryStatus::waNumber('0812-3456-7890'));
        $this->assertSame('6281234567890', GalleryStatus::waNumber('+62 812 3456 7890'));
        $this->assertNull(GalleryStatus::waNumber(''));
        $this->assertNull(GalleryStatus::waNumber('123'));
        $this->assertNull(GalleryStatus::waNumber(null));
    }

    public function test_token_only_read_from_the_configured_gallery_host(): void
    {
        config(['services.popsnap_gallery.url' => 'https://galeri.contoh.id']);
        $this->assertSame('K7M29QXH4Bab', GalleryStatus::tokenFromUrl('https://galeri.contoh.id/s/K7M29QXH4Bab'));
        $this->assertNull(GalleryStatus::tokenFromUrl('https://jahat.example/s/K7M29QXH4Bab'), 'host lain tidak boleh dipanggil');
        $this->assertNull(GalleryStatus::tokenFromUrl(null));

        config(['services.popsnap_gallery.url' => null]);
        $this->assertNull(GalleryStatus::tokenFromUrl('https://galeri.contoh.id/s/K7M29QXH4Bab'));
    }
}
