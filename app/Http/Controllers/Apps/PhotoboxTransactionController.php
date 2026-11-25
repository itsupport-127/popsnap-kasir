<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\CashierShift;
use App\Models\PhotoboxStore;
use App\Models\PhotoboxStudio;
use App\Models\PhotoboxTransaction;
use App\Models\Transaction;
use App\Services\CashierShiftService;
use App\Services\GalleryStatus;
use App\Services\PhotoboxDiscountService;
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
            'store_id' => $request->input('store_id'),
            'studio_id' => $request->input('studio_id'),
            'method' => $request->input('method'),
            'status' => $request->input('status'),
            'search' => $request->input('search'),
            'test' => in_array($request->input('test'), ['only', 'hide'], true) ? $request->input('test') : null,
        ];
        [$start, $end] = PhotoboxStats::range($filters['period'], $filters['start_date'], $filters['end_date']);
        $studioId = $filters['studio_id'] ? (int) $filters['studio_id'] : null;

        // Hak lihat: admin/superadmin semua toko & studio; kasir hanya cakupan SHIFT AKTIF-nya (sejak shift dibuka).
        $scope = $this->scopeFor($request);
        $scopeIds = $scope['studio_ids'] ?? null; // null = tanpa batas studio
        if ($scope && $scope['since'] && $start->lt($scope['since'])) {
            $start = $scope['since']->copy();
        }
        $allowedIds = $scopeIds;
        if ($filters['store_id'] !== null && $filters['store_id'] !== '') {
            $inStore = PhotoboxStudio::query()
                ->when($filters['store_id'] === 'none', fn ($q) => $q->whereNull('store_id'), fn ($q) => $q->where('store_id', (int) $filters['store_id']))
                ->pluck('id')->map(fn ($i) => (int) $i)->all();
            $allowedIds = $allowedIds === null ? $inStore : array_values(array_intersect($allowedIds, $inStore));
        }
        if ($studioId && $scopeIds !== null && ! in_array($studioId, $scopeIds, true)) {
            $studioId = null; // studio di luar cakupan diabaikan
        }

        $transactions = PhotoboxTransaction::query()
            ->with('studio:id,name')
            ->where(function ($q) use ($start, $end) {
                // Yang sudah dibayar dihitung menurut waktu bayar; sisanya menurut waktu dibuat di booth
                $q->whereBetween('paid_at', [$start, $end])
                    ->orWhere(fn ($w) => $w->whereNull('paid_at')->whereBetween('booth_created_at', [$start, $end]));
            })
            ->when($studioId, fn ($q) => $q->where('studio_id', $studioId))
            ->when($allowedIds !== null, fn ($q) => $q->whereIn('studio_id', $allowedIds))
            ->when($filters['method'], fn ($q, $v) => $q->where('method', $v))
            ->when($filters['status'], fn ($q, $v) => $q->where('status', $v))
            ->when($filters['test'] === 'only', fn ($q) => $q->where('is_test', true))
            ->when($filters['test'] === 'hide', fn ($q) => $q->real())
            ->when($filters['search'], fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('order_no', 'like', "%{$s}%")
                ->when(strlen($code = strtoupper(preg_replace('/[\s-]+/', '', $s))) >= 4, fn ($c) => $c->orWhere('gallery_url', 'like', "%/s/{$code}%"))
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
                'is_test' => (bool) $t->is_test,
                'paid_at' => $t->paid_at?->toIso8601String(),
                'paid_at_local' => $t->paid_at?->copy()->setTimezone(config('app.timezone'))->format('Y-m-d\TH:i'),
                'edited' => (bool) $t->edited_at,
                'gallery_url' => $t->gallery_url,
                'booth_created_at' => $t->booth_created_at?->toIso8601String(),
                'kasir_reference' => $t->meta['kasir_reference'] ?? null,
                'meta' => ['wa_number' => $t->meta['wa_number'] ?? null, 'wa_name' => $t->meta['wa_name'] ?? null],
            ]);

        // Status galeri (terupload / izin foto) + nomor WhatsApp pelanggan dari transaksi kasir
        $page = $transactions->getCollection();
        $gallery = GalleryStatus::fetch($page->map(fn ($r) => GalleryStatus::tokenFromUrl($r['gallery_url']))->filter()->all());
        // Baris yang belum lengkap di kode link-nya sendiri: cari lewat nomor transaksi (mungkin sudah terunggah dengan kode link lain)
        $incomplete = collect($page->all())->filter(function ($r) use ($gallery) {
            $g = ($t = GalleryStatus::tokenFromUrl($r['gallery_url'])) ? ($gallery[$t] ?? null) : null;

            return ! $g || ! empty($g['expired']) ? false : (($g['files'] ?? 0) === 0 || ($g['pending'] ?? 0) > 0);
        })->merge(collect($page->all())->filter(fn ($r) => ! $r['gallery_url']))->pluck('order_no')->unique()->values()->all();
        $byOrder = GalleryStatus::fetchByOrders($incomplete);
        $phones = Transaction::query()->with('customer:id,name,no_telp')
            ->whereIn('invoice', $page->pluck('kasir_reference')->filter()->unique()->all())
            ->get()->keyBy('invoice');
        $transactions->setCollection($page->map(function ($r) use ($gallery, $phones, $byOrder) {
            $tok = GalleryStatus::tokenFromUrl($r['gallery_url']);
            $g = $tok ? ($gallery[$tok] ?? null) : null;
            $cust = $r['kasir_reference'] ? ($phones[$r['kasir_reference']]->customer ?? null) : null;
            $r['gallery_state'] = GalleryStatus::state((bool) $r['gallery_url'], $g);
            $r['gallery_files'] = $g['files'] ?? 0;
            $r['gallery_pending'] = $g['pending'] ?? 0;
            // Batas waktu link (epoch ms dari galeri) -> ISO, dipakai sapaan WhatsApp. Kosong kalau galeri belum diperbarui / link sudah kedaluwarsa.
            $r['gallery_expires_at'] = (! empty($g['expiresAt']) && empty($g['expired'])) ? \Carbon\Carbon::createFromTimestampMs((int) $g['expiresAt'])->toIso8601String() : null;
            // Kode link (token) untuk pelacakan: sama dengan yang tampil di website galeri
            $r['link_code'] = $tok;
            // Belum lengkap di kode link ini, tapi ada sesi galeri dengan NOMOR TRANSAKSI yang sama dan sudah berisi file
            $r['gallery_alt'] = in_array($r['gallery_state'], [null, 'pending', 'partial'], true)
                ? GalleryStatus::alternative($tok, $byOrder[$r['order_no']] ?? [])
                : null;
            $r['social'] = $g['social'] ?? null;
            // Kontak yang diisi manual oleh CS (tersimpan di transaksi photobox) didahulukan; kalau kosong pakai data pelanggan kasir
            $meta = $r['meta'] ?? [];
            $manualWa = GalleryStatus::waNumber($meta['wa_number'] ?? null);
            $r['customer_name'] = ($manualWa ? ($meta['wa_name'] ?? null) : null) ?: $cust?->name;
            $r['customer_wa'] = $manualWa ?: GalleryStatus::waNumber($cust?->no_telp);
            $r['customer_manual'] = (bool) $manualWa;
            unset($r['meta']);
            unset($r['kasir_reference']);

            return $r;
        }));

        $canManage = $request->user()?->can('photobox-manage') ?? false;

        $studioTx = PhotoboxTransaction::query()
            ->when($scopeIds !== null, fn ($q) => $q->whereIn('studio_id', $scopeIds))
            ->selectRaw("studio_id, COUNT(*) as count, SUM(CASE WHEN status = 'paid' AND is_test = 0 THEN amount ELSE 0 END) as revenue")
            ->groupBy('studio_id')->get()->keyBy('studio_id');

        return Inertia::render('Dashboard/PhotoboxTransactions/Index', [
            'filters' => $filters,
            'range' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'transactions' => $transactions,
            'summary' => PhotoboxStats::summary($start, $end, $studioId, $allowedIds),
            'studios' => PhotoboxStudio::query()->when($scopeIds !== null, fn ($q) => $q->whereIn('id', $scopeIds))->orderBy('name')->get()->map(fn (PhotoboxStudio $s) => [
                'id' => $s->id,
                'tx_count' => (int) ($studioTx[$s->id]->count ?? 0),
                'tx_revenue' => (int) ($studioTx[$s->id]->revenue ?? 0),
                'name' => $s->name,
                'store_id' => $s->store_id ? (int) $s->store_id : null,
                'is_active' => $s->is_active,
                'token_hint' => $scope ? null : $s->token_hint,
                'last_seen_at' => $s->last_seen_at?->toIso8601String(),
                'status' => $s->reportSummary(),
            ]),
            'stores' => PhotoboxStore::query()->withCount('studios')
                ->when($scopeIds !== null, fn ($q) => $q->whereIn('id', PhotoboxStudio::query()->whereIn('id', $scopeIds)->whereNotNull('store_id')->select('store_id')))
                ->orderBy('name')->get()->map(fn (PhotoboxStore $st) => ['id' => (int) $st->id, 'name' => $st->name, 'studios_count' => (int) $st->studios_count]),
            'galleryStatusOn' => GalleryStatus::baseUrl() !== null,
            'testCount' => PhotoboxTransaction::query()->where('is_test', true)->when($studioId, fn ($q) => $q->where('studio_id', $studioId))->when($allowedIds !== null, fn ($q) => $q->whereIn('studio_id', $allowedIds))->count(),
            'scope' => $scope ? ['restricted' => true, 'has_shift' => $scope['has_shift'], 'label' => $scope['label'], 'since' => $scope['since']?->toIso8601String()] : ['restricted' => false, 'has_shift' => true, 'label' => null, 'since' => null],
            'canManage' => $canManage,
            'canEdit' => $request->user()?->can('photobox-edit') ?? false,
            'canDelete' => $request->user()?->can('photobox-delete') ?? false,
            'apiUrl' => url('/api/photobox'),
            'newToken' => session('photobox_new_token'),
            'newTokenStudio' => session('photobox_new_token_studio'),
        ]);
    }

    private function scopeFor(Request $request): ?array
    {
        return $request->user() ? app(CashierShiftService::class)->photoboxScopeFor($request->user()) : null;
    }

    /** Kasir hanya boleh menyentuh transaksi di cakupan shift aktifnya (admin bebas). */
    private function authorizeInScope(Request $request, PhotoboxTransaction $transaction): void
    {
        $scope = $this->scopeFor($request);
        if ($scope === null) {
            return;
        }
        $at = $transaction->paid_at ?? $transaction->booth_created_at ?? $transaction->created_at;
        $ok = $scope['has_shift']
            && ($scope['studio_ids'] === null || in_array((int) $transaction->studio_id, $scope['studio_ids'], true))
            && (! $scope['since'] || ! $at || $at->gte($scope['since']));
        abort_unless($ok, 403, 'Transaksi ini di luar toko/studio shift Anda.');
    }

    /** Ubah transaksi. Ringkasan & dashboard dihitung dari tabel ini, jadi pendapatan otomatis ikut menyesuaikan. */
    /** Cari pelanggan kasir (nama / nomor) untuk mengisi kontak WhatsApp. Hanya nama + nomor yang dikembalikan. */
    public function customerSearch(Request $request): \Illuminate\Http\JsonResponse
    {
        // Tanpa kata kunci = pelanggan TERBARU di atas (seperti pemilih pelanggan di halaman transaksi); dengan kata kunci = cari nama / telepon / kode member.
        $q = trim((string) $request->query('q', ''));
        $query = \App\Models\Customer::query()->orderByDesc('id')->limit(80);
        if ($q !== '') {
            $like = '%'.str_replace(['%', '_'], ['\\%', '\\_'], $q).'%';
            $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('no_telp', 'like', $like)->orWhere('member_code', 'like', $like));
        }
        $rows = $query->get()
            ->map(fn ($c) => [
                'id' => $c->id, 'name' => $c->name, 'wa' => GalleryStatus::waNumber($c->no_telp), 'no_telp' => $c->no_telp,
                'member_code' => $c->member_code, 'is_loyalty_member' => (bool) $c->is_loyalty_member, 'loyalty_tier' => $c->loyalty_tier, 'loyalty_points' => $c->loyalty_points,
            ])
            ->filter(fn ($c) => $c['wa'])->take(30)->values();

        return response()->json($rows);
    }

    /** Simpan nama + nomor WhatsApp tamu untuk satu transaksi photobox (kosongkan nomor = hapus). Tidak mengubah data kasir. */
    public function saveContact(Request $request, PhotoboxTransaction $transaction): RedirectResponse
    {
        $this->authorizeInScope($request, $transaction);
        $data = $request->validate(['name' => 'nullable|string|max:80', 'wa' => 'nullable|string|max:30']);
        $wa = GalleryStatus::waNumber($data['wa'] ?? null);
        if (! empty($data['wa']) && ! $wa) {
            return back()->withErrors(['wa' => 'Nomor WhatsApp tidak valid.']);
        }
        $meta = $transaction->meta ?? [];
        if ($wa) {
            $meta['wa_number'] = $wa;
            $meta['wa_name'] = trim((string) ($data['name'] ?? '')) ?: null;
        } else {
            unset($meta['wa_number'], $meta['wa_name']);
        }
        $transaction->meta = $meta;
        $transaction->save();

        return back();
    }

    public function update(Request $request, PhotoboxTransaction $transaction): RedirectResponse
    {
        $this->authorizeInScope($request, $transaction);
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

        if ($data['status'] !== 'paid') {
            $this->releasePromoQuota($transaction); // refund / batal: kuota kode diskon kembali
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

    /** Tandai / lepas tanda TEST (mis. gateway masih sandbox padahal uang asli). Pendapatan, shift & dashboard otomatis menyesuaikan; booth ikut menerima perubahannya. */
    public function toggleTest(Request $request, PhotoboxTransaction $transaction): RedirectResponse
    {
        $this->authorizeInScope($request, $transaction);
        $data = $request->validate(['is_test' => ['required', 'boolean']]);
        $transaction->forceFill(['is_test' => (bool) $data['is_test'], 'edited_at' => now()])->save();

        return back()->with('success', 'Transaksi '.$transaction->order_no.($transaction->is_test ? ' ditandai TEST (tidak dihitung pendapatan).' : ' ditandai transaksi asli (dihitung pendapatan).'));
    }

    /**
     * Hapus transaksi TEST sekaligus (soft delete, tidak mengubah pendapatan karena memang tidak dihitung).
     * Maksimal 100 per klik supaya booth bisa menarik semua perubahannya (satu halaman perubahan = 200 baris); ulangi kalau masih ada.
     */
    public function destroyTests(Request $request): RedirectResponse
    {
        $studioId = $request->filled('studio_id') ? (int) $request->input('studio_id') : null;
        $scopeIds = $this->scopeFor($request)['studio_ids'] ?? null;
        $restricted = $this->scopeFor($request) !== null;
        $inScope = fn ($q) => $q->when($restricted, fn ($w) => $w->whereIn('studio_id', $scopeIds ?? PhotoboxStudio::query()->pluck('id')->all()));
        $ids = PhotoboxTransaction::query()->where('is_test', true)->tap($inScope)
            ->when($studioId, fn ($q) => $q->where('studio_id', $studioId))
            ->orderBy('id')->limit(100)->pluck('id');
        $deleted = $ids->isEmpty() ? 0 : PhotoboxTransaction::query()->whereIn('id', $ids)->delete();
        $left = PhotoboxTransaction::query()->where('is_test', true)->tap($inScope)->when($studioId, fn ($q) => $q->where('studio_id', $studioId))->count();

        return back()->with('success', $deleted.' transaksi TEST dihapus'.($left > 0 ? ", masih ada {$left}: klik lagi untuk melanjutkan." : '.'));
    }

    /** Kembalikan kuota kode diskon yang dipakai transaksi ini (sekali saja; tanda promo_counted dimatikan). */
    private function releasePromoQuota(PhotoboxTransaction $transaction): void
    {
        $meta = $transaction->meta ?? [];
        if (empty($meta['promo_counted']) || empty($meta['promo_code'])) {
            return;
        }
        app(PhotoboxDiscountService::class)->release((string) $meta['promo_code']);
        $meta['promo_counted'] = false;
        $transaction->meta = $meta;
        $transaction->save();
    }

    /** Hapus = soft delete: pendapatan berkurang, dan kiriman ulang dari booth tidak akan membuatnya muncul lagi. */
    public function destroy(Request $request, PhotoboxTransaction $transaction): RedirectResponse
    {
        $this->authorizeInScope($request, $transaction);
        $orderNo = $transaction->order_no;
        $this->releasePromoQuota($transaction);
        $transaction->delete();

        return back()->with('success', 'Transaksi '.$orderNo.' dihapus.');
    }

    public function storeStudio(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'store_id' => ['nullable', 'integer', 'exists:photobox_stores,id'],
        ]);
        $studio = new PhotoboxStudio(['name' => $data['name'], 'store_id' => $data['store_id'] ?? null, 'is_active' => true, 'token_hash' => bin2hex(random_bytes(32))]);
        $studio->save();
        $token = $studio->issueToken();

        return back()->with(['photobox_new_token' => $token, 'photobox_new_token_studio' => $studio->name]);
    }

    public function regenerateToken(PhotoboxStudio $studio): RedirectResponse
    {
        $token = $studio->issueToken();

        return back()->with(['photobox_new_token' => $token, 'photobox_new_token_studio' => $studio->name]);
    }

    /** Ubah nama studio dan/atau pindahkan ke toko lain (store_id kosong = "Tanpa toko"). */
    public function updateStudio(Request $request, PhotoboxStudio $studio): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'store_id' => ['sometimes', 'nullable', 'integer', 'exists:photobox_stores,id'],
        ]);

        $update = [];
        if (array_key_exists('name', $data)) {
            $update['name'] = trim($data['name']);
        }
        if (array_key_exists('store_id', $data)) {
            $update['store_id'] = $data['store_id'] ?: null;
        }
        if ($update === []) {
            return back();
        }
        $studio->update($update);

        return back()->with('success', array_key_exists('store_id', $update) && ! array_key_exists('name', $update) ? 'Toko studio diperbarui.' : 'Studio diperbarui.');
    }

    public function storeStore(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:photobox_stores,name']]);
        PhotoboxStore::create(['name' => trim($data['name'])]);

        return back()->with('success', 'Toko ditambahkan.');
    }

    public function updateStore(Request $request, PhotoboxStore $store): RedirectResponse
    {
        $data = $request->validate(['name' => ['required', 'string', 'max:100', 'unique:photobox_stores,name,'.$store->id]]);
        $store->update(['name' => trim($data['name'])]);

        return back()->with('success', 'Nama toko diperbarui.');
    }

    /**
     * Hapus toko: studionya menjadi "Tanpa toko" (transaksi tidak tersentuh). Ditolak selama ada shift TERBUKA yang memegang toko ini,
     * supaya shift itu tidak diam-diam kehilangan cakupannya.
     */
    public function destroyStore(PhotoboxStore $store): RedirectResponse
    {
        $open = CashierShift::query()->where('status', CashierShift::STATUS_OPEN)->where('photobox_store_id', $store->id)->count();
        if ($open > 0) {
            return back()->withErrors(['store' => 'Toko ini sedang dipegang '.$open.' shift yang masih terbuka. Tutup shift itu dulu.']);
        }

        $name = $store->name;
        DB::transaction(fn () => $store->delete());

        return back()->with('success', 'Toko '.$name.' dihapus. Studionya menjadi "Tanpa toko".');
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
