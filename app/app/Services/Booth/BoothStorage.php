<?php

namespace App\Services\Booth;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/** Storage media booth — dibangun dari .env, jadi tidak perlu mengubah config/filesystems.php. */
class BoothStorage
{
    public static function driver(): string
    {
        return config('booth.storage.driver', 'local');
    }

    public static function isS3(): bool
    {
        return self::driver() === 's3';
    }

    public static function disk(): Filesystem
    {
        $c = config('booth.storage');
        if (self::isS3()) {
            return Storage::build([
                'driver' => 's3', 'key' => $c['key'], 'secret' => $c['secret'], 'region' => $c['region'] ?: 'auto',
                'bucket' => $c['bucket'], 'endpoint' => $c['endpoint'] ?: null,
                'use_path_style_endpoint' => (bool) $c['path_style'], 'throw' => true,
            ]);
        }

        return Storage::build(['driver' => 'local', 'root' => $c['root'] ?: storage_path('app/booth'), 'throw' => true]);
    }
}
