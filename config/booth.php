<?php

return [
    // Produk POS tempat penjualan sesi dicatat (dibuat otomatis oleh `php artisan booth:setup`).
    'product_sku' => env('BOOTH_PRODUCT_SKU', 'SESI-PHOTOBOX'),
    // Kasir (user id) yang dicatat sebagai pembuat transaksi booth. Kosong = user pertama.
    'cashier_id' => env('BOOTH_CASHIER_ID'),
    // Penyimpanan media: 'local' (disk server) atau 's3' (S3-compatible: Cloudflare R2, Backblaze B2, MinIO, dll).
    'storage' => [
        'driver' => env('BOOTH_STORAGE', 'local'),
        'root' => env('BOOTH_LOCAL_ROOT'),
        'key' => env('BOOTH_S3_KEY'),
        'secret' => env('BOOTH_S3_SECRET'),
        'region' => env('BOOTH_S3_REGION', 'auto'),
        'bucket' => env('BOOTH_S3_BUCKET'),
        'endpoint' => env('BOOTH_S3_ENDPOINT'),
        'path_style' => env('BOOTH_S3_PATH_STYLE', true),
    ],
    // Media dihapus otomatis setelah N hari (juga atur lifecycle rule di bucket).
    'retention_days' => (int) env('BOOTH_RETENTION_DAYS', 14),
    // Masa berlaku QRIS (menit).
    'qris_minutes' => (int) env('BOOTH_QRIS_MINUTES', 10),
    'upload_url_minutes' => 30,
];
