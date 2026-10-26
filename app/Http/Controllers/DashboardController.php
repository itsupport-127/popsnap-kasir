<?php

namespace App\Http\Controllers;

use App\Models\CashierShift;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Profit;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Services\CashierShiftService;
use App\Services\PhotoboxStats;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request, CashierShiftService $cashierShiftService)
    {
        // ── Filter Waktu ────────────────────────────────────────────────────
        $period = $request->input('period', 'today'); // today|week|month|year|custom
        $customStart = $request->input('start_date');
        $customEnd   = $request->input('end_date');

        [$startDate, $endDate, $prevStart, $prevEnd] = $this->getPeriodRange($period, $customStart, $customEnd);

        // ── Stats periode ini ────────────────────────────────────────────────
        // Catatan: hanya transaksi payment_status='paid' yang dihitung sebagai
        // omzet nyata. Transaksi 'pending' (QRIS belum dibayar) atau 'failed'
        // (gagal/expired) tidak boleh masuk hitungan — konsisten dengan
        // CashierShiftService & SalesReturnController yang sudah benar duluan.
        $sales        = Transaction::where('payment_status', 'paid')->whereBetween('created_at', [$startDate, $endDate])->sum('grand_total');
        $profit       = Profit::whereHas('transaction', fn ($q) => $q->where('payment_status', 'paid'))->whereBetween('created_at', [$startDate, $endDate])->sum('total');
        $transactions = Transaction::where('payment_status', 'paid')->whereBetween('created_at', [$startDate, $endDate])->count();
        $avgOrder     = Transaction::where('payment_status', 'paid')->whereBetween('created_at', [$startDate, $endDate])->avg('grand_total') ?? 0;
        $newCustomers = Customer::whereBetween('created_at', [$startDate, $endDate])->count();

        // ── Stats periode sebelumnya (untuk perbandingan) ────────────────────
        $prevSales        = Transaction::where('payment_status', 'paid')->whereBetween('created_at', [$prevStart, $prevEnd])->sum('grand_total');
        $prevProfit       = Profit::whereHas('transaction', fn ($q) => $q->where('payment_status', 'paid'))->whereBetween('created_at', [$prevStart, $prevEnd])->sum('total');
        $prevTransactions = Transaction::where('payment_status', 'paid')->whereBetween('created_at', [$prevStart, $prevEnd])->count();

        // ── Photobox (sinkron dari app booth) digabung ke angka utama ───────────────
        // Pendapatan & transaksi photobox ikut dijumlahkan dengan toko. Profit tetap hanya toko (photobox belum punya data modal).
        // Detail transaksi: menu "Riwayat Transaksi" (toko) dan "Transaksi Photobox" (studio).
        $storeSales = (int) $sales;
        $storeTransactions = (int) $transactions;
        $photobox = null;
        $photoboxTrendRows = [];
        $monthPhotoboxSales = 0;
        if ($request->user()?->can('photobox-access') && Schema::hasTable('photobox_transactions')) {
            $photobox = PhotoboxStats::summary($startDate, $endDate);
            $prevPhotobox = PhotoboxStats::summary($prevStart, $prevEnd);
            $sales        += $photobox['revenue'];
            $transactions += $photobox['transactions'];
            $prevSales        += $prevPhotobox['revenue'];
            $prevTransactions += $prevPhotobox['transactions'];
            $avgOrder = $transactions > 0 ? $sales / $transactions : 0;
            $photoboxTrendRows = PhotoboxStats::trend($period, $startDate, $endDate);
            [$ms, $me] = PhotoboxStats::range('month');
            $monthPhotoboxSales = PhotoboxStats::summary($ms, $me)['revenue'];
        }

        $salesGrowth        = $prevSales > 0 ? round((($sales - $prevSales) / $prevSales) * 100, 1) : null;
        $profitGrowth       = $prevProfit > 0 ? round((($profit - $prevProfit) / $prevProfit) * 100, 1) : null;
        $transactionGrowth  = $prevTransactions > 0 ? round((($transactions - $prevTransactions) / $prevTransactions) * 100, 1) : null;

        // ── Summary (Total Kategori & Total Produk tetap global — konsep
        // katalog, bukan aktivitas per-waktu, jadi wajar tidak ikut filter) ──
        $totalCategories  = Category::count();
        $totalProducts    = Product::count();
        // Total Transaksi & Total Pelanggan SEKARANG ikut filter periode,
        // sebelumnya ini selalu hitung all-time walau period diganti-ganti.
        $totalTransactions = $transactions;
        $totalCustomers    = Transaction::where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('customer_id')
            ->distinct('customer_id')
            ->count('customer_id');

        // ── Monthly Target ───────────────────────────────────────────────────
        $monthlyTarget     = Setting::where('key', 'monthly_sales_target')->first()?->value ?? 0;
        $currentMonthSales = Transaction::where('payment_status', 'paid')
            ->whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('grand_total') + $monthPhotoboxSales;

        // ── Revenue Trend (sesuai period) ────────────────────────────────────
        $revenueTrend = $this->mergeTrend($this->getRevenueTrend($period, $startDate, $endDate), $photoboxTrendRows, $period);

        // ── Top Products (periode ini) — semua produk yang terjual, + breakdown per kategori ──
        $topProductsRaw = TransactionDetail::select(
                'transaction_details.product_id',
                DB::raw('SUM(transaction_details.qty) as qty'),
                DB::raw('SUM(transaction_details.price) as total')
            )
            ->join('products', 'products.id', '=', 'transaction_details.product_id')
            ->join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->with('product:id,title,sku,category_id', 'product.category:id,name')
            ->where('transactions.payment_status', 'paid')
            ->whereBetween('transaction_details.created_at', [$startDate, $endDate])
            ->groupBy('transaction_details.product_id')
            ->orderByDesc('qty')
            ->get();

        $topProducts = $topProductsRaw
            ->map(fn ($d) => [
                'name'     => $d->product?->title ?? 'Produk terhapus',
                'sku'      => $d->product?->sku ?? '-',
                'category' => $d->product?->category?->name ?? 'Tanpa Kategori',
                'qty'      => (int) $d->qty,
                'total'    => (int) $d->total,
            ])
            ->values();

        // Kelompokkan per kategori, biar kelihatan kategori (mis. paket studio photo)
        // mana yang paling diminati, bukan cuma per-produk.
        $topProductsByCategory = $topProducts
            ->groupBy('category')
            ->map(function ($items, $category) {
                return [
                    'category' => $category,
                    'total_qty' => (int) $items->sum('qty'),
                    'total_sales' => (int) $items->sum('total'),
                    'products' => $items->values(),
                ];
            })
            ->sortByDesc('total_qty')
            ->values();

        // ── Top Customers (periode ini) ──────────────────────────────────────
        $topCustomers = Transaction::select('customer_id', DB::raw('COUNT(*) as orders'), DB::raw('SUM(grand_total) as total'))
            ->with('customer:id,name')
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('customer_id')
            ->groupBy('customer_id')
            ->orderByDesc('total')
            ->take(5)
            ->get()
            ->map(fn($r) => [
                'name'   => $r->customer?->name ?? 'Pelanggan',
                'orders' => (int) $r->orders,
                'total'  => (int) $r->total,
            ]);

        // ── Metode Pembayaran (periode ini) ──────────────────────────────────
        // Transaksi dual_cash_qris tidak dihitung sebagai satu baris utuh,
        // melainkan dipecah: porsi cash masuk ke bucket "cash" dan porsi qris
        // masuk ke bucket "qris", supaya konsisten dengan cash_sales_total di
        // laporan tutup kasir (CashierShiftService::calculateSummary).
        $paymentMethods = Transaction::select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(grand_total) as total'))
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('payment_method', '!=', 'dual_cash_qris')
            ->groupBy('payment_method')
            ->get()
            ->map(fn($r) => [
                'method' => $r->payment_method ?? 'cash',
                'count'  => (int) $r->count,
                'total'  => (int) $r->total,
            ])
            ->keyBy('method');

        $dualSplit = Transaction::where('payment_method', 'dual_cash_qris')
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('COUNT(*) as count, COALESCE(SUM(dual_cash), 0) as cash_total, COALESCE(SUM(dual_qris), 0) as qris_total')
            ->first();

        if ($dualSplit && (int) $dualSplit->count > 0) {
            foreach ([
                'cash' => (int) $dualSplit->cash_total,
                'qris' => (int) $dualSplit->qris_total,
            ] as $method => $amount) {
                $existing = $paymentMethods->get($method, ['method' => $method, 'count' => 0, 'total' => 0]);
                $paymentMethods->put($method, [
                    'method' => $method,
                    'count'  => $existing['count'] + (int) $dualSplit->count,
                    'total'  => $existing['total'] + $amount,
                ]);
            }
        }

        // QRIS di booth photobox ikut ke bucket "qris"; voucher bernilai (opsi "hitung voucher sebagai pendapatan") ke bucket "voucher"
        foreach ($photobox['byMethod'] ?? [] as $m) {
            if ($m['method'] === 'free' || $m['total'] <= 0) {
                continue;
            }
            $existing = $paymentMethods->get($m['method'], ['method' => $m['method'], 'count' => 0, 'total' => 0]);
            $paymentMethods->put($m['method'], [
                'method' => $m['method'],
                'count'  => $existing['count'] + $m['count'],
                'total'  => $existing['total'] + $m['total'],
            ]);
        }
        $paymentMethods = $paymentMethods->values();

        // ── Rincian per sumber: kasir toko + tiap studio photobox (detail di menu masing-masing) ──
        $sources = collect([[
            'key' => 'store', 'name' => 'Kasir Toko', 'type' => 'store',
            'transactions' => $storeTransactions, 'sessions' => null, 'revenue' => $storeSales,
        ]]);
        foreach ($photobox['byStudio'] ?? [] as $st) {
            $sources->push([
                'key' => 'studio-'.$st['id'], 'name' => $st['name'], 'type' => 'studio', 'studio_id' => $st['id'],
                'transactions' => $st['transactions'], 'sessions' => $st['sessions'], 'revenue' => $st['revenue'],
            ]);
        }

        // ── Low Stock & Slow Moving (tidak tergantung filter) ────────────────
        $lowStockProducts = Product::where('stock', '<', 10)->orderBy('stock')->take(5)->get()
            ->map(fn($p) => ['name' => $p->title, 'stock' => (int) $p->stock]);

        $recentlySoldIds = TransactionDetail::join('transactions', 'transactions.id', '=', 'transaction_details.transaction_id')
            ->where('transactions.payment_status', 'paid')
            ->where('transaction_details.created_at', '>=', Carbon::now()->subDays(30))
            ->distinct()
            ->pluck('transaction_details.product_id');
        $slowMovingProducts = Product::whereNotIn('id', $recentlySoldIds)->where('stock', '>', 0)->take(5)->get()
            ->map(fn($p) => ['name' => $p->title, 'stock' => (int) $p->stock]);

        // ── Recent Transactions ──────────────────────────────────────────────
        $recentTransactions = Transaction::with('cashier:id,name', 'customer:id,name')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->latest()->take(5)->get()
            ->map(fn($t) => [
                'invoice'  => $t->invoice,
                'date'     => Carbon::parse($t->created_at)->setTimezone('Asia/Jakarta')->format('d M Y H:i'),
                'customer' => $t->customer?->name ?? '-',
                'cashier'  => $t->cashier?->name ?? '-',
                'total'    => (int) $t->grand_total,
                'method'   => $t->payment_method ?? 'cash',
                'status'   => $t->payment_status ?? 'paid',
            ]);

        // ── Active Shifts ────────────────────────────────────────────────────
        $activeShifts = CashierShift::with('user:id,name')->open()->latest('opened_at')->take(5)->get()
            ->map(function (CashierShift $shift) use ($cashierShiftService) {
                $summary = $cashierShiftService->calculateSummary($shift);
                return [
                    'id'                 => $shift->id,
                    'opened_at'          => optional($shift->opened_at)?->toISOString(),
                    'opening_cash'       => (int) $shift->opening_cash,
                    'expected_cash'      => $summary['expected_cash'],
                    'transactions_count' => $summary['transactions_count'],
                    'cash_sales_total'   => $summary['cash_sales_total'],
                    'user'               => ['id' => $shift->user?->id, 'name' => $shift->user?->name],
                ];
            })->values();

        return Inertia::render('Dashboard/Index', [
            // Photobox: null kalau user tidak punya photobox-access
            'photobox'    => $photobox ? [
                'revenue' => (int) $photobox['revenue'], 'transactions' => (int) $photobox['transactions'], 'sessions' => (int) $photobox['sessions'],
            ] : null,
            'storeSales'        => $storeSales,
            'storeTransactions' => $storeTransactions,
            'sources'     => $sources->values(),
            // Filter info
            'period'      => $period,
            'startDate'   => $startDate->toDateString(),
            'endDate'     => $endDate->toDateString(),
            // Stats periode
            'sales'             => (int) $sales,
            'profit'            => (int) $profit,
            'transactions'      => (int) $transactions,
            'avgOrder'          => (int) round($avgOrder),
            'newCustomers'      => (int) $newCustomers,
            'salesGrowth'       => $salesGrowth,
            'profitGrowth'      => $profitGrowth,
            'transactionGrowth' => $transactionGrowth,
            // Summary global
            'totalCategories'   => $totalCategories,
            'totalProducts'     => $totalProducts,
            'totalTransactions' => $totalTransactions,
            'totalCustomers'    => $totalCustomers,
            'monthlyTarget'     => (int) $monthlyTarget,
            'currentMonthSales' => (int) $currentMonthSales,
            // Data
            'revenueTrend'       => $revenueTrend,
            'topProducts'        => $topProducts,
            'topProductsByCategory' => $topProductsByCategory,
            'topCustomers'       => $topCustomers,
            'paymentMethods'     => $paymentMethods,
            'lowStockProducts'   => $lowStockProducts,
            'slowMovingProducts' => $slowMovingProducts,
            'recentTransactions' => $recentTransactions,
            'activeShifts'       => $activeShifts,
            // Legacy props (backward compat) — sekarang mengikuti periode yang dipilih,
            // bukan selalu "hari ini". Sebelumnya field ini hardcode ke Carbon::today()
            // sehingga angka di card tidak berubah walau ganti filter Minggu/Bulan/dst.
            'todaySales'         => (int) $sales,
            'todayProfit'        => (int) $profit,
            'todayTransactions'  => (int) $transactions,
            'totalRevenue'       => (int) Transaction::where('payment_status', 'paid')->sum('grand_total'),
            'totalProfit'        => (int) Profit::whereHas('transaction', fn ($q) => $q->where('payment_status', 'paid'))->sum('total'),
            'averageOrder'       => (int) round(Transaction::where('payment_status', 'paid')->avg('grand_total') ?? 0),
        ]);
    }

    private function getPeriodRange(string $period, ?string $customStart, ?string $customEnd): array
    {
        $now = Carbon::now()->setTimezone('Asia/Jakarta');

        switch ($period) {
            case 'today':
                $start = $now->copy()->startOfDay();
                $end   = $now->copy()->endOfDay();
                $prevStart = $now->copy()->subDay()->startOfDay();
                $prevEnd   = $now->copy()->subDay()->endOfDay();
                break;
            case 'week':
                $start = $now->copy()->startOfWeek();
                $end   = $now->copy()->endOfWeek();
                $prevStart = $now->copy()->subWeek()->startOfWeek();
                $prevEnd   = $now->copy()->subWeek()->endOfWeek();
                break;
            case 'month':
                $start = $now->copy()->startOfMonth();
                $end   = $now->copy()->endOfMonth();
                $prevStart = $now->copy()->subMonth()->startOfMonth();
                $prevEnd   = $now->copy()->subMonth()->endOfMonth();
                break;
            case 'year':
                $start = $now->copy()->startOfYear();
                $end   = $now->copy()->endOfYear();
                $prevStart = $now->copy()->subYear()->startOfYear();
                $prevEnd   = $now->copy()->subYear()->endOfYear();
                break;
            case 'all':
                // "Semua" — dari transaksi paling awal sampai sekarang. Tidak ada
                // "periode sebelumnya" yang masuk akal buat dibandingkan, jadi
                // prevStart/prevEnd sengaja diarahkan ke rentang yang pasti kosong
                // (sebelum toko ini beroperasi) supaya growth% otomatis null,
                // bukan malah nampilin angka perbandingan yang menyesatkan.
                $earliest = Transaction::min('created_at');
                $start = $earliest ? Carbon::parse($earliest)->startOfDay() : $now->copy()->startOfDay();
                $end   = $now->copy()->endOfDay();
                $prevStart = Carbon::parse('2000-01-01')->startOfDay();
                $prevEnd   = Carbon::parse('2000-01-02')->endOfDay();
                break;
            case 'custom':
                $start = Carbon::parse($customStart)->startOfDay();
                $end   = Carbon::parse($customEnd)->endOfDay();
                // Jaga-jaga kalau tanggal "sampai" ternyata lebih awal dari
                // "dari" (input nggak tervalidasi/dikirim manual) — tukar biar
                // range-nya tetap valid, bukan malah nampilin data kosong.
                if ($end->lt($start)) {
                    [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
                }
                $diff  = $start->diffInDays($end);
                $prevStart = $start->copy()->subDays($diff + 1)->startOfDay();
                $prevEnd   = $start->copy()->subDay()->endOfDay();
                break;
            default:
                $start = $now->copy()->startOfDay();
                $end   = $now->copy()->endOfDay();
                $prevStart = $now->copy()->subDay()->startOfDay();
                $prevEnd   = $now->copy()->subDay()->endOfDay();
        }

        return [$start, $end, $prevStart, $prevEnd];
    }

    /** Gabungkan tren toko + photobox: bucket yang sama dijumlahkan, yang hanya ada di salah satu tetap tampil, urut waktu. */
    private function mergeTrend(array $store, array $photobox, string $period): array
    {
        if (! $photobox) {
            return $store;
        }
        $merged = [];
        foreach ($store as $row) {
            $merged[$row['date']] = $row;
        }
        foreach ($photobox as $key => $row) {
            if (isset($merged[$key])) {
                $merged[$key]['total'] += $row['total'];
            } else {
                $merged[$key] = $row;
            }
        }
        $order = function (string $date) use ($period): int {
            if ($period === 'year' || $period === 'all') {
                [$y, $m] = array_map('intval', explode('-', $date));

                return $y * 100 + $m;
            }
            if ($period === 'today') {
                return (int) $date;
            }

            return (int) strtotime($date);
        };
        uksort($merged, fn ($a, $b) => $order((string) $a) <=> $order((string) $b));

        return array_values($merged);
    }

    private function getRevenueTrend(string $period, Carbon $start, Carbon $end): array
    {
        if ($period === 'year' || $period === 'all') {
            // Group by month — dipakai juga buat "Semua" karena rentangnya bisa
            // bertahun-tahun, jadi per-bulan lebih enak dibaca daripada per-hari.
            return Transaction::selectRaw('YEAR(created_at) as yr, MONTH(created_at) as mo, SUM(grand_total) as total')
                ->where('payment_status', 'paid')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('yr', 'mo')
                ->orderBy('yr')->orderBy('mo')
                ->get()
                ->map(fn($r) => [
                    'date'  => "{$r->yr}-{$r->mo}",
                    'label' => Carbon::createFromDate($r->yr, $r->mo, 1)->isoFormat('MMM YY'),
                    'total' => (int) $r->total,
                ])->toArray();
        }

        if ($period === 'month' || $period === 'custom') {
            // Group by date
            return Transaction::selectRaw('DATE(created_at) as date, SUM(grand_total) as total')
                ->where('payment_status', 'paid')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('date')->orderBy('date')
                ->get()
                ->map(fn($r) => [
                    'date'  => $r->date,
                    'label' => Carbon::parse($r->date)->isoFormat('D MMM'),
                    'total' => (int) $r->total,
                ])->toArray();
        }

        if ($period === 'week') {
            // Group by date (7 days)
            return Transaction::selectRaw('DATE(created_at) as date, SUM(grand_total) as total')
                ->where('payment_status', 'paid')
                ->whereBetween('created_at', [$start, $end])
                ->groupBy('date')->orderBy('date')
                ->get()
                ->map(fn($r) => [
                    'date'  => $r->date,
                    'label' => Carbon::parse($r->date)->isoFormat('ddd D'),
                    'total' => (int) $r->total,
                ])->toArray();
        }

        // today — group by hour
        return Transaction::selectRaw('HOUR(created_at) as hr, SUM(grand_total) as total')
            ->where('payment_status', 'paid')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('hr')->orderBy('hr')
            ->get()
            ->map(fn($r) => [
                'date'  => "{$r->hr}:00",
                'label' => str_pad($r->hr, 2, '0', STR_PAD_LEFT) . ':00',
                'total' => (int) $r->total,
            ])->toArray();
    }
}