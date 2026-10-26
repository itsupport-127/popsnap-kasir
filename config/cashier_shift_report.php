<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Penerima Email Laporan Tutup Kasir
    |--------------------------------------------------------------------------
    |
    | Daftar email yang akan menerima laporan tutup kasir (otomatis saat
    | closing maupun saat dikirim manual dari halaman shift kasir). Bisa
    | dioverride lewat env CASHIER_SHIFT_REPORT_RECIPIENTS (pisahkan dengan
    | koma) tanpa perlu mengubah kode.
    |
    */

    'recipients' => array_values(array_filter(array_map(
        'trim',
        explode(',', env(
            'CASHIER_SHIFT_REPORT_RECIPIENTS',
            'angela_c_n@yahoo.com,rahestia.h@gmail.com,dahayu.office@gmail.com,papyrusjogjaccm@gmail.com'
        ))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Nama Toko untuk Judul Email
    |--------------------------------------------------------------------------
    */

    'store_label' => env('CASHIER_SHIFT_REPORT_STORE_LABEL', 'POPSNAP'),

];
