import DashboardLayout from "@/Layouts/DashboardLayout";
import { useAuthorization } from "@/Utils/authorization";
import { Head, Link, router } from "@inertiajs/react";
import { Fragment, useEffect, useMemo, useRef, useState } from "react";
import Chart from "chart.js/auto";
import {
    IconBox,
    IconCategory,
    IconMoneybag,
    IconUsers,
    IconCoin,
    IconReceipt,
    IconTrendingUp,
    IconArrowUpRight,
    IconArrowDownRight,
    IconShoppingCart,
    IconChartBar,
    IconClock,
    IconAlertTriangle,
    IconPackageOff,
    IconTarget,
    IconWallet,
    IconCalendar,
    IconCamera,
} from "@tabler/icons-react";

const formatCurrency = (value = 0) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(value);

// Stat Card Component
function StatCard({ title, value, subtitle, icon: Icon, gradient, growth }) {
    const hasGrowth = growth !== null && growth !== undefined;
    const trend = hasGrowth ? (growth >= 0 ? "up" : "down") : null;

    return (
        <div
            className={`
            relative overflow-hidden rounded-2xl p-5
            bg-gradient-to-br ${gradient}
            text-white shadow-lg
        `}
        >
            {/* Background Pattern */}
            <div className="absolute top-0 right-0 w-32 h-32 opacity-20">
                <Icon
                    size={128}
                    strokeWidth={0.5}
                    className="transform translate-x-8 -translate-y-8"
                />
            </div>

            <div className="relative z-10">
                <div className="flex items-center justify-between gap-2 mb-3">
                    <div className="flex items-center gap-2">
                        <div className="p-2 rounded-xl bg-white/20">
                            <Icon size={20} strokeWidth={1.5} />
                        </div>
                        <span className="text-sm font-medium opacity-90">
                            {title}
                        </span>
                    </div>
                    {hasGrowth && (
                        <span
                            className={`inline-flex items-center gap-0.5 px-2 py-0.5 rounded-full text-xs font-semibold ${
                                trend === "up"
                                    ? "bg-white/25"
                                    : "bg-black/15"
                            }`}
                            title="Dibanding periode sebelumnya"
                        >
                            {trend === "up" ? (
                                <IconArrowUpRight size={12} />
                            ) : (
                                <IconArrowDownRight size={12} />
                            )}
                            {Math.abs(growth)}%
                        </span>
                    )}
                </div>

                <p className="text-3xl font-bold">{value}</p>

                {subtitle && (
                    <p className="mt-2 text-sm opacity-80">{subtitle}</p>
                )}
            </div>
        </div>
    );
}

// Target Progress Card Component
function TargetCard({ title, current, target, icon: Icon }) {
    const percentage = target > 0 ? Math.min((current / target) * 100, 100) : 0;
    const isAchieved = percentage >= 100;

    return (
        <div className="relative overflow-hidden rounded-2xl p-5 bg-gradient-to-br from-indigo-500 to-indigo-700 text-white shadow-lg">
            {/* Background Pattern */}
            <div className="absolute top-0 right-0 w-32 h-32 opacity-20">
                <Icon
                    size={128}
                    strokeWidth={0.5}
                    className="transform translate-x-8 -translate-y-8"
                />
            </div>

            <div className="relative z-10">
                <div className="flex items-center gap-2 mb-3">
                    <div className="p-2 rounded-xl bg-white/20">
                        <Icon size={20} strokeWidth={1.5} />
                    </div>
                    <span className="text-sm font-medium opacity-90">
                        {title}
                    </span>
                </div>

                <p className="text-2xl font-bold">{percentage.toFixed(0)}%</p>

                {/* Progress Bar */}
                <div className="mt-3 w-full h-2 bg-white/30 rounded-full overflow-hidden">
                    <div
                        className={`h-full rounded-full transition-all duration-500 ${
                            isAchieved ? "bg-green-400" : "bg-white"
                        }`}
                        style={{ width: `${percentage}%` }}
                    />
                </div>

                <p className="mt-2 text-xs opacity-80">
                    {formatCurrency(current)} / {formatCurrency(target)}
                </p>
            </div>
        </div>
    );
}

