<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class QzCertController extends Controller
{
    /**
     * Kirim certificate ke QZ Tray (dipanggil saat QZ Tray minta verifikasi)
     */
    public function certificate()
    {
        $certPath = storage_path('app/qz/digital-certificate.txt');

        if (! file_exists($certPath)) {
            return response('', 200)
                ->header('Content-Type', 'text/plain');
        }

        return response(file_get_contents($certPath), 200)
            ->header('Content-Type', 'text/plain');
    }

    /**
     * Sign pesan dari QZ Tray menggunakan private key
     * QZ Tray kirim "toSign" (string random), kita sign, kirim balik
     */
    public function sign(Request $request)
    {
        $toSign      = $request->input('toSign', '');
        $keyPath     = storage_path('app/qz/private-key.pem');

        if (! file_exists($keyPath) || blank($toSign)) {
            return response('', 200)
                ->header('Content-Type', 'text/plain');
        }

        $privateKey = file_get_contents($keyPath);
        $signature  = '';

        openssl_sign($toSign, $signature, $privateKey, OPENSSL_ALGO_SHA512);

        return response(base64_encode($signature), 200)
            ->header('Content-Type', 'text/plain');
    }
}