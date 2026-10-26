<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\PhotoboxStudio;
use App\Models\PhotoboxTransaction;
use App\Services\PhotoboxStats;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                'booth_created_at' => $t->booth_created_at?->toIso8601String(),
            ]);

        $canManage = $request->user()?->can('photobox-manage') ?? false;

        return Inertia::render('Dashboard/PhotoboxTransactions/Index', [
            'filters' => $filters,
            'range' => ['start' => $start->toDateString(), 'end' => $end->toDateString()],
            'transactions' => $transactions,
            'summary' => PhotoboxStats::summary($start, $end, $studioId),
            'studios' => PhotoboxStudio::query()->orderBy('name')->get()->map(fn (PhotoboxStudio $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'is_active' => $s->is_active,
                'token_hint' => $s->token_hint,
                'last_seen_at' => $s->last_seen_at?->toIso8601String(),
            ]),
            'canManage' => $canManage,
            'apiUrl' => url('/api/photobox'),
            'newToken' => session('photobox_new_token'),
            'newTokenStudio' => session('photobox_new_token_studio'),
        ]);
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

    public function toggleStudio(PhotoboxStudio $studio): RedirectResponse
    {
        $studio->update(['is_active' => ! $studio->is_active]);

        return back();
    }
}
