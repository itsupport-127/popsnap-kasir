<?php

// Dimuat dari routes/web.php (di baris paling bawah)
use App\Http\Controllers\Apps\BoothAdminController;
use App\Http\Controllers\BoothPublicController;
use Illuminate\Support\Facades\Route;

// Halaman unduh publik untuk pelanggan
Route::get('/s/{token}', [BoothPublicController::class, 'show'])->middleware('throttle:60,1')->name('booth.share');
Route::get('/s/{token}/f/{media}', [BoothPublicController::class, 'file'])->middleware('throttle:120,1')->name('booth.file');

// Admin (butuh login + izin transaksi)
Route::group(['prefix' => 'dashboard/booth', 'middleware' => ['auth', 'verified', 'permission:transactions-access']], function () {
    Route::get('/', [BoothAdminController::class, 'index'])->name('booth.index');
    Route::put('outlet', [BoothAdminController::class, 'updateOutlet'])->name('booth.outlet.update');
    Route::post('devices', [BoothAdminController::class, 'storeDevice'])->name('booth.devices.store');
    Route::post('devices/{device}/toggle', [BoothAdminController::class, 'toggleDevice'])->name('booth.devices.toggle');
    Route::post('vouchers', [BoothAdminController::class, 'storeVoucher'])->name('booth.vouchers.store');
    Route::post('vouchers/{voucher}/toggle', [BoothAdminController::class, 'toggleVoucher'])->name('booth.vouchers.toggle');
    Route::post('sessions/{session}/wa-sent', [BoothAdminController::class, 'waSent'])->name('booth.sessions.wa-sent');
    Route::post('sessions/{session}/phone', [BoothAdminController::class, 'setPhone'])->name('booth.sessions.phone');
    Route::post('upload', [BoothAdminController::class, 'manualUpload'])->name('booth.upload');
});
