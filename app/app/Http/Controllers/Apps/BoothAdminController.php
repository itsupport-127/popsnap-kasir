<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\BoothDevice;
use App\Models\BoothMedia;
use App\Models\BoothOutlet;
use App\Models\BoothSession;
use App\Models\BoothVoucher;
use Illuminate\Http\Request;
use App\Services\Booth\BoothStorage;
use Illuminate\Support\Str;
use Inertia\Inertia;

class BoothAdminController extends Controller
{
    private function outlet(): BoothOutlet
    {
        return BoothOutlet::first() ?? BoothOutlet::create(['name' => 'Outlet Utama']);
    }

    public static function waLink(BoothSession $s): ?string
    {
        if (! $s->customer_phone) {
            return null;
        }
        $p = preg_replace('/\D/', '', $s->customer_phone);
        $p = str_starts_with($p, '0') ? '62'.substr($p, 1) : $p;
        $tpl = $s->outlet?->wa_template ?: 'Halo! Ini foto & video sesi photobox kamu: {link}  Link aktif beberapa hari saja, silakan diunduh ya. Terima kasih!';

        return 'https://wa.me/'.$p.'?text='.rawurlencode(str_replace('{link}', $s->shareUrl(), $tpl));
    }

    public function index(Request $request)
    {
        $outlet = $this->outlet();

        $sessions = BoothSession::with('outlet:id,name,wa_template', 'device:id,name')->withCount(['media' => fn ($q) => $q->whereNull('deleted_at')])
            ->when($request->input('wa'), fn ($q, $v) => $q->where('wa_status', $v))
            ->latest()->limit(100)->get()->map(fn ($s) => [
                'id' => $s->id, 'uuid' => $s->uuid, 'device' => $s->device?->name, 'template' => $s->template_name,
                'photos' => $s->photos_count, 'phone' => $s->customer_phone, 'wa_status' => $s->wa_status,
                'media_status' => $s->media_status, 'media_count' => $s->media_count, 'was_offline' => $s->was_offline,
                'share_url' => $s->shareUrl(), 'wa_link' => self::waLink($s), 'at' => optional($s->finished_at ?? $s->created_at)->toDateTimeString(),
            ]);

        return Inertia::render('Dashboard/Booth/Index', [
            'outlet' => $outlet,
            'devices' => BoothDevice::where('outlet_id', $outlet->id)->get(['id', 'name', 'prefix', 'is_active', 'last_seen_at', 'last_ip']),
            'vouchers' => BoothVoucher::latest()->limit(100)->get(),
            'sessions' => $sessions,
            'newToken' => session('booth_new_token'),
            'filters' => ['wa' => $request->input('wa')],
        ]);
    }

    public function storeDevice(Request $r)
    {
        $data = $r->validate(['name' => 'required|string|max:80']);
        [$device, $token] = BoothDevice::register($this->outlet(), $data['name']);

        return back()->with('booth_new_token', ['name' => $device->name, 'prefix' => $device->prefix, 'token' => $token]);
    }

    public function toggleDevice(BoothDevice $device)
    {
        $device->update(['is_active' => ! $device->is_active]);

        return back();
    }

    public function updateOutlet(Request $r)
    {
        $data = $r->validate(['name' => 'required|string|max:80', 'wa_template' => 'nullable|string|max:500']);
        $this->outlet()->update($data);

        return back();
    }

    public function storeVoucher(Request $r)
    {
        $data = $r->validate([
            'code' => 'nullable|string|max:40|alpha_dash',
            'count' => 'nullable|integer|min:1|max:200',
            'max_uses' => 'nullable|integer|min:1|max:1000',
            'valid_days' => 'nullable|integer|min:1|max:3650',
            'note' => 'nullable|string|max:120',
        ]);
        $outlet = $this->outlet();
        $count = $data['count'] ?? 1;
        for ($i = 0; $i < $count; $i++) {
            $code = $count === 1 && ! empty($data['code']) ? strtoupper($data['code']) : 'SV-'.strtoupper(Str::random(6));
            BoothVoucher::firstOrCreate(['code' => $code], [
                'owner_id' => $outlet->owner_id, 'outlet_id' => $outlet->id, 'source' => 'server',
                'max_uses' => $data['max_uses'] ?? 1,
                'expires_at' => ! empty($data['valid_days']) ? now()->addDays($data['valid_days']) : null,
                'note' => $data['note'] ?? null,
            ]);
        }

        return back();
    }

