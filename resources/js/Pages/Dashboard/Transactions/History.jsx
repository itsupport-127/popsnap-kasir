import React, { useEffect, useState } from "react";
import { Head, router, Link, usePage } from "@inertiajs/react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import Button from "@/Components/Dashboard/Button";
import Table from "@/Components/Dashboard/Table";
import Pagination from "@/Components/Dashboard/Pagination";
import TransactionItemsModal from "@/Components/Dashboard/TransactionItemsModal";
import { useAuthorization } from "@/Utils/authorization";
import {
    IconDatabaseOff,
    IconSearch,
    IconHistory,
    IconCalendar,
    IconReceipt,
    IconPrinter,
    IconFilter,
    IconX,
    IconCheck,
    IconBuildingBank,
    IconAlertCircle,
    IconBrandWhatsapp,
    IconCircleCheck,
    IconClock,
    IconCircleX,
    IconClockX,
    IconCircleDot,
    IconCircleHalf2,
    IconEdit,
    IconTrash,
} from "@tabler/icons-react";

const defaultFilters = {
    invoice: "",
    start_date: "",
    end_date: "",
    category_id: "",
};

const formatCurrency = (value = 0) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(value);

// Label metode bayar buat ditampilin ke siapapun (kasir/staff maupun kalau
// kepakai di konteks customer) — sengaja generalisir semua gateway QRIS jadi
// "QRIS" aja, nama teknis kayak "qris_static"/"midtrans" cuma relevan di
// halaman Setting Payment Gateway (buat bedain provider mana yang lagi aktif).
const paymentMethodLabel = (method) => {
    const labels = {
        cash: "Tunai",
        bank_transfer: "Transfer Bank",
        qris_static: "QRIS",
        doku: "QRIS",
        flip: "QRIS",
        midtrans: "QRIS",
        xendit: "QRIS",
        pay_later: "Piutang",
        dual_cash_qris: "Tunai + QRIS",
    };
    return labels[method] ?? (method ? method.replace(/_/g, " ") : "-");
};