// Info Card Component
function InfoCard({ title, value, subtitle, icon: Icon }) {
    return (
        <div className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 transition-all hover:shadow-md hover:border-slate-300 dark:hover:border-slate-700">
            <div className="flex items-center justify-between">
                <div>
                    <p className="text-sm text-slate-500 dark:text-slate-400">
                        {title}
                    </p>
                    <p className="mt-2 text-2xl font-bold text-slate-900 dark:text-white">
                        {value}
                    </p>
                    {subtitle && (
                        <p className="mt-1 text-xs text-slate-400 dark:text-slate-500 flex items-center gap-1">
                            <Icon size={14} />
                            {subtitle}
                        </p>
                    )}
                </div>
                <div className="p-3 rounded-xl bg-slate-100 dark:bg-slate-800">
                    <Icon
                        size={24}
                        className="text-slate-600 dark:text-slate-400"
                        strokeWidth={1.5}
                    />
                </div>
            </div>
        </div>
    );
}

// List Card Component
function ListCard({ title, subtitle, icon: Icon, children, emptyMessage }) {
    return (
        <div className="rounded-2xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 overflow-hidden">
            <div className="p-5 border-b border-slate-100 dark:border-slate-800">
                <div className="flex items-center gap-2">
                    <div className="p-2 rounded-lg bg-primary-100 dark:bg-primary-900/30">
                        <Icon
                            size={18}
                            className="text-primary-600 dark:text-primary-400"
                        />
                    </div>
                    <div>
                        <h3 className="text-sm font-semibold text-slate-800 dark:text-slate-100">
                            {title}
                        </h3>
                        {subtitle && (
                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                {subtitle}
                            </p>
                        )}
                    </div>
                </div>
            </div>
            <div className="p-5">
                {children || (
                    <div className="flex h-32 items-center justify-center text-sm text-slate-400 dark:text-slate-500">
                        {emptyMessage}
                    </div>
                )}
            </div>
        </div>
    );
}

