<?php

use App\Http\Controllers\Api\PaymentWebhookController;
use App\Http\Controllers\Api\PhotoboxSyncController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are loaded by the RouteServiceProvider and are assigned
| the "api" middleware group.
|
*/

// Payment Gateway Webhooks (no auth required)
Route::prefix('webhooks')->group(function () {
    Route::post('/midtrans', [PaymentWebhookController::class, 'midtrans'])->name('webhooks.midtrans');
    Route::post('/xendit', [PaymentWebhookController::class, 'xendit'])->name('webhooks.xendit');
    Route::post('/doku', [PaymentWebhookController::class, 'doku'])->name('webhooks.doku');
    Route::post('/flip', [PaymentWebhookController::class, 'flip'])->name('webhooks.flip');
});

// Sinkron riwayat transaksi dari app Photobox (auth: Bearer token per studio)
Route::prefix('photobox')->middleware('throttle:120,1')->group(function () {
    Route::get('/ping', [PhotoboxSyncController::class, 'ping'])->name('photobox.ping');
    Route::post('/transactions', [PhotoboxSyncController::class, 'store'])->name('photobox.transactions.store');
    Route::get('/changes', [PhotoboxSyncController::class, 'changes'])->name('photobox.changes');
});
