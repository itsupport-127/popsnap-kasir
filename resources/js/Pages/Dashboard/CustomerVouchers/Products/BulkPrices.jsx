import React, { useMemo, useState } from "react";
import { Head, Link, router } from "@inertiajs/react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import { getProductImageUrl } from "@/Utils/imageUrl";
import {
    IconArrowLeft,
    IconDeviceFloppy,
    IconPackage,
    IconSearch,
    IconAlertTriangle,
    IconCheck,
} from "@tabler/icons-react";

const formatCurrency = (value = 0) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(value);

const BulkPrices = ({ products = [], categories = [] }) => {
    // buyPrices: { [product_id]: number } — cuma menyimpan nilai yang sedang diedit
    const [buyPrices, setBuyPrices] = useState(() =>
        Object.fromEntries(products.map((p) => [p.id, p.buy_price ?? 0]))
    );
    const [search, setSearch] = useState("");
    const [categoryId, setCategoryId] = useState("");
    const [isSaving, setIsSaving] = useState(false);

    const originalPrices = useMemo(
        () => Object.fromEntries(products.map((p) => [p.id, p.buy_price ?? 0])),
        [products]
    );

    const changedCount = useMemo(
        () =>
            Object.keys(buyPrices).filter(
                (id) => Number(buyPrices[id]) !== Number(originalPrices[id])
            ).length,
        [buyPrices, originalPrices]
    );

    const filteredProducts = useMemo(() => {
        return products.filter((p) => {
            const matchSearch = p.title
                .toLowerCase()
                .includes(search.toLowerCase());
            const matchCategory = categoryId
                ? String(p.category_id) === categoryId
                : true;
            return matchSearch && matchCategory;
        });
    }, [products, search, categoryId]);

    const handleChange = (id, value) => {
        const numeric = value.replace(/[^0-9]/g, "");
        setBuyPrices((prev) => ({ ...prev, [id]: numeric }));
    };

    const applyPercentage = (percent) => {
        setBuyPrices((prev) => {
            const next = { ...prev };
            filteredProducts.forEach((p) => {
                next[p.id] = Math.round((p.sell_price ?? 0) * (percent / 100));
            });
            return next;
        });
    };

    const handleSave = () => {
        const changedRows = Object.keys(buyPrices)
            .filter((id) => Number(buyPrices[id]) !== Number(originalPrices[id]))
            .map((id) => ({ id: Number(id), buy_price: Number(buyPrices[id]) || 0 }));

        if (changedRows.length === 0) return;

        setIsSaving(true);
        router.post(
            route("products.bulk-prices.update"),
            { prices: changedRows },
            {
                preserveScroll: true,
                onFinish: () => setIsSaving(false),
            }
        );
    };

    return (
        <>
            <Head title="Atur Harga Beli" />

            <div className="space-y-6 pb-24">
                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <Link
                            href={route("products.index")}
                            className="inline-flex items-center gap-1 text-sm text-slate-500 dark:text-slate-400 hover:text-primary-600 dark:hover:text-primary-400 mb-1 transition-colors"
                        >
                            <IconArrowLeft size={16} />
                            Kembali ke Produk
                        </Link>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">
                            Atur Harga Beli
                        </h1>
                        <p className="text-sm text-slate-500 dark:text-slate-400">
                            Isi harga beli semua produk sekaligus dalam satu
                            tabel, biar profit di laporan lebih akurat.
                        </p>
                    </div>
                </div>

                {/* Info banner */}
                <div className="flex items-start gap-3 p-4 rounded-2xl bg-amber-50 dark:bg-amber-950/30 border border-amber-200 dark:border-amber-800">
                    <IconAlertTriangle
                        size={20}
                        className="text-amber-600 dark:text-amber-400 shrink-0 mt-0.5"
                    />
                    <p className="text-sm text-amber-800 dark:text-amber-300">
                        <strong>Harga Beli</strong> dipakai untuk hitung profit
                        (Harga Jual − Harga Beli). Untuk paket sesi photobox,
                        isi dengan perkiraan biaya operasional per sesi
                        (kertas, tinta, listrik, dll) — bukan harga dari
                        supplier.
                    </p>
                </div>

                {/* Toolbar */}
                <div className="flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3">
                    <div className="flex flex-col sm:flex-row gap-2 flex-1">
                        <div className="relative w-full sm:w-72">
                            <IconSearch
                                size={16}
                                className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"
                            />
                            <input
                                type="text"
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                                placeholder="Cari produk..."
                                className="w-full h-10 pl-9 pr-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
                            />
                        </div>
                        <select
                            value={categoryId}
                            onChange={(e) => setCategoryId(e.target.value)}
                            className="h-10 px-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-sm text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
                        >
                            <option value="">Semua Kategori</option>
                            {categories.map((cat) => (
                                <option key={cat.id} value={String(cat.id)}>
                                    {cat.name}
                                </option>
                            ))}
                        </select>
                    </div>
                    <div className="flex items-center gap-2">
                        <span className="text-xs text-slate-500 dark:text-slate-400 hidden sm:inline">
                            Isi cepat (dari hasil filter):
                        </span>
                        {[20, 30, 40].map((pct) => (
                            <button
                                key={pct}
                                type="button"
                                onClick={() => applyPercentage(pct)}
                                className="px-3 py-2 text-xs font-medium rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                            >
                                {pct}% dari Jual
                            </button>
                        ))}
                    </div>
                </div>

                {/* Table */}
                <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                    <div className="overflow-x-auto">
                        <table className="w-full">
                            <thead>
                                <tr className="border-b border-slate-100 dark:border-slate-800">
                                    <th className="px-4 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">
                                        Produk
                                    </th>
                                    <th className="px-4 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">
                                        Kategori
                                    </th>
                                    <th className="px-4 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">
                                        Harga Jual
                                    </th>
                                    <th className="px-4 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">
                                        Harga Beli
                                    </th>
                                    <th className="px-4 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">
                                        Estimasi Profit
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                {filteredProducts.map((product) => {
                                    const current = Number(
                                        buyPrices[product.id] ?? 0
                                    );
                                    const isChanged =
                                        current !==
                                        Number(originalPrices[product.id]);
                                    const profit =
                                        (product.sell_price ?? 0) - current;

                                    return (
                                        <tr
                                            key={product.id}
                                            className={`transition-colors ${
                                                isChanged
                                                    ? "bg-primary-50/60 dark:bg-primary-950/20"
                                                    : "hover:bg-slate-50 dark:hover:bg-slate-800/50"
                                            }`}
                                        >
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-3">
                                                    <div className="w-10 h-10 rounded-lg overflow-hidden bg-slate-100 dark:bg-slate-800 shrink-0 flex items-center justify-center">
                                                        {product.image ? (
                                                            <img
                                                                src={getProductImageUrl(
                                                                    product.image
                                                                )}
                                                                alt={
                                                                    product.title
                                                                }
                                                                className="w-full h-full object-cover"
                                                            />
                                                        ) : (
                                                            <IconPackage
                                                                size={16}
                                                                className="text-slate-400"
                                                            />
                                                        )}
                                                    </div>
                                                    <span className="text-sm font-medium text-slate-800 dark:text-slate-200">
                                                        {product.title}
                                                    </span>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <span className="px-2 py-0.5 text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded">
                                                    {product.category?.name ??
                                                        "-"}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-right text-sm text-slate-600 dark:text-slate-400">
                                                {formatCurrency(
                                                    product.sell_price
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <div className="relative inline-block w-36">
                                                    <span className="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">
                                                        Rp
                                                    </span>
                                                    <input
                                                        type="text"
                                                        inputMode="numeric"
                                                        value={current}
                                                        onChange={(e) =>
                                                            handleChange(
                                                                product.id,
                                                                e.target.value
                                                            )
                                                        }
                                                        className="w-full h-9 pl-8 pr-3 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-sm text-right text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
                                                    />
                                                </div>
                                            </td>
                                            <td
                                                className={`px-4 py-3 text-right text-sm font-semibold ${
                                                    profit < 0
                                                        ? "text-danger-600 dark:text-danger-400"
                                                        : "text-success-600 dark:text-success-400"
                                                }`}
                                            >
                                                {formatCurrency(profit)}
                                            </td>
                                        </tr>
                                    );
                                })}
                                {filteredProducts.length === 0 && (
                                    <tr>
                                        <td
                                            colSpan={5}
                                            className="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400"
                                        >
                                            Tidak ada produk yang cocok.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {/* Sticky save bar */}
            <div className="fixed bottom-0 left-0 right-0 sm:left-64 z-40 border-t border-slate-200 dark:border-slate-800 bg-white/95 dark:bg-slate-900/95 backdrop-blur px-4 sm:px-6 py-3 flex items-center justify-between gap-4">
                <span className="text-sm text-slate-600 dark:text-slate-400">
                    {changedCount > 0
                        ? `${changedCount} produk belum disimpan`
                        : "Belum ada perubahan"}
                </span>
                <button
                    onClick={handleSave}
                    disabled={changedCount === 0 || isSaving}
                    className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {isSaving ? (
                        <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                    ) : (
                        <IconDeviceFloppy size={18} />
                    )}
                    Simpan Perubahan
                </button>
            </div>
        </>
    );
};

BulkPrices.layout = (page) => <DashboardLayout children={page} />;

export default BulkPrices;
