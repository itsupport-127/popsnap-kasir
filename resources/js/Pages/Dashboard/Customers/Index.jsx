import React, { useState } from "react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import { Head, usePage, Link, router } from "@inertiajs/react";
import Button from "@/Components/Dashboard/Button";
import {
    IconCirclePlus,
    IconDatabaseOff,
    IconPencilCog,
    IconTrash,
    IconLayoutGrid,
    IconList,
    IconUser,
    IconPhone,
    IconMapPin,
    IconBrandWhatsapp,
    IconFilter,
    IconChevronDown,
    IconX,
} from "@tabler/icons-react";
import Search from "@/Components/Dashboard/Search";
import Table from "@/Components/Dashboard/Table";
import Pagination from "@/Components/Dashboard/Pagination";
import { useAuthorization } from "@/Utils/authorization";

function waUrl(noTelp) {
    if (!noTelp) return null;
    let number = String(noTelp).replace(/\D/g, "");
    if (!number) return null;
    if (number.startsWith("0")) number = "62" + number.slice(1);
    if (!number.startsWith("62")) number = "62" + number;
    return "https://wa.me/" + number;
}

// Buat URL WA dengan greeting otomatis + nama pelanggan
function waGreetingUrl(noTelp, name) {
    const base = waUrl(noTelp);
    if (!base) return null;
    const firstName = name ? name.split(" ")[0] : "kak";
    const message = `Hii ${firstName}! Thanks for stopping by Popsnap! Here's your photobox soft file Hope you had tons of fun, come again soon`;
    return base + "?text=" + encodeURIComponent(message);
}

// Customer Card for Grid View
function CustomerCard({ customer, canUpdate, canDelete }) {
    const wa = waGreetingUrl(customer.no_telp, customer.name);
    return (
        <div className="group bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 hover:shadow-lg hover:border-slate-300 dark:hover:border-slate-700 transition-all duration-200">
            {/* Avatar & Name */}
            <div className="flex items-start justify-between mb-4">
                <div className="flex items-center gap-3">
                    {customer.avatar ? (
                        <img
                            src={customer.avatar}
                            alt={customer.name}
                            className="w-12 h-12 rounded-full object-cover border border-slate-200 dark:border-slate-700 flex-shrink-0"
                        />
                    ) : (
                        <div className="w-12 h-12 rounded-full bg-gradient-to-br from-accent-400 to-accent-600 flex items-center justify-center text-white text-lg font-semibold flex-shrink-0">
                            {customer.name.charAt(0).toUpperCase()}
                        </div>
                    )}
                    <div>
                        <h3 className="text-base font-semibold text-slate-800 dark:text-slate-200">
                            <Link
                                href={route("customers.show", customer.id)}
                                className="hover:text-primary-600"
                            >
                                {customer.name}
                            </Link>
                        </h3>
                        <div className="mt-1 flex flex-wrap gap-1">
                            <span className="inline-flex rounded-full bg-primary-100 px-2 py-0.5 text-[11px] font-semibold text-primary-700 dark:bg-primary-950/40 dark:text-primary-300">
                                {customer.is_loyalty_member
                                    ? customer.loyalty_tier
                                    : "non-member"}
                            </span>
                            <span className="inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] font-semibold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                                {customer.loyalty_points || 0} poin
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            {/* Contact Info */}
            <div className="space-y-2 mb-4">
                {customer.no_telp && (
                    <div className="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                        <IconPhone size={16} />
                        <span>{customer.no_telp}</span>
                    </div>
                )}
                {customer.address && (
                    <div className="flex items-start gap-2 text-sm text-slate-500 dark:text-slate-400">
                        <IconMapPin
                            size={16}
                            className="flex-shrink-0 mt-0.5"
                        />
                        <span className="line-clamp-2">{customer.address}</span>
                    </div>
                )}
            </div>

            {/* Actions */}
            {(canUpdate || canDelete || wa) && (
                <div className="flex gap-2 pt-3 border-t border-slate-100 dark:border-slate-800">
                    {wa && (
                        <a
                            href={wa}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-lg bg-green-100 text-green-600 hover:bg-green-200 dark:bg-green-900/50 dark:text-green-400 text-sm font-medium transition-colors"
                            title="Chat WhatsApp"
                        >
                            <IconBrandWhatsapp size={16} />
                            <span>WA</span>
                        </a>
                    )}
                    {canUpdate && (
                        <Link
                            href={route("customers.edit", customer.id)}
                            className="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-lg bg-warning-100 text-warning-600 hover:bg-warning-200 dark:bg-warning-900/50 dark:text-warning-400 text-sm font-medium transition-colors"
                        >
                            <IconPencilCog size={16} />
                            <span>Edit</span>
                        </Link>
                    )}
                    {canDelete && (
                        <Button
                            type={"delete"}
                            icon={<IconTrash size={16} />}
                            className={
                                "flex-1 flex items-center justify-center gap-1.5 py-2 rounded-lg bg-danger-100 text-danger-600 hover:bg-danger-200 dark:bg-danger-900/50 dark:text-danger-400 text-sm font-medium"
                            }
                            url={route("customers.destroy", customer.id)}
                            label="Hapus"
                        />
                    )}
                </div>
            )}
        </div>
    );
}

