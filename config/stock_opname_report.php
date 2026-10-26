<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Penerima Email Laporan Stock Opname
    |--------------------------------------------------------------------------
    |
    | Daftar email yang menerima laporan Stock Opname waktu dikirim manual
    | dari halaman Stock Opname. Bisa dioverride lewat env
    | STOCK_OPNAME_REPORT_RECIPIENTS (pisahkan dengan koma) tanpa perlu
    | mengubah kode.
    |
    */

    'recipients' => array_values(array_filter(array_map(
        'trim',
        explode(',', env(
            'STOCK_OPNAME_REPORT_RECIPIENTS',
            'angela_c_n@yahoo.com,rahestia.h@gmail.com'
        ))
    ))),

    /*
    |--------------------------------------------------------------------------
    | Nama Toko untuk Judul Email
    |--------------------------------------------------------------------------
    */

    'store_label' => env('STOCK_OPNAME_REPORT_STORE_LABEL', 'POPSNAP'),

];
