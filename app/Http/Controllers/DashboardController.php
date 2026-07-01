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
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
        $sales        = Transaction::whereBetween('created_at', [$startDate, $endDate])->sum('grand_total');
        $profit       = Profit::whereBetween('created_at', [$startDate, $endDate])->sum('total');
        $transactions = Transaction::whereBetween('created_at', [$startDate, $endDate])->count();
        $avgOrder     = Transaction::whereBetween('created_at', [$startDate, $endDate])->avg('grand_total') ?? 0;
        $newCustomers = Customer::whereBetween('created_at', [$startDate, $endDate])->count();

        // ── Stats periode sebelumnya (untuk perbandingan) ────────────────────
        $prevSales        = Transaction::whereBetween('created_at', [$prevStart, $prevEnd])->sum('grand_total');
        $prevProfit       = Profit::whereBetween('created_at', [$prevStart, $prevEnd])->sum('total');
        $prevTransactions = Transaction::whereBetween('created_at', [$prevStart, $prevEnd])->count();

        $salesGrowth        = $prevSales > 0 ? round((($sales - $prevSales) / $prevSales) * 100, 1) : null;
        $profitGrowth       = $prevProfit > 0 ? round((($profit - $prevProfit) / $prevProfit) * 100, 1) : null;
        $transactionGrowth  = $prevTransactions > 0 ? round((($transactions - $prevTransactions) / $prevTransactions) * 100, 1) : null;

        // ── Summary total keseluruhan (tidak tergantung filter) ──────────────
        $totalCategories  = Category::count();
        $totalProducts    = Product::count();
        $totalTransactions = Transaction::count();
        $totalCustomers   = Customer::count();

        // ── Monthly Target ───────────────────────────────────────────────────
        $monthlyTarget     = Setting::where('key', 'monthly_sales_target')->first()?->value ?? 0;
        $currentMonthSales = Transaction::whereMonth('created_at', Carbon::now()->month)
            ->whereYear('created_at', Carbon::now()->year)
            ->sum('grand_total');

        // ── Revenue Trend (sesuai period) ────────────────────────────────────
        $revenueTrend = $this->getRevenueTrend($period, $startDate, $endDate);

        // ── Top Products (periode ini) ───────────────────────────────────────
        $topProducts = TransactionDetail::select('product_id', DB::raw('SUM(qty) as qty'), DB::raw('SUM(price) as total'))
            ->with('product:id,title,sku')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('product_id')
            ->orderByDesc('qty')
            ->take(5)
            ->get()
            ->map(fn($d) => [
                'name'  => $d->product?->title ?? 'Produk terhapus',
                'sku'   => $d->product?->sku ?? '-',
                'qty'   => (int) $d->qty,
                'total' => (int) $d->total,
            ]);

        // ── Top Customers (periode ini) ──────────────────────────────────────
        $topCustomers = Transaction::select('customer_id', DB::raw('COUNT(*) as orders'), DB::raw('SUM(grand_total) as total'))
            ->with('customer:id,name')
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
        $paymentMethods = Transaction::select('payment_method', DB::raw('COUNT(*) as count'), DB::raw('SUM(grand_total) as total'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('payment_method')
            ->get()
            ->map(fn($r) => [
                'method' => $r->payment_method ?? 'cash',
                'count'  => (int) $r->count,
                'total'  => (int) $r->total,
            ]);

        // ── Low Stock & Slow Moving (tidak tergantung filter) ────────────────
        $lowStockProducts = Product::where('stock', '<', 10)->orderBy('stock')->take(5)->get()
            ->map(fn($p) => ['name' => $p->title, 'stock' => (int) $p->stock]);

        $recentlySoldIds = TransactionDetail::where('created_at', '>=', Carbon::now()->subDays(30))->distinct()->pluck('product_id');
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

        // ── Top Locations ────────────────────────────────────────────────────
        $topLocations = Transaction::join('customers', 'transactions.customer_id', '=', 'customers.id')
            ->select('customers.village_name', DB::raw('COUNT(*) as orders'))
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->whereNotNull('customers.village_name')
            ->groupBy('customers.village_name')
            ->orderByDesc('orders')
            ->take(5)
            ->get()
            ->map(fn($r) => ['name' => $r->village_name ?? 'Lainnya', 'orders' => (int) $r->orders]);

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
            'topCustomers'       => $topCustomers,
            'paymentMethods'     => $paymentMethods,
            'lowStockProducts'   => $lowStockProducts,
            'slowMovingProducts' => $slowMovingProducts,
            'recentTransactions' => $recentTransactions,
            'topLocations'       => $topLocations,
            'activeShifts'       => $activeShifts,
            // Legacy props (backward compat)
            'todaySales'         => (int) Transaction::whereDate('created_at', Carbon::today())->sum('grand_total'),
            'todayProfit'        => (int) Profit::whereDate('created_at', Carbon::today())->sum('total'),
            'todayTransactions'  => (int) Transaction::whereDate('created_at', Carbon::today())->count(),
            'totalRevenue'       => (int) Transaction::sum('grand_total'),
            'totalProfit'        => (int) Profit::sum('total'),
            'averageOrder'       => (int) round(Transaction::avg('grand_total') ?? 0),
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
            case 'custom':
                $start = Carbon::parse($customStart)->startOfDay();
                $end   = Carbon::parse($customEnd)->endOfDay();
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

    private function getRevenueTrend(string $period, Carbon $start, Carbon $end): array
    {
        if ($period === 'year') {
            // Group by month
            return Transaction::selectRaw('YEAR(created_at) as yr, MONTH(created_at) as mo, SUM(grand_total) as total')
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