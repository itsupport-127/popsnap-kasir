<?php

namespace App\Http\Middleware;

use App\Models\CashierShift;
use App\Models\Payable;
use App\Models\Product;
use App\Models\Receivable;
use App\Services\CashierShiftService;
use App\Services\PayableAgingService;
use App\Services\ReceivableService;
use App\Support\ProductionSecurityBaseline;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public const HEAVY_TTL = 20; // detik

    public const STORE_KEY = 'shared.store_profile';

    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Data bersama untuk SEMUA halaman. Dulu semuanya dihitung ulang tiap pindah menu (15+ query: stok, piutang, hutang,
     * aging, shift kasir, 7x pengaturan toko + cek tabel), itu yang membuat navigasi terasa lambat.
     * Sekarang: (1) dibungkus closure = hanya dihitung bila memang dikirim (polling "only" tidak ikut menghitungnya),
     * (2) hasilnya di-cache singkat per pengguna.
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $heavy = null;
        $getHeavy = function () use ($user, &$heavy) {
            if ($heavy !== null) {
                return $heavy;
            }
            if (! $user) {
                return $heavy = [];
            }

            return $heavy = Cache::remember('shared.heavy.u'.$user->id, self::HEAVY_TTL, fn () => $this->buildHeavy($user->id));
        };

        $stepUp = function () use ($request) {
            $confirmedAt = (int) $request->session()->get('auth.password_confirmed_at', 0);

            return $confirmedAt > 0
                ? now()->setTimestamp($confirmedAt + (int) config('auth.password_timeout', 900))->toISOString()
                : null;
        };

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
                'permissions' => $user ? $user->getPermissions() : [],
                'super' => $user ? $user->isSuperAdmin() : false,
            ],
            'lowStockNotifications' => fn () => $getHeavy()['lowStock'] ?? [],
            'receivableNotifications' => fn () => $getHeavy()['receivable'] ?? [],
            'payableNotifications' => fn () => $getHeavy()['payable'] ?? [],
            'payableAgingSummary' => fn () => $getHeavy()['payableAging'] ?? null,
            'receivableAgingSummary' => fn () => $getHeavy()['receivableAging'] ?? null,
            'activeCashierShift' => fn () => $getHeavy()['shift'] ?? null,
            'storeProfile' => fn () => Cache::remember(self::STORE_KEY, 300, fn () => $this->storeProfile()),
            'security' => fn () => [
                'warnings' => $user ? ProductionSecurityBaseline::issues() : [],
                'publicRegistrationEnabled' => config('security.auth.public_registration'),
                'stepUpFreshUntil' => $user ? $stepUp() : null,
            ],
        ];
    }

    /** Hasil berat per pengguna; di-cache HEAVY_TTL detik (notifikasi boleh telat beberapa detik). */
    private function buildHeavy(int $userId): array
    {
        $lowStock = Product::where('stock', '<=', 0)
            ->whereNotExists(function ($query) use ($userId) {
                $query->selectRaw('1')
                    ->from('product_notification_reads as pr')
                    ->whereColumn('pr.product_id', 'products.id')
                    ->where('pr.user_id', $userId)
                    ->whereColumn('pr.updated_at', '>=', 'products.updated_at');
            })
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get(['id', 'title', 'stock', 'updated_at'])
            ->map(fn ($product) => [
                'id' => $product->id,
                'title' => $product->title,
                'stock' => (int) $product->stock,
                'time' => optional($product->updated_at)->diffForHumans(),
            ])->all();

        $due = function ($model, array $cols, string $label, string $numberCol) {
            return $model::whereNot('status', 'paid')
                ->whereNotNull('due_date')
                ->whereDate('due_date', '<=', now()->addDays(3))
                ->orderBy('due_date')
                ->limit(5)
                ->get($cols)
                ->map(function ($item) use ($label, $numberCol) {
                    $remaining = max(0, ($item->total ?? 0) - ($item->paid ?? 0));

                    return [
                        'id' => $item->id,
                        'title' => "{$label}: {$item->{$numberCol}}",
                        'subtitle' => 'Sisa '.number_format($remaining, 0, ',', '.'),
                        'time' => optional($item->due_date)->diffForHumans(),
                        'status' => $item->status,
                        'aging_bucket' => $item->aging_bucket,
                    ];
                })->all();
        };

        $activeShift = CashierShift::query()
            ->with('user:id,name')
            ->open()
            ->where('user_id', $userId)
            ->latest('opened_at')
            ->first();

        return [
            'lowStock' => $lowStock,
            'receivable' => $due(Receivable::class, ['id', 'invoice', 'customer_id', 'due_date', 'total', 'paid', 'status'], 'Piutang', 'invoice'),
            'payable' => $due(Payable::class, ['id', 'document_number', 'due_date', 'total', 'paid', 'status'], 'Hutang', 'document_number'),
            'payableAging' => (new PayableAgingService)->getAgingSummary(),
            'receivableAging' => (new ReceivableService)->getAgingSummary(),
            'shift' => $activeShift ? app(CashierShiftService::class)->summarizeForDisplay($activeShift) : null,
        ];
    }

    private function storeProfile(): array
    {
        $profile = ['name' => 'Toko Anda', 'logo' => null, 'address' => '', 'phone' => '', 'email' => '', 'website' => '', 'city' => ''];

        if (! Schema::hasTable('settings')) {
            return $profile;
        }

        $v = \App\Models\Setting::query()
            ->whereIn('key', ['store_logo', 'store_name', 'store_address', 'store_phone', 'store_email', 'store_website', 'store_city'])
            ->pluck('value', 'key');

        $logo = $v['store_logo'] ?? null;
        if ($logo && ! str_starts_with($logo, 'http') && ! str_starts_with($logo, '/storage')) {
            $logo = asset('storage/'.ltrim($logo, '/'));
        }

        return [
            'name' => $v['store_name'] ?? 'Toko Anda',
            'logo' => $logo,
            'address' => $v['store_address'] ?? '',
            'phone' => $v['store_phone'] ?? '',
            'email' => $v['store_email'] ?? '',
            'website' => $v['store_website'] ?? '',
            'city' => $v['store_city'] ?? '',
        ];
    }
}
