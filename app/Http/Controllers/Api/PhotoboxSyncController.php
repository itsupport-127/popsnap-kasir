<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PhotoboxStudio;
use App\Models\PhotoboxTransaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;

/**
 * Penerima riwayat transaksi dari app Photobox (booth). Auth: header "Authorization: Bearer <token studio>".
 * Idempoten: order_no yang sama dikirim ulang = diperbarui, bukan dobel. Status "paid" tidak bisa mundur ke pending/failed.
 */
class PhotoboxSyncController extends Controller
{
    private const RANK = ['pending' => 0, 'failed' => 1, 'expired' => 1, 'paid' => 2, 'refunded' => 3];

    private function studio(Request $request): ?PhotoboxStudio
    {
        return PhotoboxStudio::findByToken($request->bearerToken());
    }

    public function ping(Request $request): JsonResponse
    {
        $studio = $this->studio($request);
        if (! $studio) {
            return response()->json(['ok' => false, 'error' => 'Token tidak valid atau studio nonaktif.'], 401);
        }
        $studio->forceFill(['last_seen_at' => now()])->save();

        return response()->json(['ok' => true, 'studio' => $studio->name, 'serverTime' => now()->toIso8601String()]);
    }

    public function store(Request $request): JsonResponse
    {
        $studio = $this->studio($request);
        if (! $studio) {
            return response()->json(['ok' => false, 'error' => 'Token tidak valid atau studio nonaktif.'], 401);
        }

        $rows = $request->input('transactions');
        if (! is_array($rows) || count($rows) === 0 || count($rows) > 100) {
            return response()->json(['ok' => false, 'error' => 'Kirim 1-100 transaksi di "transactions".'], 422);
        }

        $results = [];
        foreach ($rows as $row) {
            $v = Validator::make(is_array($row) ? $row : [], [
                'order_no' => ['required', 'string', 'max:64'],
                'template_id' => ['nullable', 'string', 'max:64'],
                'template_name' => ['nullable', 'string', 'max:255'],
                'category' => ['nullable', 'string', 'max:255'],
                'mode' => ['nullable', 'string', 'max:24'],
                'method' => ['required', 'in:qris,voucher,free'],
                'voucher_code' => ['nullable', 'string', 'max:40'],
                'amount' => ['required', 'integer', 'min:0'],
                'status' => ['required', 'in:pending,paid,failed,expired,refunded'],
                'paid_at' => ['nullable', 'date'],
                'created_at' => ['nullable', 'date'],
                'session_id' => ['nullable', 'string', 'max:64'],
                'meta' => ['nullable', 'array'],
            ]);
            $orderNo = is_array($row) ? (string) ($row['order_no'] ?? '') : '';
            if ($v->fails()) {
                $results[] = ['order_no' => $orderNo, 'ok' => false, 'error' => $v->errors()->first()];

                continue;
            }
            $d = $v->validated();
            $tz = config('app.timezone');
            $paidAt = ! empty($d['paid_at']) ? Carbon::parse($d['paid_at'])->setTimezone($tz) : null;
            $createdAt = ! empty($d['created_at']) ? Carbon::parse($d['created_at'])->setTimezone($tz) : null;
            if ($d['status'] === 'paid' && ! $paidAt) {
                $paidAt = $createdAt ?? now();
            }

            $existing = PhotoboxTransaction::withTrashed()->where('order_no', $d['order_no'])->first();
            if ($existing && $existing->studio_id !== $studio->id) {
                $results[] = ['order_no' => $d['order_no'], 'ok' => false, 'error' => 'Nomor transaksi sudah dipakai studio lain.'];

                continue;
            }
            // Sudah dihapus di kasir: biarkan terhapus (jangan dibuat ulang) supaya pendapatan tidak dobel
            if ($existing && $existing->trashed()) {
                $results[] = ['order_no' => $d['order_no'], 'ok' => true, 'kept' => 'deleted'];

                continue;
            }
            // Sudah diedit manual di kasir: data kasir yang dipakai, jangan ditimpa kiriman booth
            if ($existing && $existing->edited_at) {
                $results[] = ['order_no' => $d['order_no'], 'ok' => true, 'kept' => 'edited'];

                continue;
            }
            // Jangan turunkan status (mis. paid -> pending karena data lama datang terlambat)
            if ($existing && (self::RANK[$d['status']] ?? 0) < (self::RANK[$existing->status] ?? 0)) {
                $results[] = ['order_no' => $d['order_no'], 'ok' => true, 'kept' => $existing->status];

                continue;
            }

            PhotoboxTransaction::updateOrCreate(
                ['order_no' => $d['order_no']],
                [
                    'studio_id' => $studio->id,
                    'template_id' => $d['template_id'] ?? null,
                    'template_name' => $d['template_name'] ?? null,
                    'category' => $d['category'] ?? null,
                    'mode' => $d['mode'] ?? null,
                    'method' => $d['method'],
                    'voucher_code' => isset($d['voucher_code']) ? strtoupper($d['voucher_code']) : null,
                    'amount' => $d['amount'],
                    'status' => $d['status'],
                    'paid_at' => $paidAt,
                    'booth_created_at' => $createdAt,
                    'session_id' => $d['session_id'] ?? null,
                    'meta' => $d['meta'] ?? null,
                ]
            );
            $results[] = ['order_no' => $d['order_no'], 'ok' => true];
        }

        $studio->forceFill(['last_seen_at' => now()])->save();

        return response()->json(['ok' => true, 'studio' => $studio->name, 'results' => $results]);
    }
}