export default function Dashboard({
    period = "today",
    startDate,
    endDate,
    totalCategories,
    totalProducts,
    totalTransactions,
    totalCustomers,
    revenueTrend,
    totalRevenue,
    totalProfit,
    averageOrder,
    todayTransactions,
    todaySales = 0,
    todayProfit = 0,
    salesGrowth = null,
    profitGrowth = null,
    transactionGrowth = null,
    monthlyTarget = 0,
    currentMonthSales = 0,
    topProducts = [],
    topProductsByCategory = [],
    slowMovingProducts = [],
    recentTransactions = [],
    topCustomers = [],
    lowStockProducts = [],
    activeShifts = [],
    photobox = null,
    sources = [],
    storeSales = 0,
    storeTransactions = 0,
}) {
    const chartRef = useRef(null);
    const chartInstance = useRef(null);
    const [productView, setProductView] = useState("all");

    const { can } = useAuthorization();
    // "Laporan Profit" (profits-access) dipakai sebagai penanda role admin/pemilik.
    // Kasir biasa (tanpa permission ini) tidak lihat filter periode & 3 card
    // penjualan/profit/transaksi yang bisa diganti-ganti periodenya.
    const canViewProfits = can("profits-access");

    const chartData = useMemo(() => revenueTrend ?? [], [revenueTrend]);

    const PERIODS = [
        { value: "all",   label: "Semua" },
        { value: "today", label: "Hari Ini" },
        { value: "week",  label: "Minggu Ini" },
        { value: "month", label: "Bulan Ini" },
        { value: "year",  label: "Tahun Ini" },
        { value: "custom",label: "Kustom" },
    ];

    const [activePeriod, setActivePeriod] = useState(period);
    const [customStart, setCustomStart]   = useState(startDate ?? "");
    const [customEnd, setCustomEnd]       = useState(endDate ?? "");
    const [showCustom, setShowCustom]     = useState(period === "custom");

    const applyFilter = (p, cs, ce) => {
        const params = { period: p };
        if (p === "custom" && cs && ce) { params.start_date = cs; params.end_date = ce; }
        router.get(route("dashboard"), params, { preserveScroll: true, preserveState: false });
    };

    const handlePeriod = (p) => {
        setActivePeriod(p);
        setShowCustom(p === "custom");
        if (p !== "custom") applyFilter(p);
    };

    const periodLabel = PERIODS.find(p => p.value === period)?.label ?? "Hari Ini";

    // Setup chart
    useEffect(() => {
        if (!chartRef.current) return;

        if (chartInstance.current) {
            chartInstance.current.destroy();
            chartInstance.current = null;
        }

        if (!chartData.length) return;

        const labels = chartData.map((item) => item.label);
        const totals = chartData.map((item) => item.total);

        const ctx = chartRef.current.getContext("2d");
        const gradient = ctx.createLinearGradient(0, 0, 0, 200);
        gradient.addColorStop(0, "rgba(99, 102, 241, 0.3)");
        gradient.addColorStop(1, "rgba(99, 102, 241, 0.01)");

        chartInstance.current = new Chart(chartRef.current, {
            type: "line",
            data: {
                labels,
                datasets: [
                    {
                        label: "Pendapatan",
                        data: totals,
                        borderColor: "#6366f1",
                        backgroundColor: gradient,
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointRadius: 0,
                        pointHoverRadius: 6,
                        pointHoverBackgroundColor: "#6366f1",
                        pointHoverBorderColor: "#fff",
                        pointHoverBorderWidth: 2,
                    },
                ],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: "index",
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: "#1e293b",
                        titleColor: "#f1f5f9",
                        bodyColor: "#f1f5f9",
                        padding: 12,
                        borderRadius: 8,
                        displayColors: false,
                        callbacks: {
                            label: (ctx) => formatCurrency(ctx.raw),
                        },
                    },
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => formatCurrency(value),
                            color: "#94a3b8",
                            font: { size: 11 },
                        },
                        grid: {
                            color: "rgba(148, 163, 184, 0.1)",
                            drawBorder: false,
                        },
                        border: { display: false },
                    },
                    x: {
                        ticks: {
                            color: "#94a3b8",
                            font: { size: 11 },
                        },
                        grid: { display: false },
                        border: { display: false },
                    },
                },
            },
        });

        return () => chartInstance.current?.destroy();
    }, [chartData]);

    return (
        <>
            <Head title="Dashboard" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col gap-4">
                    <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-slate-900 dark:text-white">
                                Dashboard
                            </h1>
                            <p className="text-sm text-slate-500 dark:text-slate-400">
                                Data: <span className="font-semibold text-primary-600 dark:text-primary-400">{canViewProfits ? periodLabel : "Hari Ini"}</span>
                                {canViewProfits && period === "custom" && startDate && endDate && (
                                    <span className="ml-1 text-slate-400">({startDate} s/d {endDate})</span>
                                )}
                            </p>
                        </div>
                        <Link
                            href={route("transactions.index")}
                            className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium transition-colors shadow-lg shadow-primary-500/30"
                        >
                            <IconShoppingCart size={18} />
                            <span>Transaksi Baru</span>
                        </Link>
                    </div>

                    {/* Filter Periode — cuma buat yang punya akses Laporan Profit */}
                    {canViewProfits && (
                        <div className="flex flex-wrap gap-2">
                            {PERIODS.map(p => (
                                <button
                                    key={p.value}
                                    onClick={() => handlePeriod(p.value)}
                                    className={`px-4 py-2 rounded-xl text-sm font-medium transition-all ${
                                        activePeriod === p.value
                                            ? "bg-primary-500 text-white shadow-md shadow-primary-500/30"
                                            : "bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-primary-300 dark:hover:border-primary-700"
                                    }`}
                                >
                                    {p.label}
                                </button>
                            ))}
                        </div>
                    )}

                    {/* Custom Date Range */}
                    {canViewProfits && showCustom && (
                        <div className="flex flex-wrap items-center gap-3 p-4 rounded-2xl bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700">
                            <IconCalendar size={16} className="text-slate-400" />
                            <div className="flex items-center gap-2">
                                <label className="text-xs text-slate-500">Dari</label>
                                <input
                                    type="date"
                                    value={customStart}
                                    onChange={e => setCustomStart(e.target.value)}
                                    className="h-9 px-3 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 text-sm text-slate-700 dark:text-slate-200"
                                />
                            </div>
                            <div className="flex items-center gap-2">
                                <label className="text-xs text-slate-500">Sampai</label>
                                <input
                                    type="date"
                                    value={customEnd}
                                    onChange={e => setCustomEnd(e.target.value)}
                                    className="h-9 px-3 rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-900 text-sm text-slate-700 dark:text-slate-200"
                                />
                            </div>
                            <button
                                onClick={() => applyFilter("custom", customStart, customEnd)}
                                disabled={!customStart || !customEnd || customStart > customEnd}
                                className="px-4 py-2 rounded-lg bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium disabled:opacity-50 transition-colors"
                            >
                                Terapkan
                            </button>
                            {customStart && customEnd && customStart > customEnd && (
                                <p className="w-full text-xs text-danger-500">
                                    Tanggal "Sampai" tidak boleh lebih awal dari "Dari".
                                </p>
                            )}
                        </div>
                    )}
                </div>

                {/* Main Stat Cards - Reorganized */}
                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    {canViewProfits && (
                        <>
                            <StatCard
                                title={`Penjualan ${periodLabel}`}
                                value={formatCurrency(todaySales)}
                                subtitle={photobox
                                    ? `Kasir ${formatCurrency(storeSales)} · Booth ${formatCurrency(photobox.revenue)}`
                                    : `Total penjualan ${periodLabel.toLowerCase()}`}
                                icon={IconCoin}
                                gradient="from-primary-500 to-primary-700"
                                growth={salesGrowth}
                            />
                            <StatCard
                                title={`Profit ${periodLabel}`}
                                value={formatCurrency(todayProfit)}
                                subtitle={photobox
                                    ? "Profit toko (photobox belum punya data modal)"
                                    : `Profit bersih ${periodLabel.toLowerCase()}`}
                                icon={IconTrendingUp}
                                gradient="from-success-500 to-success-700"
                                growth={profitGrowth}
                            />
                        </>
                    )}
                    <TargetCard
                        title="Target Bulan Ini"
                        current={currentMonthSales}
                        target={monthlyTarget}
                        icon={IconTarget}
                    />
                    {canViewProfits && (
                        <StatCard
                            title={`Transaksi ${periodLabel}`}
                            value={todayTransactions}
                            subtitle={photobox
                                ? `Kasir ${storeTransactions} · Booth ${photobox.transactions} (QRIS booth)`
                                : "Transaksi"}
                            icon={IconClock}
                            gradient="from-warning-500 to-warning-600"
                            growth={transactionGrowth}
                        />
                    )}
                </div>

                {/* Per sumber: kasir langsung + booth (dirinci per studio). Jumlahnya = angka penjualan di atas. */}
                {photobox && sources.length > 0 && (() => {
                    const totalRevenue = sources.reduce((a, x) => a + x.revenue, 0);
                    const totalTx = sources.reduce((a, x) => a + x.transactions, 0);
                    const share = (v) => (totalRevenue > 0 ? `${Math.round((v / totalRevenue) * 100)}%` : "0%");
                    const hrefOf = (src) =>
                        src.type === "store"
                            ? route("transactions.history")
                            : route("photobox-transactions.index", src.studio_id ? { period, studio_id: src.studio_id } : { period });
                    const Row = ({ src, child = false }) => (
                        <li className={`flex flex-wrap items-center justify-between gap-2 py-2.5 text-sm ${child ? "pl-8" : ""}`}>
                            <span className={`flex items-center gap-2 ${child ? "text-slate-600 dark:text-slate-300" : "font-semibold text-slate-800 dark:text-slate-100"}`}>
                                {src.type === "store" ? <IconShoppingCart size={16} /> : <IconCamera size={child ? 14 : 16} />}
                                {src.name}
                            </span>
                            <span className="flex items-center gap-4 text-slate-500 dark:text-slate-400">
                                <span>
                                    {src.transactions} transaksi
                                    {src.sessions !== null && src.sessions !== src.transactions ? ` · ${src.sessions} sesi` : ""}
                                </span>
                                <span className="min-w-[40px] text-right text-xs">{share(src.revenue)}</span>
                                <b className="min-w-[110px] text-right text-slate-800 dark:text-slate-100">{formatCurrency(src.revenue)}</b>
                                <Link href={hrefOf(src)} className="text-xs font-semibold text-primary-600 hover:underline dark:text-primary-400">
                                    Detail
                                </Link>
                            </span>
                        </li>
                    );
                    return (
                        <ListCard
                            title="Pendapatan per Sumber"
                            subtitle={`${periodLabel} · kasir langsung + booth = angka penjualan di atas`}
                            icon={IconCamera}
                        >
                            <ul className="divide-y divide-slate-100 dark:divide-slate-800">
                                {sources.map((src) => (
                                    <Fragment key={src.key}>
                                        <Row src={src} />
                                        {(src.children || []).map((c) => <Row key={c.key} src={c} child />)}
                                    </Fragment>
                                ))}
                                <li className="flex items-center justify-between gap-2 py-2.5 text-sm font-bold text-slate-900 dark:text-white">
                                    <span>Total</span>
                                    <span className="flex items-center gap-4">
                                        <span className="font-medium text-slate-500 dark:text-slate-400">{totalTx} transaksi</span>
                                        <span className="min-w-[40px]" />
                                        <span className="min-w-[110px] text-right">{formatCurrency(totalRevenue)}</span>
                                        <span className="w-[44px]" />
                                    </span>
                                </li>
                            </ul>
                        </ListCard>
                    );
                })()}

                {/* Secondary Stats */}
                <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <InfoCard
                        title="Total Kategori"
                        value={totalCategories}
                        icon={IconCategory}
                    />
                    <InfoCard
                        title="Total Produk"
                        value={totalProducts}
                        icon={IconBox}
                    />
                    <InfoCard
                        title="Total Transaksi"
                        value={totalTransactions}
                        icon={IconMoneybag}
                    />
                    <InfoCard
                        title="Total Pelanggan"
                        value={totalCustomers}
                        icon={IconUsers}
                    />
                </div>

                {/* Revenue Chart - Full Width */}
                <ListCard
                    title="Tren Pendapatan"
                    subtitle={periodLabel}
                    icon={IconChartBar}
                    emptyMessage="Belum ada data pendapatan"
                >
                    {chartData.length > 0 && (
                        <div className="h-72">
                            <canvas ref={chartRef} />
                        </div>
                    )}
                </ListCard>

                {/* Top Products - Full Width with category breakdown */}
                <ListCard
                    title="Produk Terlaris"
                    subtitle="Best seller"
                    icon={IconBox}
                    emptyMessage="Belum ada data"
                >
                    {topProducts.length > 0 && (
                        <div>
                            {/* Tabs */}
                            <div className="flex items-center gap-1 mb-4 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl w-fit">
                                <button
                                    onClick={() => setProductView("all")}
                                    className={`px-3 py-1.5 text-xs font-medium rounded-lg transition-colors ${
                                        productView === "all"
                                            ? "bg-white dark:bg-slate-900 text-primary-600 dark:text-primary-400 shadow-sm"
                                            : "text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300"
                                    }`}
                                >
                                    Semua Produk
                                </button>
                                <button
                                    onClick={() => setProductView("category")}
                                    className={`px-3 py-1.5 text-xs font-medium rounded-lg transition-colors ${
                                        productView === "category"
                                            ? "bg-white dark:bg-slate-900 text-primary-600 dark:text-primary-400 shadow-sm"
                                            : "text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-300"
                                    }`}
                                >
                                    Per Kategori
                                </button>
                            </div>

                            {/* Semua Produk */}
                            {productView === "all" && (
                                <div className="max-h-96 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800">
                                    {topProducts.map((product, index) => (
                                        <div
                                            key={index}
                                            className="flex items-center justify-between gap-3 py-3 first:pt-0 last:pb-0"
                                        >
                                            <div className="flex items-start gap-3">
                                                <span className="w-7 h-7 shrink-0 rounded-full bg-primary-100 dark:bg-primary-900/50 text-primary-600 dark:text-primary-400 text-sm font-semibold flex items-center justify-center">
                                                    {index + 1}
                                                </span>
                                                <div className="space-y-1">
                                                    <p className="text-sm font-semibold text-slate-800 dark:text-slate-200 line-clamp-1">
                                                        {product.name}
                                                    </p>
                                                    <p className="text-xs text-slate-500 dark:text-slate-400">
                                                        {product.category} • SKU:{" "}
                                                        {product.sku || "-"}
                                                    </p>
                                                </div>
                                            </div>
                                            <div className="text-right shrink-0">
                                                <p className="text-base font-semibold text-primary-600 dark:text-primary-400 leading-tight">
                                                    {product.qty}x
                                                </p>
                                                <p className="text-[11px] uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                                    Terjual
                                                </p>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}

                            {/* Per Kategori */}
                            {productView === "category" && (
                                <div className="max-h-96 overflow-y-auto space-y-4">
                                    {topProductsByCategory.map((cat, catIndex) => (
                                        <div
                                            key={catIndex}
                                            className="rounded-xl border border-slate-100 dark:border-slate-800 overflow-hidden"
                                        >
                                            <div className="flex items-center justify-between px-4 py-2.5 bg-slate-50 dark:bg-slate-800/60">
                                                <p className="text-sm font-semibold text-slate-800 dark:text-slate-200">
                                                    {catIndex + 1}. {cat.category}
                                                </p>
                                                <span className="text-xs font-semibold text-primary-600 dark:text-primary-400">
                                                    {cat.total_qty}x terjual
                                                </span>
                                            </div>
                                            <div className="divide-y divide-slate-100 dark:divide-slate-800 px-4">
                                                {cat.products.map((product, index) => (
                                                    <div
                                                        key={index}
                                                        className="flex items-center justify-between gap-3 py-2.5"
                                                    >
                                                        <p className="text-sm text-slate-700 dark:text-slate-300 line-clamp-1">
                                                            {product.name}
                                                        </p>
                                                        <span className="text-xs font-semibold text-slate-500 dark:text-slate-400 shrink-0">
                                                            {product.qty}x
                                                        </span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>
                    )}
                </ListCard>

                {/* 3-Column Bottom Widgets */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <ListCard
                        title="Shift Aktif"
                        subtitle="Pemantauan kasir"
                        icon={IconWallet}
                        emptyMessage="Tidak ada shift aktif"
                    >
                        {activeShifts.length > 0 && (
                            <div className="divide-y divide-slate-100 dark:divide-slate-800">
                                {activeShifts.map((shift) => (
                                    <div
                                        key={shift.id}
                                        className="py-3 first:pt-0 last:pb-0"
                                    >
                                        <div className="flex items-center justify-between gap-3">
                                            <div>
                                                <p className="text-sm font-semibold text-slate-800 dark:text-slate-200">
                                                    {shift.user?.name || "-"}
                                                </p>
                                                <p className="text-xs text-slate-500 dark:text-slate-400">
                                                    {shift.transactions_count} transaksi
                                                </p>
                                            </div>
                                            <span className="text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                                {formatCurrency(shift.expected_cash)}
                                            </span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </ListCard>

                    {/* Slow Moving Products */}
                    <ListCard
                        title="Slow Moving"
                        subtitle="Tidak terjual 30 hari"
                        icon={IconPackageOff}
                        emptyMessage="Semua produk laku"
                    >
                        {slowMovingProducts.length > 0 && (
                            <ul className="divide-y divide-slate-100 dark:divide-slate-800">
                                {slowMovingProducts.map((product, index) => (
                                    <li
                                        key={index}
                                        className="flex items-center justify-between py-3 first:pt-0 last:pb-0"
                                    >
                                        <div className="flex items-center gap-3">
                                            <span className="w-7 h-7 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-sm font-semibold flex items-center justify-center">
                                                {index + 1}
                                            </span>
                                            <span className="text-sm text-slate-700 dark:text-slate-300 truncate max-w-[120px]">
                                                {product.name}
                                            </span>
                                        </div>
                                        <span className="text-xs text-warning-500 font-semibold">
                                            {product.stock} pcs
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </ListCard>

                    {/* Top Customers */}
                    <ListCard
                        title="Pelanggan Terbaik"
                        subtitle="Top spender"
                        icon={IconUsers}
                        emptyMessage="Belum ada data"
                    >
                        {topCustomers.length > 0 && (
                            <ul className="divide-y divide-slate-100 dark:divide-slate-800">
                                {topCustomers
                                    .slice(0, 5)
                                    .map((customer, index) => (
                                        <li
                                            key={index}
                                            className="flex items-center justify-between py-3 first:pt-0 last:pb-0"
                                        >
                                            <div className="flex items-center gap-3">
                                                <span className="w-7 h-7 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 text-sm font-semibold flex items-center justify-center">
                                                    {index + 1}
                                                </span>
                                                <span className="text-sm text-slate-700 dark:text-slate-300">
                                                    {customer.name}
                                                </span>
                                            </div>
                                            <span className="text-xs text-slate-500 font-semibold">
                                                {customer.orders}x
                                            </span>
                                        </li>
                                    ))}
                            </ul>
                        )}
                    </ListCard>

                </div>

                {/* Recent Transactions */}
                <ListCard
                    title="Transaksi Terbaru"
                    subtitle="5 transaksi terakhir"
                    icon={IconReceipt}
                    emptyMessage="Belum ada transaksi"
                >
                    {recentTransactions.length > 0 && (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            {recentTransactions.map((trx, index) => (
                                <div
                                    key={index}
                                    className="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50"
                                >
                                    <div>
                                        <p className="text-sm font-semibold text-slate-800 dark:text-slate-200">
                                            {trx.invoice}
                                        </p>
                                        <p className="text-xs text-slate-500 mt-0.5">
                                            {trx.date} • {trx.customer}
                                        </p>
                                    </div>
                                    <p className="text-sm font-bold text-primary-600 dark:text-primary-400">
                                        {formatCurrency(trx.total)}
                                    </p>
                                </div>
                            ))}
                        </div>
                    )}
                </ListCard>

                {/* Low Stock Highlight */}
                <ListCard
                    title="Stok Menipis"
                    subtitle="Stok < 10"
                    icon={IconAlertTriangle}
                    emptyMessage="Semua stok aman"
                >
                    {lowStockProducts.length > 0 && (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                            {lowStockProducts.map((product, index) => (
                                <div
                                    key={index}
                                    className="flex items-center justify-between p-3 rounded-xl bg-rose-50 dark:bg-rose-900/20 border border-rose-100 dark:border-rose-800"
                                >
                                    <div className="flex items-center gap-3">
                                        <span className="w-7 h-7 rounded-full bg-rose-100 dark:bg-rose-900/40 text-rose-700 dark:text-rose-300 text-sm font-semibold flex items-center justify-center">
                                            {index + 1}
                                        </span>
                                        <span className="text-sm font-semibold text-rose-800 dark:text-rose-200 truncate max-w-[140px]">
                                            {product.name}
                                        </span>
                                    </div>
                                    <span className="text-xs font-semibold text-rose-700 dark:text-rose-200">
                                        {product.stock} pcs
                                    </span>
                                </div>
                            ))}
                        </div>
                    )}
                </ListCard>
            </div>
        </>
    );
}

Dashboard.layout = (page) => <DashboardLayout children={page} />;