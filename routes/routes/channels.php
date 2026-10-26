<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| "print-jobs" dipakai buat jembatan HP -> PC kasir. Siapa pun yang sudah
| login (punya session valid) boleh subscribe/kirim ke channel ini. Kalau
| nanti mau dibatasi per toko/cabang, tinggal cek $user->store_id di sini.
|
*/

Broadcast::channel('print-jobs', function ($user) {
    return $user !== null;
});
