<?php

// Dimuat dari routes/api.php  (prefix /api otomatis)
use App\Http\Controllers\Api\BoothApiController;
use App\Http\Middleware\AuthenticateBoothDevice;
use Illuminate\Support\Facades\Route;

Route::prefix('booth')->middleware([AuthenticateBoothDevice::class, 'throttle:240,1'])->group(function () {
    Route::get('ping', [BoothApiController::class, 'ping']);
    Route::post('payments', [BoothApiController::class, 'createPayment']);
    Route::get('payments/{clientRef}', [BoothApiController::class, 'paymentStatus']);
    Route::get('vouchers', [BoothApiController::class, 'vouchers']);
    Route::post('vouchers', [BoothApiController::class, 'createVoucher']);
    Route::post('sessions', [BoothApiController::class, 'upsertSession']);
    Route::post('sessions/{uuid}/upload-url', [BoothApiController::class, 'uploadUrl']);
    Route::post('sessions/{uuid}/media', [BoothApiController::class, 'storeMedia']);
});
