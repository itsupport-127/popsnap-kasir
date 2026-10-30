<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\PhotoboxStudio;
use App\Models\PhotoboxTransaction;
use App\Models\Transaction;
use App\Services\GalleryStatus;
use App\Services\PhotoboxStats;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/** Menu "Transaksi Photobox": riwayat per studio, ringkasan pendapatan & produk terjual, kelola studio/token. */
class PhotoboxTransactionController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'period' => $request->input('period', 'today'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
            'studio_id' => $request->input('studio_id'),
            'method' => $request->input('method'),
            'status' => $request->input('status'),
            'search' => $request->input('search'),
        ];
        [$start, $end] = PhotoboxStats::range($filters['period'], $filters['start_date'], $filters['end_date']);
        $studioId = $filters['studio_id'] ? (int) $filters['studio_id'] : null;

        $transactions = PhotoboxTransaction::query()
            ->with('studio:id,name')
            ->where(function ($q) use ($start, $end) {
                // Yang sudah dibayar dihitung menurut waktu bayar; sisanya menurut waktu dibuat di booth
                $q->whereBetween('paid_at', [$start, $end])
                    ->orWhere(fn ($w) => $w->whereNull('paid_at')->whereBetween('booth_created_at', [$start, $end]));
            })
            ->when($studioId, fn ($q) => $q->where('studio_id', $studioId))
            ->when($filters['method'], fn ($q, $v) => $q->where('method', $v))
            ->when($filters['status'], fn ($q, $v) => $q->where('status', $v))
            ->when($filters['search'], fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('order_no', 'like', "%{$s}%")
                ->orWhere('voucher_code', 'like', "%{$s}%")
                ->orWhere('template_name', 'like', "%{$s}%")))
            ->orderByRaw('COALESCE(paid_at, booth_created_at, created_at) DESC')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (PhotoboxTransaction $t) => [
                'id' => $t->id,
                'order_no' => $t->order_no,
                'studio' => $t->studio?->name,
                'template_name' => $t->template_name,
                'category' => $t->category,
                'mode' => $t->mode,
                'method' => $t->method,
                'voucher_code' => $t->voucher_code,
                'amount' => $t->amount,
                'status' => $t->status,
                'paid_at' => $t->paid_at?->toIso8601String(),
                'paid_at_local' => $t->paid_at?->copy()->setTimezone(config('app.timezone'))->format('Y-m-d\TH:i'),
                'edited' => (bool) $t->edited_at,
                'gallery_url' => $t->gallery_url,
                'booth_created_at' => $t->booth_created_at?->toIso8601String(),
                'kasir_reference' => $t->meta['kasir_reference'] ?? null,
            ]);

        // Status galeri (terupload / izin foto) + nomor WhatsApp pelanggan dari transaksi kasir
        $page = $transactions->getCollection();
        $gallery = GalleryStatus::fetch($page->map(fn ($r) => GalleryStatus::tokenFromUrl($r['gallery_url']))->filter()->all());
        $phones = Transaction::query()->with('customer:id,name,no_telp')
            ->whereIn('invoice', $page->pluck('kasir_reference')->filter()->unique()->all())
            ->get()->keyBy('invoice');
        $transactions->setCollection($page->map(function ($r) use ($gallery, $phones) {
            $tok = GalleryStatus::tokenFromUrl($r['gallery_url']);
            $g = $tok ? ($gallery[$tok] ?? null) : null;
            $cust = $r['kasir_reference'] ? ($phones[$r['kasir_reference']]->customer ?? null) : null;
            $r['gallery_state'] = ! $r['gallery_url'] ? null : (! $g ? 'unknown' : (! empty($g['expired']) ? 'expired' : (($g['files'] ?? 0) > 0 ? 'uploaded' : 'pending')));
            $r['gallery_files'] = $g['files'] ?? 0;
            $r['social'] = $g['social'] ?? null;
            $r['customer_name'] = $cust?->name;
            $r['customer_wa'] = GalleryStatus::waNumber($cust?->no_telp);
            unset($r['kasir_reference']);

            return $r;
        }));

        $canManage = $request->user()?->can('photobox-manage') ?? false;

        $studioTx = PhotoboxTransaction::query()
            ->selectRaw("studio_id, COUNT(*) as count, SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as revenue")
            ->groupBy('studio_id')->get()->keyBy('studio_id');

        return Inertia::render('Dashboard/PhotoboxTransactions/Index', [
            'filters' => $filters,
            'range' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'transactions' => $transactions,
            'summary' => PhotoboxStats::summary($start, $end, $studioId),
            'studios' => PhotoboxStudio::query()->orderBy('name')->get()->map(fn (PhotoboxStudio $s) => [
                'id' => $s->id,
                'tx_count' => (int) ($studioTx[$s->id]->count ?? 0),
                'tx_revenue' => (int) ($studioTx[$s->id]->revenue ?? 0),
                'name' => $s->name,
                'is_active' => $s->is_active,
                'token_hint' => $s->token_hint,
                'last_seen_at' => $s->last_seen_at?->toIso8601String(),
            ]),
            'galleryStatusOn' => GalleryStatus::baseUrl() !== null,
            'canManage' => $canManage,
            'canEdit' => $request->user()?->can('photobox-edit') ?? false,
            'canDelete' => $request->user()?->can('photobox-delete') ?? false,
            'apiUrl' => url('/api/photobox'),
            'newToken' => session('photobox_new_token'),
            'newTokenStudio' => session('photobox_new_token_studio'),
        ]);
    }

    /** Ubah transaksi. Ringkasan & dashboard dihitung dari tabel ini, jadi pendapatan otomatis ikut menyesuaikan. */
    public function update(Request $request, PhotoboxTransaction $transaction): RedirectResponse
    {
        $data = $request->validate([
            'template_name' => ['nullable', 'string', 'max:255'],
            'method' => ['required', 'in:qris,voucher,free'],
            'amount' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'in:pending,paid,failed,expired,refunded'],
            'paid_at' => ['nullable', 'date'],
        ]);

        $tz = config('app.timezone');
        $paidAt = ! empty($data['paid_at']) ? Carbon::parse($data['paid_at'], $tz) : $transaction->paid_at;
        if ($data['status'] === 'paid' && ! $paidAt) {
            $paidAt = now();
        }

        $transaction->forceFill([
            'template_name' => $data['template_name'] ?: null,
            'method' => $data['method'],
            'amount' => $data['amount'],
            'status' => $data['status'],
            'paid_at' => $paidAt,
            'edited_at' => now(),
        ])->save();

        return back()->with('success', 'Transaksi '.$transaction->order_no.' diperbarui.');
    }

    /** Hapus = soft delete: pendapatan berkurang, dan kiriman ulang dari booth tidak akan membuatnya muncul lagi. */
    public function destroy(PhotoboxTransaction $transaction): RedirectResponse
    {
        $orderNo = $transaction->order_no;
        $transaction->delete();

        return back()->with('success', 'Transaksi '.$orderNo.' dihapus.');
    }

    public function storeStudio(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);
        $studio = new PhotoboxStudio(['name' => $data['name'], 'is_active' => true, 'token_hash' => bin2hex(random_bytes(32))]);
        $studio->save();
        $token = $studio->issueToken();

        return back()->with(['photobox_new_token' => $token, 'photobox_new_token_studio' => $studio->name]);
    }

    public function regenerateToken(PhotoboxStudio $studio): RedirectResponse
    {
        $token = $studio->issueToken();

        return back()->with(['photobox_new_token' => $token, 'photobox_new_token_studio' => $studio->name]);
    }

    public function updateStudio(Request $request, PhotoboxStudio $studio): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100']]);
        $studio->update(['name' => trim($data['name'])]);

        return back()->with('success', 'Nama studio diperbarui.');
    }

    /**
     * Hapus studio. Studio yang sudah punya transaksi ikut menghapus SEMUA transaksinya (pendapatan berkurang),
     * jadi harus dikonfirmasi eksplisit (with_transactions) dan butuh izin photobox-delete. Kalau hanya ingin
     * menghentikan studio, pakai "Nonaktifkan".
     */
    public function destroyStudio(Request $request, PhotoboxStudio $studio): RedirectResponse
    {
        $count = $studio->transactions()->count(); // transaksi yang masih tampil (yang sudah dihapus ikut terhapus diam-diam)
        if ($count > 0) {
            abort_unless($request->user()?->can('photobox-delete'), 403, 'Studio ini punya transaksi; menghapusnya butuh izin hapus transaksi Photobox.');
            if (! $request->boolean('with_transactions')) {
                return back()->withErrors(['studio' => 'Studio ini punya '.$count.' transaksi. Konfirmasi penghapusan beserta transaksinya, atau pilih Nonaktifkan.']);
            }
        }

        $name = $studio->name;
        DB::transaction(fn () => $studio->delete()); // transaksi ikut terhapus lewat foreign key cascade

        return back()->with('success', 'Studio '.$name.' dihapus'.($count > 0 ? ' beserta '.$count.' transaksinya.' : '.'));
    }

    public function toggleStudio(PhotoboxStudio $studio): RedirectResponse
    {
        $studio->update(['is_active' => ! $studio->is_active]);

        return back();
    }
}
