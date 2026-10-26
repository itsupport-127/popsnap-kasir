import React from "react";
import { Head, Link, router } from "@inertiajs/react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import Button from "@/Components/Dashboard/Button";
import Table from "@/Components/Dashboard/Table";
import Pagination from "@/Components/Dashboard/Pagination";
import {
    IconChartInfographic,
    IconCirclePlus,
    IconPencil,
    IconSearch,
    IconTrash,
    IconCopy,
    IconBolt,
    IconTicket,
} from "@tabler/icons-react";
import { useAuthorization } from "@/Utils/authorization";

const formatCurrency = (value = 0) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(value);

const discountLabel = (promo) => {
    if (promo.discount_kind === "bundle_price") {
        return `Bundle ${formatCurrency(promo.discount_value)}`;
    }

    if (promo.discount_kind === "buy_x_get_y") {
        return promo.activation_mode === "manual"
            ? "Beli X Gratis Y"
            : `${promo.buy_get_items_count || 0} item rule`;
    }

    if (promo.discount_type === "percentage") {
        return `${Number(promo.discount_value)}%`;
    }

    if (promo.discount_type === "fixed_price") {
        return `Harga ${formatCurrency(promo.discount_value)}`;
    }

    return `Potong ${formatCurrency(promo.discount_value)}`;
};

const targetLabel = (promo) => {
    if (promo.target_type === "product") return promo.product?.title || "Produk";
    if (promo.target_type === "category") {
        return promo.category?.name || "Kategori";
    }

    return "Semua Produk";
};

const customerScopeLabel = (promo) => {
    if (promo.customer) return `Personal · ${promo.customer.name}`;
    if (promo.customer_scope === "walk_in") return "Non-Member";
    if (promo.customer_scope === "registered") return "Pelanggan Terdaftar";
    if (promo.customer_scope === "member") return "Member";
    return "Semua Pelanggan";
};

const paymentMethodLabel = (promo) => {
    if (promo.payment_method_restriction === "cashless_only") return "Cashless saja";
    if (promo.payment_method_restriction === "cash_only") return "Tunai saja";
    return null;
};

const kindLabel = (kind) => {
    if (kind === "qty_break") return "Grosir";
    if (kind === "bundle_price") return "Bundle";
    if (kind === "buy_x_get_y") return "BXGY";
    return "Standar";
};

const STATUS_BADGE = {
    active: "bg-success-100 text-success-700 dark:bg-success-950/30 dark:text-success-400",
    scheduled: "bg-amber-100 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300",
    used: "bg-violet-100 text-violet-700 dark:bg-violet-950/30 dark:text-violet-300",
};

