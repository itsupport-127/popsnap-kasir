<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\PaymentGatewayException;
use App\Http\Controllers\Controller;
use App\Models\BoothMedia;
use App\Models\BoothSession;
use App\Models\BoothVoucher;
use App\Services\Booth\BoothPaymentService;
use Illuminate\Http\Request;
use App\Services\Booth\BoothStorage;
use Illuminate\Support\Str;

class BoothApiController extends Controller
{
    public function __construct(private BoothPaymentService $payments) {}

    private function device(Request $r)
    {
        return $r->attributes->get('booth_device');
    }

    public function ping(Request $r)
    {
        $d = $this->device($r);

        return ['ok' => true, 'device' => $d->name, 'prefix' => $d->prefix, 'outlet' => $d->outlet->name, 'server_time' => now()->toIso8601String()];
    }

    // ---------------------------------------------------------------- pembayaran

    public function createPayment(Request $r)
    {
        $data = $r->validate(['client_ref' => 'required|string|max:64', 'amount' => 'required|integer|min:1000|max:10000000', 'product_sku' => 'nullable|string|max:60']);
        try {
            $p = $this->payments->create($this->device($r), $data['client_ref'], (int) $data['amount'], $data['product_sku'] ?? null);
        } catch (PaymentGatewayException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return $this->paymentJson($p);
    }

    public function paymentStatus(Request $r, string $clientRef)
    {
        $p = \App\Models\BoothPayment::where('device_id', $this->device($r)->id)->where('client_ref', $clientRef)->firstOrFail();

        return $this->paymentJson($this->payments->refresh($p));
    }

    private function paymentJson($p): array
    {
        return [
            'client_ref' => $p->client_ref, 'status' => $p->status, 'amount' => $p->amount,
            'qr_string' => $p->qr_string, 'qr_url' => $p->qr_url,
            'expires_at' => optional($p->expires_at)->toIso8601String(),
        ];
    }

    // ---------------------------------------------------------------- voucher sesi

    /** Daftar voucher yang masih bisa dipakai, untuk disimpan di booth (cache offline). */
    public function vouchers(Request $r)
    {
        $d = $this->device($r);
        $list = BoothVoucher::usable()
            ->where(fn ($q) => $q->whereNull('outlet_id')->orWhere('outlet_id', $d->outlet_id))
            ->get(['code', 'max_uses', 'used_count', 'expires_at', 'source']);

        return ['vouchers' => $list, 'server_time' => now()->toIso8601String()];
    }

    /** Booth membuat voucher (kode wajib diawali prefix perangkat agar tidak bentrok). */
    public function createVoucher(Request $r)
    {
        $d = $this->device($r);
        $data = $r->validate([
            'code' => 'required|string|max:40|alpha_dash',
            'max_uses' => 'nullable|integer|min:1|max:1000',
            'expires_at' => 'nullable|date',
            'note' => 'nullable|string|max:120',
        ]);
        $code = strtoupper($data['code']);
        if (! str_starts_with($code, $d->prefix.'-')) {
            return response()->json(['message' => 'Kode voucher booth harus diawali '.$d->prefix.'-'], 422);
        }
        $v = BoothVoucher::firstOrCreate(['code' => $code], [
            'owner_id' => $d->owner_id, 'outlet_id' => $d->outlet_id, 'device_id' => $d->id, 'source' => 'booth',
            'max_uses' => $data['max_uses'] ?? 1, 'expires_at' => $data['expires_at'] ?? null, 'note' => $data['note'] ?? null,
        ]);

        return ['code' => $v->code, 'created' => $v->wasRecentlyCreated];
    }

    // ---------------------------------------------------------------- sesi

    /** Catat/perbarui sesi (idempotent per uuid). Dipanggil booth begitu sesi selesai, atau saat antrean offline terkirim. */
    public function upsertSession(Request $r)
    {
        $d = $this->device($r);
        $data = $r->validate([
            'uuid' => 'required|uuid',
            'payment_ref' => 'nullable|string|max:64',
            'voucher_code' => 'nullable|string|max:40',
            'template_name' => 'nullable|string|max:120',
            'photos_count' => 'nullable|integer|min:0|max:100',
            'started_at' => 'nullable|date',
            'finished_at' => 'nullable|date',
            'customer_phone' => 'nullable|string|max:32',
            'was_offline' => 'nullable|boolean',
            'media_expected' => 'nullable|integer|min:0|max:500',
            'meta' => 'nullable|array',
        ]);

        $payment = ! empty($data['payment_ref'])
            ? \App\Models\BoothPayment::where('device_id', $d->id)->where('client_ref', $data['payment_ref'])->first()
            : null;

        $session = BoothSession::firstOrNew(['uuid' => $data['uuid']]);
        $isNew = ! $session->exists;
        if ($isNew) {
            $session->share_token = Str::random(24);
            $session->owner_id = $d->owner_id;
            $session->outlet_id = $d->outlet_id;
            $session->device_id = $d->id;
        }
        $session->fill(array_filter([
            'payment_id' => $payment?->id,
            'voucher_code' => $data['voucher_code'] ?? null,
            'template_name' => $data['template_name'] ?? null,
            'photos_count' => $data['photos_count'] ?? null,
            'started_at' => $data['started_at'] ?? null,
            'finished_at' => $data['finished_at'] ?? null,
            'customer_phone' => $data['customer_phone'] ?? null,
            'was_offline' => $data['was_offline'] ?? null,
            'media_expected' => $data['media_expected'] ?? null,
            'meta' => $data['meta'] ?? null,
        ], fn ($v) => $v !== null));
        if (! empty($data['customer_phone']) && $session->wa_status === 'none') {
            $session->wa_status = 'queued';
        }
        $session->save();

        // voucher terpakai: hitung sekali per sesi baru
        if ($isNew && ! empty($data['voucher_code'])) {
            BoothVoucher::where('code', strtoupper($data['voucher_code']))->increment('used_count');
        }

        return ['uuid' => $session->uuid, 'share_token' => $session->share_token, 'share_url' => $session->shareUrl(), 'media_status' => $session->media_status];
    }

    // ---------------------------------------------------------------- media

    private function mediaPath(BoothSession $s, string $kind, string $filename): string
    {
        $safe = preg_replace('/[^A-Za-z0-9._-]/', '_', basename($filename));

        return 'booth/'.($s->owner_id ?? 0).'/'.$s->outlet_id.'/'.($s->started_at ?? $s->created_at)->format('Y-m-d').'/'.$s->uuid.'/'.$kind.'-'.$safe;
    }

    /** Minta URL unggah langsung ke storage (S3 presigned) — atau fallback unggah lewat API untuk disk lokal. */
    public function uploadUrl(Request $r, string $uuid)
    {
        $s = BoothSession::where('uuid', $uuid)->where('device_id', $this->device($r)->id)->firstOrFail();
        $data = $r->validate(['kind' => 'required|in:raw,result,gif,video,clip,other', 'filename' => 'required|string|max:150', 'content_type' => 'nullable|string|max:80']);
        $path = $this->mediaPath($s, $data['kind'], $data['filename']);

        if (BoothStorage::isS3()) {
            $up = BoothStorage::disk()->temporaryUploadUrl($path, now()->addMinutes(config('booth.upload_url_minutes')), ['ContentType' => $data['content_type'] ?? 'application/octet-stream']);

            return ['mode' => 'put', 'url' => $up['url'], 'headers' => $up['headers'], 'path' => $path];
        }

        return ['mode' => 'api', 'url' => url('/api/booth/sessions/'.$uuid.'/media'), 'path' => $path];
    }

    /** Unggah lewat API (disk lokal) — atau konfirmasi file yang sudah di-PUT ke S3 (kirim `path` tanpa `file`). */
    public function storeMedia(Request $r, string $uuid)
    {
        $s = BoothSession::where('uuid', $uuid)->where('device_id', $this->device($r)->id)->firstOrFail();
        $data = $r->validate([
            'kind' => 'required|in:raw,result,gif,video,clip,other',
            'filename' => 'required|string|max:150',
            'file' => 'nullable|file|max:204800',
            'size' => 'nullable|integer',
            'mime' => 'nullable|string|max:80',
        ]);
        $path = $this->mediaPath($s, $data['kind'], $data['filename']);
        $size = (int) ($data['size'] ?? 0);
        $mime = $data['mime'] ?? null;

        if ($r->hasFile('file')) {
            BoothStorage::disk()->putFileAs(dirname($path), $r->file('file'), basename($path));
            $size = $r->file('file')->getSize();
            $mime = $mime ?: $r->file('file')->getMimeType();
        }

        BoothMedia::updateOrCreate(['session_id' => $s->id, 'path' => $path], ['kind' => $data['kind'], 'name' => $data['filename'], 'size' => $size, 'mime' => $mime, 'deleted_at' => null]);
        $s->refreshMediaStatus();

        return ['ok' => true, 'media_status' => $s->fresh()->media_status];
    }
}
