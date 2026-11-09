<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PhotoboxStudio;
use App\Models\PhotoboxTransaction;
use App\Services\PhotoboxDiscountService;
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

    /**
     * Laporan status dari app Photobox (tiap ±1 menit): antrean kirim, error, galeri, ruang disk.
     * Isinya dibersihkan dan dibatasi supaya aman; booth versi lama yang belum mengirim ini tetap terbaca online lewat /ping dan /changes.
     */
    public function heartbeat(Request $request): JsonResponse
    {
        $studio = $this->studio($request);
        if (! $studio) {
            return response()->json(['ok' => false, 'error' => 'Token tidak valid atau studio nonaktif.'], 401);
        }

        $int = fn ($v) => max(0, min(1000000, (int) $v));
        $str = fn ($v, int $n = 300) => mb_substr(trim((string) ($v ?? '')), 0, $n);
        $in = $request->all();
        $disk = $in['diskLevel'] ?? 'ok';
        $report = [
            'pending' => $int($in['pending'] ?? 0),
            'errors' => $int($in['errors'] ?? 0),
            'lastError' => $str($in['lastError'] ?? ''),
            'lastSessionAt' => isset($in['lastSessionAt']) && is_numeric($in['lastSessionAt']) ? (int) $in['lastSessionAt'] : null,
            'galleryPending' => $int($in['galleryPending'] ?? 0),
            'galleryErrors' => $int($in['galleryErrors'] ?? 0),
            'galleryLastError' => $str($in['galleryLastError'] ?? ''),
            'diskFreeGB' => isset($in['diskFreeGB']) && is_numeric($in['diskFreeGB']) ? round((float) $in['diskFreeGB'], 1) : null,
            'diskLevel' => in_array($disk, ['ok', 'low', 'critical'], true) ? $disk : 'ok',
            'app' => $str($in['app'] ?? 'photobox', 20),
        ];
        $studio->forceFill(['sync_report' => $report, 'report_at' => now(), 'last_seen_at' => now()])->save();

        return response()->json(['ok' => true]);
    }

    /**
     * Perubahan yang dibuat di kasir (edit / hapus) untuk studio ini, supaya booth ikut menyesuaikan.
     * Booth menarik (polling) karena booth di belakang jaringan lokal. Parameter ?since=<cursor> dari jawaban sebelumnya.
     * Aman diulang: booth menerapkan perubahan yang sama berkali-kali tanpa efek ganda.
     */
    public function changes(Request $request): JsonResponse
    {
        $studio = $this->studio($request);
        if (! $studio) {
            return response()->json(['ok' => false, 'error' => 'Token tidak valid atau studio nonaktif.'], 401);
        }

        $since = null;
        if ($request->filled('since')) {
            try {
                $since = Carbon::parse((string) $request->query('since'));
            } catch (\Throwable) {
                return response()->json(['ok' => false, 'error' => 'Parameter "since" tidak valid.'], 422);
            }
        }

        $rows = PhotoboxTransaction::withTrashed()
            ->where('studio_id', $studio->id)
            ->where(function ($q) use ($since) {
                $q->whereNotNull('edited_at')->orWhereNotNull('deleted_at');
                if ($since) {
                    $q->where(function ($w) use ($since) {
                        $w->where('edited_at', '>=', $since)->orWhere('deleted_at', '>=', $since);
                    });
                }
            })
            ->orderByRaw('COALESCE(deleted_at, edited_at)')
            ->orderBy('id')
            ->limit(200)
            ->get();

        $changes = $rows->map(function (PhotoboxTransaction $t) {
            $changedAt = $t->deleted_at ?? $t->edited_at;

            return [
                'order_no' => $t->order_no,
                'deleted' => $t->trashed(),
                'template_name' => $t->template_name,
                'method' => $t->method,
                'amount' => (int) $t->amount,
                'status' => $t->status,
                'paid_at' => $t->paid_at?->toIso8601String(),
                'changed_at' => $changedAt?->toIso8601String(),
            ];
        })->values();

        return response()->json([
            'ok' => true,
            'changes' => $changes,
            'cursor' => $changes->last()['changed_at'] ?? ($since?->toIso8601String()),
        ]);
    }

    /** Booth menanyakan potongan harga (promo otomatis + kode diskon) sebelum membuat QRIS. Hanya membaca, tidak memakai kuota. */
    public function discountQuote(Request $request): JsonResponse
    {
        if (! $this->studio($request)) {
            return response()->json(['ok' => false, 'error' => 'Token tidak valid atau studio nonaktif.'], 401);
        }
        $v = Validator::make($request->all(), ['amount' => ['required', 'integer', 'min:0', 'max:100000000'], 'code' => ['nullable', 'string', 'max:40']]);
        if ($v->fails()) {
            return response()->json(['ok' => false, 'error' => $v->errors()->first()], 422);
        }

        return response()->json(app(PhotoboxDiscountService::class)->quote((int) $request->input('amount'), $request->input('code')));
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
                // hanya http(s): dipakai sebagai link & pesan WhatsApp di kasir
                'gallery_url' => ['nullable', 'string', 'max:500', 'regex:#^https?://#i'],
                'meta' => ['nullable', 'array'],
                // transaksi uji (mode simulasi / gateway sandbox): disimpan & diberi label TEST, tidak dihitung sebagai pendapatan
                'is_test' => ['nullable', 'boolean'],
            ]);
            $orderNo = is_array($row) ? (string) ($row['order_no'] ?? '') : '';
            if ($v->fails()) {
                $results[] = ['order_no' => $orderNo, 'ok' => false, 'error' => $v->errors()->first()];

                continue;
            }
            $d = $v->validated();
            // Transaksi uji (simulasi / sandbox) tetap disimpan supaya terlihat di riwayat, tapi bertanda is_test:
            // tidak dihitung sebagai pendapatan dan tidak memakai kuota kode diskon.
            $isTest = (bool) ($d['is_test'] ?? false);
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
                // link galeri tetap diperbarui walau data transaksi sudah diedit di kasir
                if (! empty($d['gallery_url']) && $existing->gallery_url !== $d['gallery_url']) {
                    $existing->forceFill(['gallery_url' => $d['gallery_url']])->save();
                }
                $results[] = ['order_no' => $d['order_no'], 'ok' => true, 'kept' => 'edited'];

                continue;
            }
            // Jangan turunkan status (mis. paid -> pending karena data lama datang terlambat)
            if ($existing && (self::RANK[$d['status']] ?? 0) < (self::RANK[$existing->status] ?? 0)) {
                $results[] = ['order_no' => $d['order_no'], 'ok' => true, 'kept' => $existing->status];

                continue;
            }

            // Pemakaian kode diskon dicatat SEKALI saat transaksi lunas (kiriman ulang / susulan tidak menambah lagi).
            $meta = $d['meta'] ?? null;
            $code = is_array($meta) ? strtoupper(trim((string) ($meta['promo_code'] ?? ''))) : '';
            $already = (bool) ($existing?->meta['promo_redeemed'] ?? false);
            if ($already) { // tanda "sudah dihitung" tidak boleh hilang walau kiriman ulang tidak membawa meta
                $meta = array_merge(is_array($meta) ? $meta : [], ['promo_redeemed' => true]);
            }
            if ($code !== '' && $d['status'] === 'paid' && ! $already && ! $isTest) {
                app(PhotoboxDiscountService::class)->redeem($code);
                $meta['promo_redeemed'] = true;
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
                    'is_test' => $isTest,
                    'paid_at' => $paidAt,
                    'booth_created_at' => $createdAt,
                    'session_id' => $d['session_id'] ?? null,
                    // jangan hapus link yang sudah ada kalau kiriman berikutnya tidak membawa link
                    'gallery_url' => $d['gallery_url'] ?? $existing?->gallery_url,
                    'meta' => $meta,
                ]
            );
            $results[] = ['order_no' => $d['order_no'], 'ok' => true];
        }

        $studio->forceFill(['last_seen_at' => now()])->save();

        return response()->json(['ok' => true, 'studio' => $studio->name, 'results' => $results]);
    }
}