export default function Index({ promos, filters, summary = {}, recentAudits = [] }) {
    const { can } = useAuthorization();
    const hasData = promos.data.length > 0;

    const handleFilterChange = (key, value) => {
        router.get(
            route("promos.index"),
            { ...filters, [key]: value },
            { preserveState: true, replace: true }
        );
    };

    return (
        <>
            <Head title="Promo" />

            <div className="w-full">
                <div className="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">
                            Promo
                        </h1>
                        <p className="text-sm text-slate-500 dark:text-slate-400">
                            Semua diskon dan voucher dalam satu tempat — otomatis
                            maupun yang diaktifkan kasir lewat kode.
                        </p>
                    </div>
                    {can("promos-create") && (
                        <Button
                            type="link"
                            href={route("promos.create")}
                            icon={<IconCirclePlus size={18} />}
                            className="bg-primary-500 text-white shadow-lg shadow-primary-500/30 hover:bg-primary-600"
                            label="Buat Promo"
                        />
                    )}
                </div>

                <div className="mb-4 grid gap-3 grid-cols-2 md:grid-cols-4">
                    {[
                        { label: "Aktif", value: summary.active || 0 },
                        { label: "Terjadwal", value: summary.scheduled || 0 },
                        { label: "Otomatis", value: summary.auto || 0 },
                        { label: "Pakai Kode", value: summary.manual || 0 },
                    ].map((item) => (
                        <div
                            key={item.label}
                            className="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900"
                        >
                            <p className="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                {item.label}
                            </p>
                            <p className="mt-2 text-2xl font-bold text-slate-900 dark:text-white">
                                {item.value}
                            </p>
                        </div>
                    ))}
                </div>

                <div className="mb-4 rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                    <div className="grid grid-cols-1 gap-3 md:grid-cols-5">
                        <div className="relative md:col-span-2">
                            <input
                                type="text"
                                value={filters.search || ""}
                                onChange={(event) =>
                                    handleFilterChange("search", event.target.value)
                                }
                                placeholder="Cari nama atau kode promo..."
                                className="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 pr-11 text-sm text-slate-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                            />
                            <div className="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-4 text-slate-400">
                                <IconSearch size={18} />
                            </div>
                        </div>
                        <select
                            value={filters.status || ""}
                            onChange={(event) =>
                                handleFilterChange("status", event.target.value)
                            }
                            className="h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        >
                            <option value="">Semua Status</option>
                            <option value="active">Aktif</option>
                            <option value="scheduled">Terjadwal</option>
                            <option value="expired">Expired</option>
                            <option value="used">Sudah Dipakai</option>
                            <option value="inactive">Nonaktif</option>
                        </select>
                        <select
                            value={filters.activation_mode || ""}
                            onChange={(event) =>
                                handleFilterChange("activation_mode", event.target.value)
                            }
                            className="h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        >
                            <option value="">Otomatis &amp; Manual</option>
                            <option value="auto">Otomatis</option>
                            <option value="manual">Pakai Kode</option>
                        </select>
                        <select
                            value={filters.discount_kind || ""}
                            onChange={(event) =>
                                handleFilterChange("discount_kind", event.target.value)
                            }
                            className="h-11 rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                        >
                            <option value="">Semua Jenis</option>
                            <option value="standard_discount">Nominal / Persen</option>
                            <option value="qty_break">Grosir Bertingkat</option>
                            <option value="bundle_price">Bundle</option>
                            <option value="buy_x_get_y">Beli X Gratis Y</option>
                        </select>
                    </div>
                </div>

                <Table.Card title="Daftar Promo">
                    <Table>
                        <Table.Thead>
                            <tr>
                                <Table.Th>Promo</Table.Th>
                                <Table.Th>Cara Aktivasi</Table.Th>
                                <Table.Th>Target</Table.Th>
                                <Table.Th>Scope</Table.Th>
                                <Table.Th>Jenis</Table.Th>
                                <Table.Th>Benefit</Table.Th>
                                <Table.Th>Pemakaian</Table.Th>
                                <Table.Th>Status</Table.Th>
                                <Table.Th className="w-28 text-center">Aksi</Table.Th>
                            </tr>
                        </Table.Thead>
                        <Table.Tbody>
                            {hasData ? (
                                promos.data.map((promo) => (
                                    <tr
                                        key={promo.id}
                                        className="transition-colors hover:bg-slate-50 dark:hover:bg-slate-800/50"
                                    >
                                        <Table.Td>
                                            <div className="flex items-start gap-3">
                                                <div className="flex h-9 w-9 items-center justify-center rounded-xl bg-primary-100 text-primary-600 dark:bg-primary-950/40 dark:text-primary-300">
                                                    <IconChartInfographic size={18} />
                                                </div>
                                                <div>
                                                    <p className="font-semibold text-slate-800 dark:text-slate-200">
                                                        {promo.name}
                                                    </p>
                                                    <p className="text-xs text-slate-500 dark:text-slate-400">
                                                        {promo.code ||
                                                            (promo.starts_at
                                                                ? new Date(promo.starts_at).toLocaleString("id-ID")
                                                                : "Tanpa jadwal mulai")}
                                                    </p>
                                                </div>
                                            </div>
                                        </Table.Td>
                                        <Table.Td>
                                            <span
                                                className={`inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold ${
                                                    promo.activation_mode === "manual"
                                                        ? "bg-sky-100 text-sky-700 dark:bg-sky-950/30 dark:text-sky-300"
                                                        : "bg-orange-100 text-orange-700 dark:bg-orange-950/30 dark:text-orange-300"
                                                }`}
                                            >
                                                {promo.activation_mode === "manual" ? (
                                                    <IconTicket size={13} />
                                                ) : (
                                                    <IconBolt size={13} />
                                                )}
                                                {promo.activation_mode === "manual"
                                                    ? "Pakai Kode"
                                                    : "Otomatis"}
                                            </span>
                                        </Table.Td>
                                        <Table.Td>{targetLabel(promo)}</Table.Td>
                                        <Table.Td className="max-w-[160px] truncate">
                                            {customerScopeLabel(promo)}
                                            {paymentMethodLabel(promo) && (
                                                <span className="mt-1 flex w-fit items-center gap-1 rounded-full bg-indigo-100 px-2 py-0.5 text-[11px] font-medium text-indigo-700 dark:bg-indigo-950/30 dark:text-indigo-300">
                                                    {paymentMethodLabel(promo)}
                                                </span>
                                            )}
                                        </Table.Td>
                                        <Table.Td>
                                            <span className="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-800 dark:text-slate-200">
                                                {kindLabel(promo.discount_kind)}
                                            </span>
                                        </Table.Td>
                                        <Table.Td>{discountLabel(promo)}</Table.Td>
                                        <Table.Td>
                                            <span className="text-sm font-medium text-slate-700 dark:text-slate-300">
                                                {promo.used_count ?? 0}
                                                {promo.max_uses ? ` / ${promo.max_uses}` : "x"}
                                            </span>
                                        </Table.Td>
                                        <Table.Td>
                                            <span
                                                className={`inline-flex rounded-full px-2.5 py-1 text-xs font-semibold ${
                                                    STATUS_BADGE[promo.status_label] ||
                                                    "bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                                                }`}
                                            >
                                                {promo.status_label}
                                            </span>
                                        </Table.Td>
                                        <Table.Td className="text-center">
                                            <div className="flex items-center justify-center gap-2">
                                                <Link
                                                    href={route("promos.usage", promo.id)}
                                                    className="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-blue-200 bg-blue-50 text-blue-600 hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-950/30 dark:text-blue-300"
                                                    title="Lihat pemakaian"
                                                >
                                                    <IconChartInfographic size={16} />
                                                </Link>
                                                {can("promos-create") && (
                                                    <button
                                                        type="button"
                                                        title="Duplikat promo"
                                                        onClick={() =>
                                                            router.post(
                                                                route(
                                                                    "promos.duplicate",
                                                                    promo.id
                                                                )
                                                            )
                                                        }
                                                        className="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-800/60 dark:text-slate-300"
                                                    >
                                                        <IconCopy size={16} />
                                                    </button>
                                                )}
                                                {can("promos-update") && (
                                                    <Link
                                                        href={route("promos.edit", promo.id)}
                                                        className="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-amber-200 bg-amber-50 text-amber-600 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-300"
                                                    >
                                                        <IconPencil size={16} />
                                                    </Link>
                                                )}
                                                {can("promos-delete") && (
                                                    <Button
                                                        type="delete"
                                                        url={route("promos.destroy", promo.id)}
                                                        icon={<IconTrash size={16} />}
                                                        className="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-600 hover:bg-rose-100 dark:border-rose-800 dark:bg-rose-950/30 dark:text-rose-300"
                                                    />
                                                )}
                                            </div>
                                        </Table.Td>
                                    </tr>
                                ))
                            ) : (
                                <Table.Empty
                                    colSpan={9}
                                    message="Belum ada promo."
                                >
                                    <div className="mx-auto mb-3 flex h-16 w-16 items-center justify-center rounded-full bg-slate-100 dark:bg-slate-800">
                                        <IconChartInfographic
                                            size={28}
                                            className="text-slate-400"
                                        />
                                    </div>
                                </Table.Empty>
                            )}
                        </Table.Tbody>
                    </Table>
                </Table.Card>

                {promos.last_page > 1 && <Pagination links={promos.links} />}

                {recentAudits.length > 0 && (
                    <div className="mt-6 rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                        <h2 className="text-base font-semibold text-slate-900 dark:text-white">
                            Aktivitas Promo Terbaru
                        </h2>
                        <div className="mt-4 space-y-3">
                            {recentAudits.map((audit) => (
                                <div
                                    key={audit.id}
                                    className="flex items-center justify-between rounded-xl border border-slate-200 px-4 py-3 text-sm dark:border-slate-800"
                                >
                                    <div>
                                        <p className="font-medium text-slate-800 dark:text-slate-200">
                                            {audit.description}
                                        </p>
                                        <p className="text-xs text-slate-500 dark:text-slate-400">
                                            {audit.event}
                                        </p>
                                    </div>
                                    <span className="text-xs text-slate-500 dark:text-slate-400">
                                        {new Date(audit.created_at).toLocaleString("id-ID")}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}

Index.layout = (page) => <DashboardLayout children={page} />;