export default function Index({ customers, filters = {} }) {
    const { can } = useAuthorization();
    const [viewMode, setViewMode] = useState("grid");
    const [showFilter, setShowFilter] = useState(false);
    const canCreateCustomers = can("customers-create");
    const canEditCustomers = can("customers-edit");
    const canDeleteCustomers = can("customers-delete");

    const [localFilters, setLocalFilters] = useState({
        tier: filters.tier ?? "all",
        period: filters.period ?? "all",
        date_from: filters.date_from ?? "",
        date_to: filters.date_to ?? "",
        per_page: filters.per_page ?? "12",
    });

    const tierOptions = [
        { value: "all", label: "Semua Tier" },
        { value: "non-member", label: "Non-Member" },
        { value: "regular", label: "Regular" },
        { value: "silver", label: "Silver" },
        { value: "gold", label: "Gold" },
        { value: "platinum", label: "Platinum" },
    ];

    const periodOptions = [
        { value: "all", label: "Semua Waktu" },
        { value: "today", label: "Hari Ini" },
        { value: "week", label: "7 Hari Terakhir" },
        { value: "month", label: "30 Hari Terakhir" },
        { value: "3month", label: "3 Bulan Terakhir" },
        { value: "year", label: "1 Tahun Terakhir" },
        { value: "custom", label: "Pilih Tanggal..." },
    ];

    const perPageOptions = [
        { value: "12", label: "12 per halaman" },
        { value: "24", label: "24 per halaman" },
        { value: "48", label: "48 per halaman" },
        { value: "96", label: "96 per halaman" },
    ];

    const applyFilters = (newFilters) => {
        router.get(
            route("customers.index"),
            {
                ...filters,
                ...newFilters,
                page: 1,
            },
            { preserveScroll: true, replace: true },
        );
    };

    const handleFilterChange = (key, value) => {
        let updated = { ...localFilters, [key]: value };
        // Kalau ganti period ke bukan custom, clear date_from & date_to
        if (key === "period" && value !== "custom") {
            updated = { ...updated, date_from: "", date_to: "" };
        }
        setLocalFilters(updated);
        // Kalau custom tapi date belum diisi, jangan apply dulu
        if (
            updated.period === "custom" &&
            (!updated.date_from || !updated.date_to)
        )
            return;
        applyFilters(updated);
    };

    const handleDateChange = (key, value) => {
        const updated = { ...localFilters, [key]: value };
        setLocalFilters(updated);
        // Apply hanya kalau kedua tanggal sudah diisi
        if (updated.date_from && updated.date_to) {
            applyFilters(updated);
        }
    };

    const resetFilters = () => {
        const reset = {
            tier: "all",
            period: "all",
            date_from: "",
            date_to: "",
            per_page: "12",
        };
        setLocalFilters(reset);
        applyFilters(reset);
    };

    const hasActiveFilter =
        localFilters.tier !== "all" ||
        localFilters.period !== "all" ||
        localFilters.per_page !== "12";

    const activePeriodLabel =
        localFilters.period === "custom" &&
        localFilters.date_from &&
        localFilters.date_to
            ? `${localFilters.date_from} – ${localFilters.date_to}`
            : (periodOptions.find((o) => o.value === localFilters.period)
                  ?.label ?? "Semua Waktu");

    return (
        <>
            <Head title="Pelanggan" />

            {/* Header */}
            <div className="mb-6">
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">
                            Pelanggan
                        </h1>
                        <p className="text-sm text-slate-500 dark:text-slate-400">
                            {customers.total || customers.data?.length || 0}{" "}
                            pelanggan terdaftar
                        </p>
                    </div>
                    {canCreateCustomers && (
                        <Button
                            type={"link"}
                            icon={
                                <IconCirclePlus size={18} strokeWidth={1.5} />
                            }
                            className={
                                "bg-primary-500 hover:bg-primary-600 text-white shadow-lg shadow-primary-500/30"
                            }
                            label={"Tambah Pelanggan"}
                            href={route("customers.create")}
                        />
                    )}
                </div>
            </div>

            {/* Toolbar */}
            <div className="mb-4 flex flex-col sm:flex-row justify-between items-stretch sm:items-center gap-3">
                <div className="w-full sm:w-80">
                    <Search
                        url={route("customers.index")}
                        placeholder="Cari pelanggan..."
                    />
                </div>
                <div className="flex items-center gap-2">
                    {/* Tombol filter */}
                    <button
                        onClick={() => setShowFilter(!showFilter)}
                        className={`flex items-center gap-1.5 px-3 py-2 rounded-lg border text-sm font-medium transition-colors ${
                            hasActiveFilter
                                ? "border-primary-400 bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-400 dark:border-primary-700"
                                : "border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-800"
                        }`}
                    >
                        <IconFilter size={16} />
                        Filter
                        {hasActiveFilter && (
                            <span className="ml-1 w-5 h-5 rounded-full bg-primary-500 text-white text-xs flex items-center justify-center">
                                {
                                    [
                                        localFilters.tier !== "all",
                                        localFilters.period !== "all",
                                    ].filter(Boolean).length
                                }
                            </span>
                        )}
                        <IconChevronDown
                            size={14}
                            className={`transition-transform ${showFilter ? "rotate-180" : ""}`}
                        />
                    </button>
                    {/* View mode */}
                    <button
                        onClick={() => setViewMode("grid")}
                        className={`p-2.5 rounded-lg transition-colors ${
                            viewMode === "grid"
                                ? "bg-primary-100 text-primary-600 dark:bg-primary-900/50 dark:text-primary-400"
                                : "text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
                        }`}
                        title="Grid View"
                    >
                        <IconLayoutGrid size={20} />
                    </button>
                    <button
                        onClick={() => setViewMode("list")}
                        className={`p-2.5 rounded-lg transition-colors ${
                            viewMode === "list"
                                ? "bg-primary-100 text-primary-600 dark:bg-primary-900/50 dark:text-primary-400"
                                : "text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
                        }`}
                        title="List View"
                    >
                        <IconList size={20} />
                    </button>
                </div>
            </div>

            {/* Filter Panel */}
            {showFilter && (
                <div className="mb-4 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-4">
                    <div className="flex items-center justify-between mb-3">
                        <p className="text-sm font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                            <IconFilter size={15} />
                            Filter Pelanggan
                        </p>
                        {hasActiveFilter && (
                            <button
                                onClick={resetFilters}
                                className="flex items-center gap-1 text-xs text-slate-400 hover:text-red-500 transition-colors"
                            >
                                <IconX size={13} />
                                Reset filter
                            </button>
                        )}
                    </div>
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                        {/* Filter Tier */}
                        <div>
                            <label className="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">
                                Loyalty Tier
                            </label>
                            <select
                                value={localFilters.tier}
                                onChange={(e) =>
                                    handleFilterChange("tier", e.target.value)
                                }
                                className="w-full h-9 px-3 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
                            >
                                {tierOptions.map((opt) => (
                                    <option key={opt.value} value={opt.value}>
                                        {opt.label}
                                    </option>
                                ))}
                            </select>
                        </div>

                        {/* Filter Periode */}
                        <div
                            className={
                                localFilters.period === "custom"
                                    ? "sm:col-span-2"
                                    : ""
                            }
                        >
                            <label className="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">
                                Periode Bergabung
                            </label>
                            <div
                                className={`flex gap-2 ${localFilters.period === "custom" ? "flex-col sm:flex-row" : ""}`}
                            >
                                <select
                                    value={localFilters.period}
                                    onChange={(e) =>
                                        handleFilterChange(
                                            "period",
                                            e.target.value,
                                        )
                                    }
                                    className="w-full h-9 px-3 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
                                >
                                    {periodOptions.map((opt) => (
                                        <option
                                            key={opt.value}
                                            value={opt.value}
                                        >
                                            {opt.label}
                                        </option>
                                    ))}
                                </select>

                                {/* Date range picker — muncul kalau pilih "Pilih Tanggal" */}
                                {localFilters.period === "custom" && (
                                    <div className="flex items-center gap-2 flex-1">
                                        <div className="relative flex-1">
                                            <input
                                                type="date"
                                                value={localFilters.date_from}
                                                max={
                                                    localFilters.date_to ||
                                                    undefined
                                                }
                                                onChange={(e) =>
                                                    handleDateChange(
                                                        "date_from",
                                                        e.target.value,
                                                    )
                                                }
                                                className="w-full h-9 px-3 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
                                            />
                                        </div>
                                        <span className="text-slate-400 text-sm shrink-0">
                                            —
                                        </span>
                                        <div className="relative flex-1">
                                            <input
                                                type="date"
                                                value={localFilters.date_to}
                                                min={
                                                    localFilters.date_from ||
                                                    undefined
                                                }
                                                onChange={(e) =>
                                                    handleDateChange(
                                                        "date_to",
                                                        e.target.value,
                                                    )
                                                }
                                                className="w-full h-9 px-3 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
                                            />
                                        </div>
                                    </div>
                                )}
                            </div>
                            {localFilters.period === "custom" &&
                                (!localFilters.date_from ||
                                    !localFilters.date_to) && (
                                    <p className="mt-1 text-xs text-amber-500">
                                        Pilih tanggal mulai dan selesai
                                    </p>
                                )}
                        </div>

                        {/* Per Page — sembunyikan kalau custom supaya tidak overflow */}
                        {localFilters.period !== "custom" && (
                            <div>
                                <label className="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">
                                    Tampilkan
                                </label>
                                <select
                                    value={localFilters.per_page}
                                    onChange={(e) =>
                                        handleFilterChange(
                                            "per_page",
                                            e.target.value,
                                        )
                                    }
                                    className="w-full h-9 px-3 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
                                >
                                    {perPageOptions.map((opt) => (
                                        <option
                                            key={opt.value}
                                            value={opt.value}
                                        >
                                            {opt.label}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        )}
                        {localFilters.period === "custom" && (
                            <div className="sm:col-span-3">
                                <label className="block text-xs font-medium text-slate-500 dark:text-slate-400 mb-1">
                                    Tampilkan
                                </label>
                                <select
                                    value={localFilters.per_page}
                                    onChange={(e) =>
                                        handleFilterChange(
                                            "per_page",
                                            e.target.value,
                                        )
                                    }
                                    className="w-full sm:w-40 h-9 px-3 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
                                >
                                    {perPageOptions.map((opt) => (
                                        <option
                                            key={opt.value}
                                            value={opt.value}
                                        >
                                            {opt.label}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        )}
                    </div>

                    {/* Active filter chips */}
                    {hasActiveFilter && (
                        <div className="mt-3 flex flex-wrap gap-2">
                            {localFilters.tier !== "all" && (
                                <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-xs font-medium">
                                    Tier:{" "}
                                    {
                                        tierOptions.find(
                                            (o) =>
                                                o.value === localFilters.tier,
                                        )?.label
                                    }
                                    <button
                                        onClick={() =>
                                            handleFilterChange("tier", "all")
                                        }
                                    >
                                        <IconX size={11} />
                                    </button>
                                </span>
                            )}
                            {localFilters.period !== "all" && (
                                <span className="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-primary-100 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300 text-xs font-medium">
                                    {activePeriodLabel}
                                    <button
                                        onClick={() =>
                                            handleFilterChange("period", "all")
                                        }
                                    >
                                        <IconX size={11} />
                                    </button>
                                </span>
                            )}
                        </div>
                    )}
                </div>
            )}

            {/* Content */}
            {customers.data.length > 0 ? (
                viewMode === "grid" ? (
                    /* Grid View */
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        {customers.data.map((customer) => (
                            <CustomerCard
                                key={customer.id}
                                customer={customer}
                                canUpdate={canEditCustomers}
                                canDelete={canDeleteCustomers}
                            />
                        ))}
                    </div>
                ) : (
                    /* List View */
                    <Table.Card title={"Data Pelanggan"}>
                        <Table>
                            <Table.Thead>
                                <tr>
                                    <Table.Th className="w-10">No</Table.Th>
                                    <Table.Th>Pelanggan</Table.Th>
                                    <Table.Th>Loyalty</Table.Th>
                                    <Table.Th>No. Telepon</Table.Th>
                                    <Table.Th>Alamat</Table.Th>
                                    <Table.Th></Table.Th>
                                </tr>
                            </Table.Thead>
                            <Table.Tbody>
                                {customers.data.map((customer, i) => (
                                    <tr
                                        className="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
                                        key={customer.id}
                                    >
                                        <Table.Td className="text-center">
                                            {++i +
                                                (customers.current_page - 1) *
                                                    customers.per_page}
                                        </Table.Td>
                                        <Table.Td>
                                            <div className="flex items-center gap-3">
                                                {customer.avatar ? (
                                                    <img
                                                        src={customer.avatar}
                                                        alt={customer.name}
                                                        className="w-10 h-10 rounded-full object-cover border border-slate-200 dark:border-slate-700 flex-shrink-0"
                                                    />
                                                ) : (
                                                    <div className="w-10 h-10 rounded-full bg-gradient-to-br from-accent-400 to-accent-600 flex items-center justify-center text-white text-sm font-semibold flex-shrink-0">
                                                        {customer.name
                                                            .charAt(0)
                                                            .toUpperCase()}
                                                    </div>
                                                )}
                                                <p className="text-sm font-medium text-slate-800 dark:text-slate-200">
                                                    <Link
                                                        href={route(
                                                            "customers.show",
                                                            customer.id,
                                                        )}
                                                        className="hover:text-primary-600"
                                                    >
                                                        {customer.name}
                                                    </Link>
                                                </p>
                                            </div>
                                        </Table.Td>
                                        <Table.Td>
                                            <div className="flex flex-col">
                                                <span className="text-xs font-semibold text-primary-600 dark:text-primary-300">
                                                    {customer.is_loyalty_member
                                                        ? customer.loyalty_tier
                                                        : "non-member"}
                                                </span>
                                                <span className="text-xs text-slate-500 dark:text-slate-400">
                                                    {customer.loyalty_points ||
                                                        0}{" "}
                                                    poin
                                                </span>
                                            </div>
                                        </Table.Td>
                                        <Table.Td>
                                            <div className="flex items-center gap-2">
                                                <span className="text-sm text-slate-600 dark:text-slate-400">
                                                    {customer.no_telp || "-"}
                                                </span>
                                                {waGreetingUrl(
                                                    customer.no_telp,
                                                    customer.name,
                                                ) && (
                                                    <a
                                                        href={waGreetingUrl(
                                                            customer.no_telp,
                                                            customer.name,
                                                        )}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="flex items-center justify-center w-7 h-7 rounded-lg bg-green-100 text-green-600 hover:bg-green-200 dark:bg-green-900/50 dark:text-green-400 transition-colors"
                                                        title="Chat WhatsApp dengan greeting"
                                                    >
                                                        <IconBrandWhatsapp
                                                            size={14}
                                                        />
                                                    </a>
                                                )}
                                            </div>
                                        </Table.Td>
                                        <Table.Td>
                                            <p className="text-sm text-slate-500 dark:text-slate-400 line-clamp-1">
                                                {customer.address || "-"}
                                            </p>
                                        </Table.Td>
                                        <Table.Td>
                                            <div className="flex gap-2">
                                                {canEditCustomers && (
                                                    <Button
                                                        type={"edit"}
                                                        icon={
                                                            <IconPencilCog
                                                                size={16}
                                                                strokeWidth={
                                                                    1.5
                                                                }
                                                            />
                                                        }
                                                        className={
                                                            "border bg-warning-100 border-warning-200 text-warning-600 hover:bg-warning-200 dark:bg-warning-900/50 dark:border-warning-800 dark:text-warning-400"
                                                        }
                                                        href={route(
                                                            "customers.edit",
                                                            customer.id,
                                                        )}
                                                    />
                                                )}
                                                {canDeleteCustomers && (
                                                    <Button
                                                        type={"delete"}
                                                        icon={
                                                            <IconTrash
                                                                size={16}
                                                                strokeWidth={
                                                                    1.5
                                                                }
                                                            />
                                                        }
                                                        className={
                                                            "border bg-danger-100 border-danger-200 text-danger-600 hover:bg-danger-200 dark:bg-danger-900/50 dark:border-danger-800 dark:text-danger-400"
                                                        }
                                                        url={route(
                                                            "customers.destroy",
                                                            customer.id,
                                                        )}
                                                    />
                                                )}
                                            </div>
                                        </Table.Td>
                                    </tr>
                                ))}
                            </Table.Tbody>
                        </Table>
                    </Table.Card>
                )
            ) : (
                /* Empty State */
                <div className="flex flex-col items-center justify-center py-16 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800">
                    <div className="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-4">
                        <IconDatabaseOff
                            size={32}
                            className="text-slate-400"
                            strokeWidth={1.5}
                        />
                    </div>
                    <h3 className="text-lg font-medium text-slate-800 dark:text-slate-200 mb-1">
                        Belum Ada Pelanggan
                    </h3>
                    <p className="text-sm text-slate-500 dark:text-slate-400 mb-4">
                        Tambahkan pelanggan pertama Anda.
                    </p>
                    <Button
                        type={"link"}
                        icon={<IconCirclePlus size={18} />}
                        className={
                            "bg-primary-500 hover:bg-primary-600 text-white"
                        }
                        label={"Tambah Pelanggan"}
                        href={route("customers.create")}
                    />
                </div>
            )}

            {customers.last_page !== 1 && (
                <Pagination links={customers.links} />
            )}
        </>
    );
}

Index.layout = (page) => <DashboardLayout children={page} />;
