<?php

namespace App\Http\Controllers;

use App\Events\PrintJobRequested;
use App\Http\Controllers\Apps\SettingController;
use App\Models\PrintJob;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Jembatan "print dari HP" -> PC kasir.
 *
 * 1. HP tekan "Print dari HP" -> POST request(): dibuat 1 baris print_jobs (queued) + broadcast PrintJobRequested.
 * 2. PC kasir mana pun yang sedang membuka dashboard (login, QZ Tray menyala) menerima event itu lewat
 *    websocket (Reverb). Tidak perlu membuka halaman khusus lagi.
 * 3. PC mengklaim pekerjaannya (claim) — hanya satu PC yang menang, jadi struk tidak tercetak dobel —
 *    mencetak lewat QZ Tray, lalu melaporkan hasilnya (finish).
 * 4. Halaman /print-listener menampilkan riwayat print HARI INI saja (reset tiap hari).
 */
class PrintRelayController extends Controller
{
    /** Halaman "Print Hari Ini": riwayat print hari ini. */
    public function listener()
    {
        PrintJob::pruneOld();
        PrintJob::failStuck();

        return Inertia::render('Dashboard/PrintListener', [
            'jobs' => $this->todayJobs(),
            'printerName' => SettingController::printSettings()['printerName'],
        ]);
    }

    public function request(Request $request, string $invoice)
    {
        $data = $request->validate([
            'size' => 'nullable|in:58,80',
        ]);

        // Pastikan invoice valid & milik toko ini sebelum broadcast.
        Transaction::where('invoice', $invoice)->firstOrFail();

        PrintJob::pruneOld();

        $job = PrintJob::create([
            'invoice' => $invoice,
            'size' => $data['size'] ?? '58',
            'requested_by' => $request->user()?->name ?? 'HP',
            'status' => PrintJob::QUEUED,
        ]);

        event(new PrintJobRequested(
            invoice: $job->invoice,
            size: $job->size,
            requestedBy: $job->requested_by,
            jobId: $job->id,
            printerName: SettingController::printSettings()['printerName'],
        ));

        return response()->json(['message' => 'Perintah print dikirim ke PC.', 'job_id' => $job->id]);
    }

    /**
     * PC kasir mengklaim satu pekerjaan. Atomik: kalau dua PC mencoba bersamaan, hanya satu yang dapat 200,
     * yang lain 409 dan harus melewatinya.
     */
    public function claim(Request $request, PrintJob $job): JsonResponse
    {
        $claimed = PrintJob::where('id', $job->id)
            ->where('status', PrintJob::QUEUED)
            ->where('created_at', '>=', now()->subMinutes(PrintJob::PENDING_WINDOW_MINUTES))
            ->update([
                'status' => PrintJob::PRINTING,
                'printed_by' => $this->executorLabel($request),
                'claimed_at' => now(),
            ]);

        if ($claimed === 0) {
            return response()->json(['claimed' => false], 409);
        }

        return response()->json([
            'claimed' => true,
            'invoice' => $job->invoice,
            'size' => $job->size,
            'printer_name' => SettingController::printSettings()['printerName'],
        ]);
    }

    /** PC melaporkan hasil: printed / failed. */
    public function finish(Request $request, PrintJob $job): JsonResponse
    {
        $data = $request->validate([
            'status' => 'required|in:printed,failed',
            'error' => 'nullable|string|max:500',
        ]);

        PrintJob::where('id', $job->id)
            ->where('status', PrintJob::PRINTING)
            ->update([
                'status' => $data['status'],
                'error' => $data['status'] === PrintJob::FAILED ? ($data['error'] ?? 'Gagal mencetak.') : null,
                'finished_at' => now(),
            ]);

        return response()->json(['ok' => true]);
    }

    /**
     * Perintah yang masih menunggu (belum diambil PC manapun) dalam beberapa menit terakhir.
     * Dipanggil PC kasir saat baru terhubung / dashboard baru dibuka, supaya perintah yang masuk
     * tepat sebelum itu tidak hilang. Yang lebih lama dari jendela waktu dianggap basi.
     */
    public function pending(): JsonResponse
    {
        $jobs = PrintJob::where('status', PrintJob::QUEUED)
            ->where('created_at', '>=', now()->subMinutes(PrintJob::PENDING_WINDOW_MINUTES))
            ->orderBy('id')
            ->get(['id', 'invoice', 'size']);

        // Nama printer ikut dikirim supaya PC bisa memastikan printernya ada SEBELUM mengklaim.
        $printerName = SettingController::printSettings()['printerName'];

        return response()->json([
            'jobs' => $jobs->map(fn ($j) => ['id' => $j->id, 'invoice' => $j->invoice, 'size' => $j->size, 'printer_name' => $printerName])->values(),
        ]);
    }

    /** Dipakai halaman riwayat untuk menyegarkan daftar. */
    public function jobs(): JsonResponse
    {
        PrintJob::failStuck();

        return response()->json(['jobs' => $this->todayJobs()]);
    }

    private function todayJobs()
    {
        return PrintJob::where('created_at', '>=', now()->startOfDay())
            ->orderByDesc('id')
            ->limit(200)
            ->get()
            ->map(fn (PrintJob $j) => [
                'id' => $j->id,
                'invoice' => $j->invoice,
                'size' => $j->size,
                'status' => $j->status,
                'error' => $j->error,
                'requested_by' => $j->requested_by,
                'printed_by' => $j->printed_by,
                'created_at' => $j->created_at?->toIso8601String(),
                'finished_at' => $j->finished_at?->toIso8601String(),
            ])
            ->values();
    }

    private function executorLabel(Request $request): string
    {
        $device = trim((string) $request->input('device', ''));

        return mb_substr(trim(($request->user()?->name ?? 'PC kasir').($device !== '' ? ' · '.$device : '')), 0, 120);
    }
}
