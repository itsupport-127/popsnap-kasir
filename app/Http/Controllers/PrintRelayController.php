<?php

namespace App\Http\Controllers;

use App\Events\PrintJobRequested;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Jembatan "print dari HP" -> PC kasir.
 *
 * Alurnya:
 * 1. PC kasir buka halaman /print-listener sekali, dibiarkan terbuka di
 *    tab tersendiri (di samping QZ Tray yang tetap jalan seperti biasa).
 * 2. HP buka halaman struk seperti biasa, tekan tombol "Print dari HP".
 * 3. Tombol itu POST ke sini -> broadcast event PrintJobRequested.
 * 4. Tab listener di PC menerima event lewat websocket (Reverb), lalu
 *    otomatis menjalankan print via QZ Tray persis seperti klik manual.
 */
class PrintRelayController extends Controller
{
    public function listener()
    {
        return Inertia::render('Dashboard/PrintListener', [
            'printerName' => \App\Http\Controllers\Apps\SettingController::printSettings()['printerName'],
        ]);
    }

    public function request(Request $request, string $invoice)
    {
        $data = $request->validate([
            'size' => 'nullable|in:58,80',
        ]);

        // Pastikan invoice valid & milik toko ini sebelum broadcast.
        Transaction::where('invoice', $invoice)->firstOrFail();

        event(new PrintJobRequested(
            invoice: $invoice,
            size: $data['size'] ?? '58',
            requestedBy: $request->user()?->name ?? 'HP',
        ));

        return response()->json(['message' => 'Perintah print dikirim ke PC.']);
    }
}