    public function toggleVoucher(BoothVoucher $voucher)
    {
        $voucher->update(['is_active' => ! $voucher->is_active]);

        return back();
    }

    public function waSent(BoothSession $session)
    {
        $session->update(['wa_status' => 'sent']);

        return back();
    }

    public function setPhone(Request $r, BoothSession $session)
    {
        $data = $r->validate(['phone' => 'required|string|max:32']);
        $session->update(['customer_phone' => $data['phone'], 'wa_status' => $session->wa_status === 'sent' ? 'sent' : 'queued']);

        return back();
    }

    /**
     * Unggah manual: ZIP berisi folder sesi dari booth (ada session.json + file hasil).
     * Dipakai ketika booth offline dan CS mengunggah dari folder output.
     */
    public function manualUpload(Request $r)
    {
        $r->validate(['zip' => 'required|file|mimes:zip|max:1048576']);
        $zip = new \ZipArchive;
        if ($zip->open($r->file('zip')->getRealPath()) !== true) {
            return back()->withErrors(['zip' => 'ZIP tidak bisa dibuka.']);
        }
        $jsonIdx = null;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            if (basename($zip->getNameIndex($i)) === 'session.json') {
                $jsonIdx = $i;
                break;
            }
        }
        if ($jsonIdx === null) {
            return back()->withErrors(['zip' => 'session.json tidak ditemukan di dalam ZIP.']);
        }
        $meta = json_decode($zip->getFromIndex($jsonIdx), true);
        if (! is_array($meta) || empty($meta['uuid']) || ! Str::isUuid($meta['uuid'])) {
            return back()->withErrors(['zip' => 'session.json tidak valid.']);
        }
        $baseDir = dirname($zip->getNameIndex($jsonIdx));
        $baseDir = $baseDir === '.' ? '' : $baseDir.'/';

        $outlet = $this->outlet();
        $session = BoothSession::firstOrCreate(['uuid' => $meta['uuid']], [
            'owner_id' => $outlet->owner_id, 'outlet_id' => $outlet->id, 'share_token' => Str::random(24),
            'template_name' => $meta['template_name'] ?? null, 'photos_count' => $meta['photos_count'] ?? 0,
            'started_at' => $meta['started_at'] ?? now(), 'finished_at' => $meta['finished_at'] ?? null,
            'customer_phone' => $meta['customer_phone'] ?? null, 'was_offline' => true,
            'wa_status' => ! empty($meta['customer_phone']) ? 'queued' : 'none',
            'media_expected' => count($meta['files'] ?? []),
        ]);

        $disk = BoothStorage::disk();
        $n = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_ends_with($name, '/') || basename($name) === 'session.json' || ! str_starts_with($name, $baseDir)) {
                continue;
            }
            $filename = basename($name);
            $kind = $this->guessKind($filename, $meta['files'] ?? []);
            $path = 'booth/'.($session->owner_id ?? 0).'/'.$session->outlet_id.'/'.($session->started_at ?? $session->created_at)->format('Y-m-d').'/'.$session->uuid.'/'.$kind.'-'.preg_replace('/[^A-Za-z0-9._-]/', '_', $filename);
            $stream = $zip->getStream($name);
            $disk->put($path, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
            BoothMedia::updateOrCreate(['session_id' => $session->id, 'path' => $path], [
                'kind' => $kind, 'name' => $filename, 'size' => $zip->statIndex($i)['size'] ?? 0, 'deleted_at' => null,
            ]);
            $n++;
        }
        $zip->close();
        $session->refreshMediaStatus();

        return back()->with('success', "Sesi {$session->uuid}: $n file diunggah.");
    }

    private function guessKind(string $filename, array $manifest): string
    {
        foreach ($manifest as $f) {
            if (($f['name'] ?? null) === $filename && in_array($f['kind'] ?? '', ['raw', 'result', 'gif', 'video', 'clip'], true)) {
                return $f['kind'];
            }
        }
        $l = strtolower($filename);

        return match (true) {
            str_ends_with($l, '.gif') => 'gif',
            str_ends_with($l, '.mp4'), str_ends_with($l, '.webm') => 'video',
            str_contains($l, 'raw') => 'raw',
            default => 'result',
        };
    }
}