const History = ({ transactions, filters, categories = [] }) => {
    const { can } = useAuthorization();
    const canCreateSalesReturn = can("sales-returns-create");
    const canConfirmPayment = can("transactions-confirm-payment");
    const canCreateCrmCampaign = can("crm-campaigns-create");
    const canEditTransactions = can("transactions-edit");
    const canDeleteTransactions = can("transactions-delete");
    const [filterData, setFilterData] = useState({
        ...defaultFilters,
        ...filters,
    });
    const [showFilters, setShowFilters] = useState(false);
    const [confirmModal, setConfirmModal] = useState({
        open: false,
        transaction: null,
    });
    const [isConfirming, setIsConfirming] = useState(false);

    // Modal detail item
    const [itemModal, setItemModal] = useState({ open: false, transaction: null });

    // Modal ubah status
    const [statusModal, setStatusModal] = useState({ open: false, transaction: null });
    const [isUpdatingStatus, setIsUpdatingStatus] = useState(false);
    const [selectedStatus, setSelectedStatus] = useState("");

    // Modal hapus transaksi (permanen)
    const [deleteModal, setDeleteModal] = useState({ open: false, transaction: null });
    const [deleteConfirmInput, setDeleteConfirmInput] = useState("");
    const [isDeleting, setIsDeleting] = useState(false);

    const openDeleteModal = (transaction) => {
        setDeleteConfirmInput("");
        setDeleteModal({ open: true, transaction });
    };

    const handleDeleteTransaction = () => {
        if (!deleteModal.transaction) return;
        if (deleteConfirmInput.trim() !== deleteModal.transaction.invoice) return;

        setIsDeleting(true);
        router.delete(route("transactions.destroy", deleteModal.transaction.id), {
            data: { confirm_invoice: deleteConfirmInput.trim() },
            preserveScroll: true,
            onSuccess: () => {
                setDeleteModal({ open: false, transaction: null });
                setDeleteConfirmInput("");
            },
            onFinish: () => setIsDeleting(false),
        });
    };

    const handleUpdateStatus = () => {
        if (!selectedStatus || !statusModal.transaction) return;
        setIsUpdatingStatus(true);
        router.patch(
            route("transactions.update-status", statusModal.transaction.id),
            { payment_status: selectedStatus },
            {
                onSuccess: () => {
                    setStatusModal({ open: false, transaction: null });
                    setSelectedStatus("");
                    setIsUpdatingStatus(false);
                },
                onError: () => setIsUpdatingStatus(false),
            }
        );
    };

    useEffect(() => {
        setFilterData({
            ...defaultFilters,
            ...filters,
        });
    }, [filters]);

    const handleChange = (field, value) => {
        setFilterData((prev) => ({
            ...prev,
            [field]: value,
        }));
    };

    const applyFilters = (event) => {
        event.preventDefault();
        router.get(route("transactions.history"), filterData, {
            preserveScroll: true,
            preserveState: true,
        });
        setShowFilters(false);
    };

    const resetFilters = () => {
        setFilterData(defaultFilters);
        router.get(route("transactions.history"), defaultFilters, {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        });
    };

    const rows = transactions?.data ?? [];
    const links = transactions?.links ?? [];
    const currentPage = transactions?.current_page ?? 1;
    const perPage = transactions?.per_page
        ? Number(transactions?.per_page)
        : rows.length || 1;

    const hasActiveFilters =
        filterData.invoice || filterData.start_date || filterData.end_date || filterData.category_id;

    return (
        <>
            <Head title="Riwayat Transaksi" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                            <IconHistory
                                size={28}
                                className="text-primary-500"
                            />
                            Riwayat Transaksi
                        </h1>
                        <p className="text-sm text-slate-500 dark:text-slate-400">
                            {transactions?.total || 0} transaksi tercatat
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <button
                            onClick={() => setShowFilters(!showFilters)}
                            className={`inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border text-sm font-medium transition-colors ${
                                showFilters || hasActiveFilters
                                    ? "bg-primary-50 border-primary-200 text-primary-700 dark:bg-primary-950/50 dark:border-primary-800 dark:text-primary-400"
                                    : "bg-white border-slate-200 text-slate-700 hover:bg-slate-50 dark:bg-slate-900 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                            }`}
                        >
                            <IconFilter size={18} />
                            <span>Filter</span>
                            {hasActiveFilters && (
                                <span className="w-2 h-2 rounded-full bg-primary-500"></span>
                            )}
                        </button>
                        <Link
                            href={route("transactions.index")}
                            className="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-primary-500 hover:bg-primary-600 text-white text-sm font-medium transition-colors shadow-lg shadow-primary-500/30"
                        >
                            <IconReceipt size={18} />
                            <span>Transaksi Baru</span>
                        </Link>
                    </div>
                </div>

                {/* Filters Panel */}
                {showFilters && (
                    <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5 animate-slide-up">
                        <form onSubmit={applyFilters}>
                            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Nomor Invoice
                                    </label>
                                    <input
                                        type="text"
                                        placeholder="TRX-..."
                                        value={filterData.invoice}
                                        onChange={(e) =>
                                            handleChange(
                                                "invoice",
                                                e.target.value
                                            )
                                        }
                                        className="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Kategori Produk
                                    </label>
                                    <select
                                        value={filterData.category_id}
                                        onChange={(e) =>
                                            handleChange(
                                                "category_id",
                                                e.target.value
                                            )
                                        }
                                        className="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all"
                                    >
                                        <option value="">Semua Kategori</option>
                                        {categories.map((cat) => (
                                            <option key={cat.id} value={cat.id}>
                                                {cat.name}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Tanggal Mulai
                                    </label>
                                    <input
                                        type="date"
                                        value={filterData.start_date}
                                        onChange={(e) =>
                                            handleChange(
                                                "start_date",
                                                e.target.value
                                            )
                                        }
                                        className="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all"
                                    />
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                        Tanggal Akhir
                                    </label>
                                    <input
                                        type="date"
                                        value={filterData.end_date}
                                        onChange={(e) =>
                                            handleChange(
                                                "end_date",
                                                e.target.value
                                            )
                                        }
                                        className="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all"
                                    />
                                </div>
                                <div className="flex items-end gap-2">
                                    <button
                                        type="submit"
                                        className="flex-1 h-11 inline-flex items-center justify-center gap-2 rounded-xl bg-primary-500 hover:bg-primary-600 text-white font-medium transition-colors"
                                    >
                                        <IconSearch size={18} />
                                        <span>Cari</span>
                                    </button>
                                    {hasActiveFilters && (
                                        <button
                                            type="button"
                                            onClick={resetFilters}
                                            className="h-11 px-4 inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors"
                                        >
                                            <IconX size={18} />
                                        </button>
                                    )}
                                </div>
                            </div>
                        </form>
                    </div>
                )}

                {/* Transaction List */}
                {rows.length > 0 ? (
                    <div className="bg-transparent border-0 shadow-none rounded-2xl sm:bg-white sm:dark:bg-slate-900 sm:border sm:border-slate-200 sm:dark:border-slate-800 sm:overflow-hidden">
                        {/* Desktop Table */}
                        <div className="overflow-x-auto hidden sm:block">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b border-slate-100 dark:border-slate-800">
                                        <th className="px-4 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                            No
                                        </th>
                                        <th className="px-4 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                            Invoice
                                        </th>
                                        <th className="px-4 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                            Tanggal
                                        </th>
                                        <th className="px-4 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                            Kasir
                                        </th>
                                        <th className="px-4 py-4 text-left text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                            Pelanggan
                                        </th>
                                        <th className="px-4 py-4 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                            Item
                                        </th>
                                        <th className="px-4 py-4 text-right text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                            Total
                                        </th>
                                        <th className="px-4 py-4 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                            Metode
                                        </th>
                                        <th className="px-4 py-4 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th className="px-4 py-4 text-center text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                                            Aksi
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {rows.map((transaction, index) => (
                                        <tr
                                            key={transaction.id}
                                            className="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors"
                                        >
                                            <td className="px-4 py-4 text-sm text-slate-600 dark:text-slate-400">
                                                {index +
                                                    1 +
                                                    (currentPage - 1) * perPage}
                                            </td>
                                            <td className="px-4 py-4">
                                                <span className="text-sm font-semibold text-slate-900 dark:text-white">
                                                    {transaction.invoice}
                                                </span>
                                            </td>
                                            <td className="px-4 py-4 text-sm text-slate-600 dark:text-slate-400">
                                                {transaction.created_at}
                                            </td>
                                            <td className="px-4 py-4 text-sm text-slate-600 dark:text-slate-400">
                                                {transaction.cashier?.name ??
                                                    "-"}
                                            </td>
                                            <td className="px-4 py-4">
                                                <span className="px-2 py-1 text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-md">
                                                    {transaction.customer
                                                        ?.name ?? "Umum"}
                                                </span>
                                            </td>
                                            <td className="px-4 py-4 text-center">
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setItemModal({
                                                            open: true,
                                                            transaction,
                                                        })
                                                    }
                                                    className="px-2 py-1 text-xs font-medium bg-primary-100 dark:bg-primary-900/50 text-primary-700 dark:text-primary-400 rounded-full hover:bg-primary-200 dark:hover:bg-primary-900 transition-colors cursor-pointer"
                                                    title="Lihat detail item"
                                                >
                                                    {transaction.total_items ??
                                                        0}
                                                </button>
                                            </td>
                                            <td className="px-4 py-4 text-right text-sm font-semibold text-slate-900 dark:text-white">
                                                {formatCurrency(
                                                    transaction.grand_total ?? 0
                                                )}
                                                {(Number(transaction.discount ?? 0) +
                                                    Number(transaction.customer_voucher_discount ?? 0)) > 0 && (
                                                    <div className="text-xs font-normal text-emerald-600 dark:text-emerald-400">
                                                        Diskon -{formatCurrency(
                                                            Number(transaction.discount ?? 0) +
                                                                Number(transaction.customer_voucher_discount ?? 0)
                                                        )}
                                                    </div>
                                                )}
                                            </td>
                                            <td className="px-4 py-4 text-center">
                                                {(() => {
                                                    const method = transaction.payment_method ?? "cash";
                                                    const map = {
                                                        cash:         { label: "Tunai",        color: "bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400" },
                                                        bank_transfer:{ label: "Transfer Bank", color: "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400" },
                                                        qris_static:  { label: "QRIS",          color: "bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400" },
                                                        doku:         { label: "QRIS",          color: "bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400" },
                                                        flip:         { label: "QRIS",          color: "bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400" },
                                                        midtrans:     { label: "QRIS",          color: "bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400" },
                                                        xendit:       { label: "QRIS",          color: "bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400" },
                                                        pay_later:    { label: "Piutang",       color: "bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400" },
                                                        dual_cash_qris: { label: "Tunai+QRIS",  color: "bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400" },
                                                    };
                                                    const m = map[method] ?? { label: method.replace(/_/g, " "), color: "bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400" };
                                                    return (
                                                        <div className="flex flex-col items-center gap-1">
                                                            <span className={`inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold ${m.color}`}>
                                                                {m.label}
                                                            </span>
                                                            {/* Breakdown khusus Cash+QRIS */}
                                                            {method === "dual_cash_qris" && (
                                                                <div className="text-[10px] text-slate-500 dark:text-slate-400 space-y-0.5 text-left">
                                                                    <div className="flex items-center gap-1">
                                                                        <span className="text-emerald-600 dark:text-emerald-400 font-semibold"></span>
                                                                        <span>Cash: <span className="font-semibold text-slate-700 dark:text-slate-300">{formatCurrency(transaction.dual_cash ?? 0)}</span></span>
                                                                    </div>
                                                                    <div className="flex items-center gap-1">
                                                                        <span className="text-violet-600 dark:text-violet-400 font-semibold"></span>
                                                                        <span>QRIS: <span className="font-semibold text-slate-700 dark:text-slate-300">{formatCurrency(transaction.dual_qris ?? 0)}</span></span>
                                                                    </div>
                                                                </div>
                                                            )}
                                                        </div>
                                                    );
                                                })()}
                                            </td>
                                            <td className="px-4 py-4 text-center">
                                                <div className="flex flex-col items-center gap-1">
                                                    {(() => {
                                                        const s = transaction.payment_status;
                                                        if (transaction.payment_method === "pay_later" && s !== "paid") {
                                                            return <span className="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-full">Piutang</span>;
                                                        } else if (s === "paid") {
                                                            return <span className="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium bg-success-100 dark:bg-success-900/30 text-success-700 dark:text-success-400 rounded-full"><IconCheck size={12} />Lunas</span>;
                                                        } else if (s === "pending") {
                                                            return <span className="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium bg-warning-100 dark:bg-warning-900/30 text-warning-700 dark:text-warning-400 rounded-full">Pending</span>;
                                                        } else if (s === "failed") {
                                                            return <span className="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium bg-danger-100 dark:bg-danger-900/30 text-danger-700 dark:text-danger-400 rounded-full">Gagal</span>;
                                                        } else if (s === "expired") {
                                                            return <span className="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-full">Kedaluwarsa</span>;
                                                        } else {
                                                            return <span className="inline-flex items-center px-2 py-1 text-xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-full">{s ?? "-"}</span>;
                                                        }
                                                    })()}
                                                    {canConfirmPayment && (
                                                        <button
                                                            onClick={() => {
                                                                setSelectedStatus(transaction.payment_status ?? "pending");
                                                                setStatusModal({ open: true, transaction });
                                                            }}
                                                            className="text-[10px] text-slate-400 hover:text-primary-500 dark:hover:text-primary-400 underline underline-offset-2 transition-colors"
                                                        >
                                                            Ubah Status
                                                        </button>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-4 py-4">
                                                <div className="flex items-center justify-center gap-2">
                                                    {canCreateSalesReturn ? (
                                                        transaction.can_create_sales_return ? (
                                                        <Link
                                                            href={route(
                                                                "sales-returns.create",
                                                                transaction.id
                                                            )}
                                                            className="inline-flex items-center justify-center rounded-lg bg-warning-50 px-3 py-2 text-xs font-semibold text-warning-700 hover:bg-warning-100 dark:bg-warning-950/30 dark:text-warning-300"
                                                            title="Buat retur"
                                                        >
                                                            Retur
                                                        </Link>
                                                        ) : (
                                                        <span className="inline-flex items-center justify-center rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                                            Retur selesai
                                                        </span>
                                                        )
                                                    ) : null}
                                                    <a
                                                        href={`https://wa.me/?text=${encodeURIComponent(
                                                            `Invoice ${transaction.invoice}: ${route(
                                                                "transactions.public",
                                                                transaction.invoice,
                                                                true
                                                            )}`
                                                        )}`}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="inline-flex items-center justify-center w-9 h-9 rounded-lg text-green-600 hover:text-green-700 bg-green-50 hover:bg-green-100 dark:bg-green-900/30 dark:hover:bg-green-900/50 transition-colors"
                                                        title="Bagikan ke WhatsApp"
                                                    >
                                                        <IconBrandWhatsapp
                                                            size={18}
                                                        />
                                                    </a>
                                                    {canCreateCrmCampaign && (
                                                        <Link
                                                            href={route(
                                                                "transactions.share-campaign",
                                                                transaction.id
                                                            )}
                                                            method="post"
                                                            as="button"
                                                            className="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-primary-50 text-primary-600 transition-colors hover:bg-primary-100 hover:text-primary-700 dark:bg-primary-950/30 dark:hover:bg-primary-950/50"
                                                            title="Buat campaign share"
                                                        >
                                                            <IconBuildingBank
                                                                size={18}
                                                            />
                                                        </Link>
                                                    )}
                                                    <Link
                                                        href={route(
                                                            "transactions.print",
                                                            transaction.invoice
                                                        )}
                                                        className="inline-flex items-center justify-center w-9 h-9 rounded-lg text-slate-500 hover:text-primary-600 hover:bg-primary-50 dark:hover:bg-primary-950/50 transition-colors"
                                                        title="Cetak Struk"
                                                    >
                                                        <IconPrinter size={18} />
                                                    </Link>
                                                    {canEditTransactions && (
                                                        <Link
                                                            href={route(
                                                                "transactions.edit",
                                                                transaction.id
                                                            )}
                                                            className="inline-flex items-center justify-center w-9 h-9 rounded-lg text-amber-600 hover:text-amber-800 bg-amber-50 hover:bg-amber-100 dark:bg-amber-950/30 dark:hover:bg-amber-950/50 transition-colors"
                                                            title="Edit transaksi"
                                                        >
                                                            <IconEdit size={18} />
                                                        </Link>
                                                    )}
                                                    {canDeleteTransactions && (
                                                        <button
                                                            type="button"
                                                            onClick={() => openDeleteModal(transaction)}
                                                            className="inline-flex items-center justify-center w-9 h-9 rounded-lg text-danger-500 hover:text-danger-700 bg-danger-50 hover:bg-danger-100 dark:bg-danger-950/30 dark:hover:bg-danger-950/50 transition-colors"
                                                            title="Hapus transaksi (permanen)"
                                                        >
                                                            <IconTrash size={18} />
                                                        </button>
                                                    )}
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Mobile Cards */}
                        <div className="sm:hidden flex flex-col gap-3 px-1">
                            {rows.map((transaction, index) => (
                                <div
                                    key={transaction.id}
                                    className="p-4 space-y-3 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl shadow-sm"
                                >
                                    <div className="flex items-center justify-between">
                                        <div className="space-y-1">
                                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                                No {index + 1 + (currentPage - 1) * perPage}
                                            </p>
                                            <p className="text-base font-semibold text-slate-900 dark:text-white">
                                                {transaction.invoice}
                                            </p>
                                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                                {transaction.created_at}
                                            </p>
                                        </div>
                                        <div className="text-right space-y-2">
                                            <div className="flex flex-wrap gap-2 justify-end">
                                                {transaction.payment_method ===
                                                    "pay_later" &&
                                                transaction.payment_status !==
                                                    "paid" ? (
                                                    <span className="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 rounded-full">
                                                        Piutang
                                                    </span>
                                                ) : transaction.payment_status ===
                                                  "paid" ? (
                                                    <span className="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium bg-success-100 dark:bg-success-900/30 text-success-700 dark:text-success-400 rounded-full">
                                                        <IconCheck size={12} />
                                                        Lunas
                                                    </span>
                                                ) : transaction.payment_status ===
                                                  "pending" ? (
                                                    <span className="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium bg-warning-100 dark:bg-warning-900/30 text-warning-700 dark:text-warning-400 rounded-full">
                                                        Pending
                                                    </span>
                                                ) : (
                                                    <span className="inline-flex items-center px-2 py-1 text-xs font-medium bg-danger-100 dark:bg-danger-900/30 text-danger-700 dark:text-danger-400 rounded-full">
                                                        {transaction.payment_status ?? "-"}
                                                    </span>
                                                )}
                                                {canConfirmPayment && (
                                                    <button
                                                        onClick={() => {
                                                            setSelectedStatus(transaction.payment_status ?? "pending");
                                                            setStatusModal({ open: true, transaction });
                                                        }}
                                                        className="text-[10px] text-slate-400 hover:text-primary-500 underline"
                                                    >
                                                        Ubah Status
                                                    </button>
                                                )}
                                            </div>
                                            <p className="text-sm font-semibold text-slate-900 dark:text-white">
                                                {formatCurrency(
                                                    transaction.grand_total ?? 0
                                                )}
                                            </p>
                                            {(Number(transaction.discount ?? 0) +
                                                Number(transaction.customer_voucher_discount ?? 0)) > 0 && (
                                                <p className="text-[11px] text-emerald-600 dark:text-emerald-400">
                                                    Diskon -{formatCurrency(
                                                        Number(transaction.discount ?? 0) +
                                                            Number(transaction.customer_voucher_discount ?? 0)
                                                    )}
                                                </p>
                                            )}
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-2 text-sm text-slate-600 dark:text-slate-300">
                                        <div>
                                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                                Kasir
                                            </p>
                                            <p className="font-medium">
                                                {transaction.cashier?.name ??
                                                    "-"}
                                            </p>
                                        </div>
                                        <div className="text-right">
                                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                                Pelanggan
                                            </p>
                                            <p className="font-medium">
                                                {transaction.customer?.name ??
                                                    "Umum"}
                                            </p>
                                        </div>
                                        <div>
                                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                                Item
                                            </p>
                                            <button
                                                type="button"
                                                onClick={() =>
                                                    setItemModal({
                                                        open: true,
                                                        transaction,
                                                    })
                                                }
                                                className="font-medium text-primary-600 dark:text-primary-400 underline underline-offset-2"
                                            >
                                                {transaction.total_items ?? 0}
                                            </button>
                                        </div>
                                        <div className="text-right">
                                            <p className="text-xs text-slate-500 dark:text-slate-400">
                                                Pembayaran
                                            </p>
                                            {transaction.payment_method === "dual_cash_qris" ? (
                                                <div className="space-y-0.5 text-right">
                                                    <p className="font-semibold text-teal-600 dark:text-teal-400 text-xs">Cash+QRIS</p>
                                                    <p className="text-[10px] text-slate-500">💵 {formatCurrency(transaction.dual_cash ?? 0)}</p>
                                                    <p className="text-[10px] text-slate-500">📱 {formatCurrency(transaction.dual_qris ?? 0)}</p>
                                                </div>
                                            ) : (
                                                <p className="font-medium">
                                                    {paymentMethodLabel(transaction.payment_method)}
                                                </p>
                                            )}
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-2">
                                        {canCreateSalesReturn ? (
                                            transaction.can_create_sales_return ? (
                                            <Link
                                                href={route(
                                                    "sales-returns.create",
                                                    transaction.id
                                                )}
                                                className="inline-flex items-center justify-center gap-1 px-3 py-2 text-xs font-semibold rounded-lg bg-warning-50 text-warning-700 hover:bg-warning-100 dark:bg-warning-950/30 dark:text-warning-300"
                                            >
                                                Retur
                                            </Link>
                                            ) : (
                                            <div className="inline-flex items-center justify-center gap-1 px-3 py-2 text-xs font-semibold rounded-lg bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                                Retur selesai
                                            </div>
                                            )
                                        ) : null}
                                        <Link
                                            href={route(
                                                "transactions.print",
                                                transaction.invoice
                                            )}
                                            className="inline-flex items-center justify-center gap-1 px-3 py-2 text-xs font-semibold rounded-lg bg-primary-50 text-primary-700 hover:bg-primary-100 dark:bg-primary-900/30 dark:text-primary-300 dark:hover:bg-primary-900/50"
                                        >
                                            Detail
                                        </Link>
                                        <a
                                            href={route(
                                                "transactions.public",
                                                transaction.invoice
                                            )}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex items-center justify-center gap-1 px-3 py-2 text-xs font-semibold rounded-lg bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-200 dark:hover:bg-slate-700"
                                        >
                                            Invoice
                                        </a>
                                        {canCreateCrmCampaign && (
                                            <Link
                                                href={route(
                                                    "transactions.share-campaign",
                                                    transaction.id
                                                )}
                                                method="post"
                                                as="button"
                                                className="inline-flex items-center justify-center gap-1 rounded-lg bg-primary-50 px-3 py-2 text-xs font-semibold text-primary-700 hover:bg-primary-100 dark:bg-primary-900/30 dark:text-primary-300 dark:hover:bg-primary-900/50"
                                            >
                                                Campaign WA
                                            </Link>
                                        )}
                                        <a
                                            href={route(
                                                "pdf.transactions.shipping",
                                                transaction.invoice
                                            )}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="inline-flex items-center justify-center gap-1 px-3 py-2 text-xs font-semibold rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-900/30 dark:text-emerald-300 dark:hover:bg-emerald-900/50"
                                        >
                                            Resi
                                        </a>
                                        {canEditTransactions && (
                                            <Link
                                                href={route(
                                                    "transactions.edit",
                                                    transaction.id
                                                )}
                                                className="inline-flex items-center justify-center gap-1 px-3 py-2 text-xs font-semibold rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/30 dark:text-amber-400 dark:hover:bg-amber-950/50"
                                            >
                                                <IconEdit size={14} />
                                                Edit
                                            </Link>
                                        )}
                                        {canDeleteTransactions && (
                                            <button
                                                type="button"
                                                onClick={() => openDeleteModal(transaction)}
                                                className="inline-flex items-center justify-center gap-1 px-3 py-2 text-xs font-semibold rounded-lg bg-danger-50 text-danger-600 hover:bg-danger-100 dark:bg-danger-950/30 dark:text-danger-400 dark:hover:bg-danger-950/50"
                                            >
                                                <IconTrash size={14} />
                                                Hapus
                                            </button>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>
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
                            Belum Ada Transaksi
                        </h3>
                        <p className="text-sm text-slate-500 dark:text-slate-400">
                            {hasActiveFilters
                                ? "Tidak ada transaksi sesuai filter."
                                : "Transaksi akan muncul di sini."}
                        </p>
                    </div>
                )}

                {links.length > 3 && <Pagination links={links} />}
            </div>

            {/* Confirmation Modal */}
            {confirmModal.open && confirmModal.transaction && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                    {/* Backdrop */}
                    <div
                        className="absolute inset-0 bg-black/50 backdrop-blur-sm"
                        onClick={() =>
                            !isConfirming &&
                            setConfirmModal({ open: false, transaction: null })
                        }
                    />

                    {/* Modal */}
                    <div className="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-in fade-in zoom-in duration-200">
                        {/* Header */}
                        <div className="bg-gradient-to-r from-primary-500 to-primary-600 px-6 py-5 text-white">
                            <div className="flex items-center gap-3">
                                <div className="w-12 h-12 rounded-full bg-white/20 flex items-center justify-center">
                                    <IconBuildingBank size={24} />
                                </div>
                                <div>
                                    <h3 className="text-lg font-bold">
                                        Konfirmasi Pembayaran
                                    </h3>
                                    <p className="text-sm opacity-90">
                                        Transfer Bank
                                    </p>
                                </div>
                            </div>
                        </div>

                        {/* Content */}
                        <div className="p-6 space-y-4">
                            {/* Invoice Info */}
                            <div className="bg-slate-50 dark:bg-slate-800 rounded-xl p-4">
                                <div className="flex justify-between items-center mb-2">
                                    <span className="text-sm text-slate-500 dark:text-slate-400">
                                        Invoice
                                    </span>
                                    <span className="text-sm font-bold text-slate-900 dark:text-white">
                                        {confirmModal.transaction.invoice}
                                    </span>
                                </div>
                                <div className="flex justify-between items-center mb-2">
                                    <span className="text-sm text-slate-500 dark:text-slate-400">
                                        Pelanggan
                                    </span>
                                    <span className="text-sm font-medium text-slate-700 dark:text-slate-300">
                                        {confirmModal.transaction.customer
                                            ?.name ?? "Umum"}
                                    </span>
                                </div>
                                <div className="flex justify-between items-center">
                                    <span className="text-sm text-slate-500 dark:text-slate-400">
                                        Total
                                    </span>
                                    <span className="text-lg font-bold text-primary-600 dark:text-primary-400">
                                        {formatCurrency(
                                            confirmModal.transaction
                                                .grand_total ?? 0
                                        )}
                                    </span>
                                </div>
                            </div>

                            {/* Confirmation Message */}
                            <div className="flex items-start gap-3 p-4 bg-warning-50 dark:bg-warning-900/20 rounded-xl border border-warning-200 dark:border-warning-800">
                                <IconAlertCircle
                                    size={20}
                                    className="text-warning-600 dark:text-warning-400 flex-shrink-0 mt-0.5"
                                />
                                <p className="text-sm text-warning-800 dark:text-warning-300">
                                    Pastikan dana sudah diterima sebelum
                                    mengkonfirmasi pembayaran ini. Tindakan ini
                                    tidak dapat dibatalkan.
                                </p>
                            </div>
                        </div>

                        {/* Actions */}
                        <div className="px-6 pb-6 flex gap-3">
                            <button
                                onClick={() =>
                                    setConfirmModal({
                                        open: false,
                                        transaction: null,
                                    })
                                }
                                disabled={isConfirming}
                                className="flex-1 px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors disabled:opacity-50"
                            >
                                Batal
                            </button>
                            <button
                                onClick={() => {
                                    setIsConfirming(true);
                                    router.patch(
                                        route(
                                            "transactions.confirm-payment",
                                            confirmModal.transaction.id
                                        ),
                                        {},
                                        {
                                            onSuccess: () => {
                                                setConfirmModal({
                                                    open: false,
                                                    transaction: null,
                                                });
                                                setIsConfirming(false);
                                            },
                                            onError: () => {
                                                setIsConfirming(false);
                                            },
                                        }
                                    );
                                }}
                                disabled={isConfirming}
                                className="flex-1 px-4 py-3 rounded-xl bg-success-500 hover:bg-success-600 text-white font-medium transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
                            >
                                {isConfirming ? (
                                    <>
                                        <svg
                                            className="animate-spin h-4 w-4"
                                            viewBox="0 0 24 24"
                                        >
                                            <circle
                                                className="opacity-25"
                                                cx="12"
                                                cy="12"
                                                r="10"
                                                stroke="currentColor"
                                                strokeWidth="4"
                                                fill="none"
                                            />
                                            <path
                                                className="opacity-75"
                                                fill="currentColor"
                                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                                            />
                                        </svg>
                                        Memproses...
                                    </>
                                ) : (
                                    <>
                                        <IconCheck size={18} />
                                        Konfirmasi Lunas
                                    </>
                                )}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* ===== MODAL DETAIL ITEM ===== */}
            <TransactionItemsModal
                open={itemModal.open}
                transaction={itemModal.transaction}
                onClose={() => setItemModal({ open: false, transaction: null })}
            />

            {/* ===== MODAL UBAH STATUS ===== */}
            {statusModal.open && statusModal.transaction && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div
                        className="absolute inset-0 bg-black/50 backdrop-blur-sm"
                        onClick={() => !isUpdatingStatus && setStatusModal({ open: false, transaction: null })}
                    />
                    <div className="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
                        {/* Header */}
                        <div className="bg-gradient-to-r from-slate-700 to-slate-800 px-6 py-5 text-white">
                            <h3 className="text-lg font-bold">Ubah Status Pembayaran</h3>
                            <p className="text-sm opacity-80">{statusModal.transaction.invoice}</p>
                        </div>
                        {/* Content */}
                        <div className="p-6 space-y-4">
                            <div className="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 space-y-1">
                                <div className="flex justify-between text-sm">
                                    <span className="text-slate-500">Pelanggan</span>
                                    <span className="font-medium text-slate-800 dark:text-white">{statusModal.transaction.customer?.name ?? "Umum"}</span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-slate-500">Total</span>
                                    <span className="font-bold text-slate-800 dark:text-white">{formatCurrency(statusModal.transaction.grand_total ?? 0)}</span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-slate-500">Metode</span>
                                    <span className="font-medium text-slate-800 dark:text-white">{paymentMethodLabel(statusModal.transaction.payment_method)}</span>
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Pilih Status Baru
                                </label>
                                <div className="grid grid-cols-2 gap-2">
                                    {[
                                        { value: "paid",    label: "Lunas",        icon: IconCircleCheck, color: "bg-success-50 border-success-300 text-success-700 dark:bg-success-950/30 dark:border-success-700 dark:text-success-400" },
                                        { value: "pending", label: "Pending",      icon: IconClock,       color: "bg-warning-50 border-warning-300 text-warning-700 dark:bg-warning-950/30 dark:border-warning-700 dark:text-warning-400" },
                                        { value: "failed",  label: "Gagal",        icon: IconCircleX,     color: "bg-danger-50 border-danger-300 text-danger-700 dark:bg-danger-950/30 dark:border-danger-700 dark:text-danger-400" },
                                        { value: "expired", label: "Kedaluwarsa",  icon: IconClockX,      color: "bg-slate-50 border-slate-300 text-slate-700 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-400" },
                                        { value: "unpaid",  label: "Belum Lunas",  icon: IconCircleDot,   color: "bg-amber-50 border-amber-300 text-amber-700 dark:bg-amber-950/30 dark:border-amber-700 dark:text-amber-400" },
                                        { value: "partial", label: "Parsial",      icon: IconCircleHalf2, color: "bg-blue-50 border-blue-300 text-blue-700 dark:bg-blue-950/30 dark:border-blue-700 dark:text-blue-400" },
                                    ].map((s) => (
                                        <button
                                            key={s.value}
                                            onClick={() => setSelectedStatus(s.value)}
                                            className={`flex items-center justify-center gap-1.5 px-3 py-2.5 rounded-xl border-2 text-xs font-semibold transition-all ${
                                                selectedStatus === s.value
                                                    ? s.color + " ring-2 ring-offset-1 ring-current scale-105"
                                                    : "border-slate-200 dark:border-slate-700 text-slate-500 hover:border-slate-300"
                                            }`}
                                        >
                                            <s.icon size={16} />
                                            {s.label}
                                        </button>
                                    ))}
                                </div>
                            </div>
                        </div>
                        {/* Actions */}
                        <div className="px-6 pb-6 flex gap-3">
                            <button
                                onClick={() => setStatusModal({ open: false, transaction: null })}
                                disabled={isUpdatingStatus}
                                className="flex-1 px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors disabled:opacity-50"
                            >
                                Batal
                            </button>
                            <button
                                onClick={handleUpdateStatus}
                                disabled={isUpdatingStatus || !selectedStatus}
                                className="flex-1 px-4 py-3 rounded-xl bg-slate-800 hover:bg-slate-900 dark:bg-slate-700 dark:hover:bg-slate-600 text-white font-medium transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
                            >
                                {isUpdatingStatus ? (
                                    <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                                ) : (
                                    <><IconCheck size={16} /> Simpan Status</>
                                )}
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Modal Hapus Transaksi — PERMANEN, minta ketik ulang invoice */}
            {deleteModal.open && deleteModal.transaction && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div
                        className="absolute inset-0 bg-black/50 backdrop-blur-sm"
                        onClick={() => !isDeleting && setDeleteModal({ open: false, transaction: null })}
                    />
                    <div className="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
                        <div className="bg-gradient-to-r from-danger-600 to-danger-700 px-6 py-5 text-white">
                            <div className="flex items-center gap-2">
                                <IconTrash size={20} />
                                <h3 className="text-lg font-bold">Hapus Transaksi Permanen</h3>
                            </div>
                            <p className="text-sm opacity-80 mt-1">{deleteModal.transaction.invoice}</p>
                        </div>
                        <div className="p-6 space-y-4">
                            <div className="bg-danger-50 dark:bg-danger-950/30 border border-danger-200 dark:border-danger-800 rounded-xl p-4 space-y-1.5 text-sm text-danger-700 dark:text-danger-300">
                                <p className="font-semibold">Aksi ini TIDAK BISA dibatalkan.</p>
                                <p>Transaksi akan hilang selamanya dari riwayat. Stok produk dikembalikan, pemakaian voucher & poin loyalti direset otomatis.</p>
                                <p>Kalau customer beneran mengembalikan barang, pakai fitur <strong>Retur</strong> — bukan ini.</p>
                            </div>
                            <div className="bg-slate-50 dark:bg-slate-800 rounded-xl p-4 space-y-1">
                                <div className="flex justify-between text-sm">
                                    <span className="text-slate-500">Pelanggan</span>
                                    <span className="font-medium text-slate-800 dark:text-white">{deleteModal.transaction.customer?.name ?? "Umum"}</span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-slate-500">Total</span>
                                    <span className="font-bold text-slate-800 dark:text-white">{formatCurrency(deleteModal.transaction.grand_total ?? 0)}</span>
                                </div>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                    Ketik ulang nomor invoice buat konfirmasi:
                                </label>
                                <input
                                    type="text"
                                    value={deleteConfirmInput}
                                    onChange={(e) => setDeleteConfirmInput(e.target.value)}
                                    placeholder={deleteModal.transaction.invoice}
                                    autoFocus
                                    className="w-full h-11 px-4 rounded-xl border border-danger-300 dark:border-danger-800 bg-white dark:bg-slate-900 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-danger-500/20 focus:border-danger-500"
                                />
                            </div>
                        </div>
                        <div className="px-6 pb-6 flex gap-3">
                            <button
                                onClick={() => setDeleteModal({ open: false, transaction: null })}
                                disabled={isDeleting}
                                className="flex-1 px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors disabled:opacity-50"
                            >
                                Batal
                            </button>
                            <button
                                onClick={handleDeleteTransaction}
                                disabled={isDeleting || deleteConfirmInput.trim() !== deleteModal.transaction.invoice}
                                className="flex-1 px-4 py-3 rounded-xl bg-danger-600 hover:bg-danger-700 text-white font-medium transition-colors disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                            >
                                {isDeleting ? (
                                    <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                                ) : (
                                    <><IconTrash size={16} /> Hapus Permanen</>
                                )}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
};

History.layout = (page) => <DashboardLayout children={page} />;

export default History;