import React, { useEffect, useMemo, useState } from "react";
import { Head, Link, router, usePage } from "@inertiajs/react";
import Swal from "sweetalert2";
import toast from "react-hot-toast";
import DashboardLayout from "@/Layouts/DashboardLayout";
import {
    IconArrowLeft,
    IconCashBanknote,
    IconEye,
    IconMail,
    IconMailForward,
    IconReceipt,
    IconRotateClockwise2,
    IconWallet,
} from "@tabler/icons-react";
import { useAuthorization } from "@/Utils/authorization";

const formatCurrency = (value = 0) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(value);

const formatDateTime = (value) => {
    if (!value) return "-";

    return new Intl.DateTimeFormat("id-ID", {
        dateStyle: "full",
        timeStyle: "short",
    }).format(new Date(value));
};

const MONTHS_ID = [
    "Januari", "Februari", "Maret", "April", "Mei", "Juni",
    "Juli", "Agustus", "September", "Oktober", "November", "Desember",
];

const EXPENSE_PRESETS = ["Beli Galon", "Selisih Kembalian", "Lainnya"];

const appendPreset = (current, label) => {
    const trimmed = (current || "").trim();
    if (!trimmed) return `• ${label}`;
    if (trimmed.includes(label)) return trimmed;
    return `${trimmed}\n• ${label}`;
};

function MetricCard({ title, value, icon: Icon }) {
    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
            <div className="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                <Icon size={18} />
                <span>{title}</span>
            </div>
            <p className="mt-3 text-xl font-semibold text-slate-900 dark:text-white">
                {value}
            </p>
        </div>
    );
}

function ExpensePresetChips({ onPick }) {
    return (
        <div className="flex flex-wrap gap-2">
            {EXPENSE_PRESETS.map((label) => (
                <button
                    key={label}
                    type="button"
                    onClick={() => onPick(label)}
                    className="rounded-full border border-amber-300 bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700 transition-colors hover:bg-amber-100 dark:border-amber-800/60 dark:bg-amber-950/30 dark:text-amber-300"
                >
                    {label}
                </button>
            ))}
        </div>
    );
}

function PreviewModal({ open, onClose, summary, note, subject, dateLabel, storeLabel, cashierName, closedAt, shiftId }) {
    if (!open) return null;

    const row = (label, value, muted = false) => (
        <div className="flex items-center justify-between border-b border-slate-100 py-2 last:border-0 dark:border-slate-800">
            <span className="text-sm text-slate-500 dark:text-slate-400">{label}</span>
            <span className={`text-sm font-semibold ${muted ? "text-slate-400" : "text-slate-900 dark:text-white"}`}>
                {value}
            </span>
        </div>
    );

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 p-4" onClick={onClose}>
            <div
                className="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-xl dark:bg-slate-900"
                onClick={(event) => event.stopPropagation()}
            >
                <div className="flex items-center justify-between border-b border-slate-200 px-5 py-3 dark:border-slate-800">
                    <h3 className="text-sm font-semibold text-slate-900 dark:text-white">Preview Email Laporan</h3>
                    <button
                        type="button"
                        onClick={onClose}
                        className="rounded-lg px-2 py-1 text-sm text-slate-500 transition-colors hover:bg-slate-100 dark:hover:bg-slate-800"
                    >
                        Tutup
                    </button>
                </div>
                <div className="max-h-[70vh] overflow-y-auto p-5">
                    <div className="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800">
                        <div className="bg-slate-900 px-5 py-4">
                            <p className="text-[11px] uppercase tracking-widest text-slate-400">Laporan Tutup Kasir</p>
                            <h4 className="mt-1 text-base font-bold text-white">
                                TUTUP KASIR {storeLabel.toUpperCase()} {dateLabel.toUpperCase()}
                            </h4>
                        </div>
                        <div className="px-5 py-4">
                            {row("Cash", formatCurrency(summary.cash))}
                            {row("Qris", formatCurrency(summary.qris))}
                            {row("Total Penjualan", formatCurrency(summary.total))}
                            {row("Pengeluaran", formatCurrency(summary.expense), true)}

                            <div className="mt-4 rounded-lg bg-slate-50 p-3 dark:bg-slate-800/60">
                                <p className="text-[11px] uppercase tracking-wide text-slate-400">Note</p>
                                <p className="mt-1 whitespace-pre-line text-sm text-slate-700 dark:text-slate-300">
                                    {note || "-"}
                                </p>
                            </div>

                            <div className="mt-4 space-y-1 border-t border-slate-100 pt-3 text-xs text-slate-400 dark:border-slate-800">
                                <div className="flex justify-between">
                                    <span>Kasir</span>
                                    <span className="text-slate-600 dark:text-slate-300">{cashierName}</span>
                                </div>
                                <div className="flex justify-between">
                                    <span>Ditutup</span>
                                    <span className="text-slate-600 dark:text-slate-300">
                                        {closedAt ? new Date(closedAt).toLocaleString("id-ID") : "-"}
                                    </span>
                                </div>
                                <div className="flex justify-between">
                                    <span>Shift #</span>
                                    <span className="text-slate-600 dark:text-slate-300">{shiftId}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p className="mt-3 text-center text-[11px] text-slate-400">
                        Subject: {subject}
                    </p>
                </div>
            </div>
        </div>
    );
}

export default function Show({
    cashierShift,
    canForceClose = false,
    canSendReportEmail = false,
    reportRecipients = [],
    storeLabel = "POPSNAP",
    justClosed = false,
}) {
    const { auth, errors } = usePage().props;
    const { can } = useAuthorization();
    const [actualCash, setActualCash] = useState(
        cashierShift.actual_cash !== null ? String(cashierShift.actual_cash) : ""
    );
    const [closeNotes, setCloseNotes] = useState(cashierShift.close_notes || "");
    const [expenseTotal, setExpenseTotal] = useState(
        cashierShift.expense_total ? String(cashierShift.expense_total) : ""
    );
    const [expenseNotes, setExpenseNotes] = useState(cashierShift.expense_notes || "");
    const [sendingEmail, setSendingEmail] = useState(false);
    const [savingExpenses, setSavingExpenses] = useState(false);
    const [showPreview, setShowPreview] = useState(false);

    const canCloseShift = useMemo(() => {
        if (cashierShift.status !== "open") return false;

        return (
            can("cashier-shifts-close") &&
            (cashierShift.user?.id === auth?.user?.id ||
                auth?.super ||
                canForceClose)
        );
    }, [
        auth?.super,
        auth?.user?.id,
        can,
        canForceClose,
        cashierShift.status,
        cashierShift.user?.id,
    ]);

    const canEditExpenses = !canCloseShift && cashierShift.status !== "open" && canSendReportEmail;

    const actualCashNumber = Number(actualCash || 0);
    const expenseTotalNumber = Number(expenseTotal || 0);
    const difference = actualCash === ""
        ? null
        : actualCashNumber - (Number(cashierShift.expected_cash || 0) - expenseTotalNumber);

    const previewSummary = useMemo(() => {
        const cash = Number(cashierShift.cash_sales_total || 0);
        const qris = Number(cashierShift.non_cash_sales_total || 0);
        return {
            cash,
            qris,
            total: cash + qris,
            expense: Number(cashierShift.expense_total || 0),
        };
    }, [cashierShift.cash_sales_total, cashierShift.non_cash_sales_total, cashierShift.expense_total]);

    const previewDate = useMemo(() => {
        const d = cashierShift.closed_at ? new Date(cashierShift.closed_at) : new Date();
        return `${d.getDate()} ${MONTHS_ID[d.getMonth()]} ${d.getFullYear()}`;
    }, [cashierShift.closed_at]);

    const previewSubject = `Tutup Kasir ${storeLabel} ${previewDate}`.toUpperCase();

    const handleCloseShift = (event) => {
        event.preventDefault();

        router.post(route("cashier-shifts.close", cashierShift.id), {
            actual_cash: actualCashNumber,
            close_notes: closeNotes,
            expense_total: expenseTotalNumber,
            expense_notes: expenseNotes,
        });
    };

    const saveExpenses = () => {
        setSavingExpenses(true);
        router.post(
            route("cashier-shifts.expenses.update", cashierShift.id),
            {
                expense_total: expenseTotalNumber,
                expense_notes: expenseNotes,
            },
            {
                preserveScroll: true,
                onSuccess: () => toast.success("Data pengeluaran berhasil disimpan."),
                onError: () => toast.error("Gagal menyimpan data pengeluaran."),
                onFinish: () => setSavingExpenses(false),
            }
        );
    };

    const sendReportEmail = () => {
        setSendingEmail(true);
        router.post(
            route("cashier-shifts.send-report-email", cashierShift.id),
            {},
            {
                preserveScroll: true,
                onSuccess: () => {
                    toast.success("Email laporan tutup kasir berhasil dikirim.");
                },
                onError: () => {
                    toast.error("Gagal mengirim email laporan tutup kasir.");
                },
                onFinish: () => setSendingEmail(false),
            }
        );
    };

    const promptSendEmail = () => {
        Swal.fire({
            title: "Kirim email tutup kasir?",
            html: `Laporan akan dikirim ke:<br/><span style="font-size:12px;color:#64748b;">${reportRecipients.join(", ")}</span>`,
            icon: "question",
            showCancelButton: true,
            confirmButtonColor: "#0ea5e9",
            cancelButtonColor: "#64748b",
            confirmButtonText: "Ya, Kirim Sekarang",
            cancelButtonText: "Nanti Saja",
        }).then((result) => {
            if (result.isConfirmed) {
                sendReportEmail();
            } else {
                toast("Oke, email bisa dikirim manual kapan saja dari halaman ini.", { icon: "📨" });
            }
        });
    };

    useEffect(() => {
        if (justClosed && canSendReportEmail && !cashierShift.report_email_sent_at) {
            promptSendEmail();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [justClosed]);

    return (
        <>
            <Head title={`Shift #${cashierShift.id}`} />

            <PreviewModal
                open={showPreview}
                onClose={() => setShowPreview(false)}
                summary={previewSummary}
                note={cashierShift.expense_notes}
                subject={previewSubject}
                dateLabel={previewDate}
                storeLabel={storeLabel}
                cashierName={cashierShift.user?.name || "-"}
                closedAt={cashierShift.closed_at}
                shiftId={cashierShift.id}
            />

            <div className="space-y-6">
                <div className="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <Link
                            href={route("cashier-shifts.index")}
                            className="mb-3 inline-flex items-center gap-2 text-sm font-medium text-slate-500 transition hover:text-primary-600 dark:text-slate-400 dark:hover:text-primary-400"
                        >
                            <IconArrowLeft size={16} />
                            <span>Kembali ke histori shift</span>
                        </Link>
                        <h1 className="text-2xl font-bold text-slate-900 dark:text-white">
                            Shift Kasir {cashierShift.user?.name || "-"}
                        </h1>
                        <p className="text-sm text-slate-500 dark:text-slate-400">
                            Dibuka {formatDateTime(cashierShift.opened_at)}
                        </p>
                    </div>
                    <span
                        className={`inline-flex rounded-full px-3 py-1.5 text-sm font-semibold ${
                            cashierShift.status === "open"
                                ? "bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400"
                                : cashierShift.status === "force_closed"
                                  ? "bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-400"
                                  : "bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300"
                        }`}
                    >
                        {cashierShift.status === "open"
                            ? "Shift Aktif"
                            : cashierShift.status === "force_closed"
                              ? "Force Closed"
                              : "Shift Closed"}
                    </span>
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <MetricCard title="Modal Awal" value={formatCurrency(cashierShift.opening_cash)} icon={IconWallet} />
                    <MetricCard title="Expected Cash" value={formatCurrency(cashierShift.expected_cash)} icon={IconCashBanknote} />
                    <MetricCard title="Penjualan Tunai" value={formatCurrency(cashierShift.cash_sales_total)} icon={IconReceipt} />
                    <MetricCard title="Refund Tunai" value={formatCurrency(cashierShift.cash_refund_total)} icon={IconRotateClockwise2} />
                </div>

                <div className="grid gap-6 xl:grid-cols-[1.2fr_0.8fr]">
                    <div className="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                        <h2 className="text-lg font-semibold text-slate-900 dark:text-white">
                            Ringkasan Shift
                        </h2>
                        <div className="mt-5 grid gap-4 md:grid-cols-2">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Kasir</p>
                                <p className="mt-2 text-sm text-slate-900 dark:text-white">{cashierShift.user?.name || "-"}</p>
                            </div>
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Dibuka Oleh</p>
                                <p className="mt-2 text-sm text-slate-900 dark:text-white">{cashierShift.opened_by?.name || "-"}</p>
                            </div>
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Waktu Tutup</p>
                                <p className="mt-2 text-sm text-slate-900 dark:text-white">{formatDateTime(cashierShift.closed_at)}</p>
                            </div>
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Ditutup Oleh</p>
                                <p className="mt-2 text-sm text-slate-900 dark:text-white">{cashierShift.closed_by?.name || "-"}</p>
                            </div>
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Total Transaksi</p>
                                <p className="mt-2 text-sm text-slate-900 dark:text-white">{cashierShift.transactions_count}</p>
                            </div>
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Total Retur</p>
                                <p className="mt-2 text-sm text-slate-900 dark:text-white">{cashierShift.sales_returns_count}</p>
                            </div>
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Penjualan Non Tunai</p>
                                <p className="mt-2 text-sm text-slate-900 dark:text-white">{formatCurrency(cashierShift.non_cash_sales_total)}</p>
                            </div>
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Refund Non Tunai</p>
                                <p className="mt-2 text-sm text-slate-900 dark:text-white">{formatCurrency(cashierShift.non_cash_refund_total)}</p>
                            </div>
                        </div>

                        <div className="mt-5 grid gap-4 md:grid-cols-2">
                            <div className="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800/60">
                                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Catatan Shift</p>
                                <p className="mt-2 text-sm text-slate-700 dark:text-slate-300">{cashierShift.notes || "Tidak ada catatan pembukaan."}</p>
                            </div>
                            <div className="rounded-2xl bg-slate-50 p-4 dark:bg-slate-800/60">
                                <p className="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Catatan Closing</p>
                                <p className="mt-2 text-sm text-slate-700 dark:text-slate-300">{cashierShift.close_notes || "Tidak ada catatan penutupan."}</p>
                            </div>
                            <div className="rounded-2xl bg-amber-50 p-4 dark:bg-amber-950/20 md:col-span-2">
                                <div className="flex items-center justify-between">
                                    <p className="text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-400">Pengeluaran</p>
                                    <p className="text-sm font-semibold text-amber-700 dark:text-amber-400">{formatCurrency(cashierShift.expense_total)}</p>
                                </div>
                                <p className="mt-2 text-sm text-slate-700 dark:text-slate-300">
                                    {cashierShift.expense_notes || "Tidak ada catatan pengeluaran."}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="space-y-6">
                        <div className="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                            <h2 className="text-lg font-semibold text-slate-900 dark:text-white">
                                Tutup Kas
                            </h2>
                            <div className="mt-4 space-y-3">
                                <div className="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/60">
                                    <span className="text-sm text-slate-500 dark:text-slate-400">Ekspektasi Kas</span>
                                    <span className="font-semibold text-slate-900 dark:text-white">{formatCurrency(cashierShift.expected_cash)}</span>
                                </div>
                                <div className="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/60">
                                    <span className="text-sm text-slate-500 dark:text-slate-400">Kas Aktual</span>
                                    <span className="font-semibold text-slate-900 dark:text-white">
                                        {cashierShift.actual_cash === null ? "-" : formatCurrency(cashierShift.actual_cash)}
                                    </span>
                                </div>
                                <div className="flex items-center justify-between rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-800/60">
                                    <span className="text-sm text-slate-500 dark:text-slate-400">Selisih</span>
                                    <span className="font-semibold text-slate-900 dark:text-white">
                                        {cashierShift.cash_difference === null ? "-" : formatCurrency(cashierShift.cash_difference)}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {canCloseShift && (
                            <div className="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                                <h2 className="text-lg font-semibold text-slate-900 dark:text-white">
                                    Tutup Shift
                                </h2>
                                <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    Input kas fisik akhir untuk finalisasi cash closing.
                                </p>
                                <form onSubmit={handleCloseShift} className="mt-4 space-y-4">
                                    <div>
                                        <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Kas Fisik Aktual</label>
                                        <input
                                            type="number"
                                            min="0"
                                            value={actualCash}
                                            onChange={(event) => setActualCash(event.target.value)}
                                            className="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                        />
                                        {errors?.actual_cash && (
                                            <p className="mt-2 text-xs text-rose-500">{errors.actual_cash}</p>
                                        )}
                                    </div>
                                    <div>
                                        <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Catatan Closing</label>
                                        <textarea
                                            rows={3}
                                            value={closeNotes}
                                            onChange={(event) => setCloseNotes(event.target.value)}
                                            className="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                            placeholder="Opsional"
                                        />
                                    </div>
                                    <div className="rounded-xl border border-amber-200 bg-amber-50/60 p-4 dark:border-amber-900/40 dark:bg-amber-950/10">
                                        <div>
                                            <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Pengeluaran</label>
                                            <input
                                                type="number"
                                                min="0"
                                                value={expenseTotal}
                                                onChange={(event) => setExpenseTotal(event.target.value)}
                                                className="h-11 w-full rounded-xl border border-slate-200 bg-white px-4 text-sm text-slate-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                                placeholder="0"
                                            />
                                            {errors?.expense_total && (
                                                <p className="mt-2 text-xs text-rose-500">{errors.expense_total}</p>
                                            )}
                                        </div>
                                        <div className="mt-3">
                                            <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Catatan Pengeluaran</label>
                                            <textarea
                                                rows={3}
                                                value={expenseNotes}
                                                onChange={(event) => setExpenseNotes(event.target.value)}
                                                className="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                                placeholder="mis. selisih kembalian qris, atau beli galon"
                                            />
                                            {errors?.expense_notes && (
                                                <p className="mt-2 text-xs text-rose-500">{errors.expense_notes}</p>
                                            )}
                                        </div>
                                        <div className="mt-3">
                                            <p className="mb-2 text-xs font-medium text-slate-500 dark:text-slate-400">Cepat isi:</p>
                                            <ExpensePresetChips onPick={(label) => setExpenseNotes(appendPreset(expenseNotes, label))} />
                                        </div>
                                    </div>
                                    {difference !== null && (
                                        <div
                                            className={`rounded-xl px-4 py-3 text-sm ${
                                                difference === 0
                                                    ? "bg-emerald-50 text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300"
                                                    : "bg-amber-50 text-amber-700 dark:bg-amber-950/30 dark:text-amber-300"
                                            }`}
                                        >
                                            Selisih closing: {formatCurrency(difference)}
                                        </div>
                                    )}
                                    <button
                                        type="submit"
                                        className="inline-flex items-center gap-2 rounded-xl bg-primary-500 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-primary-600"
                                    >
                                        <IconCashBanknote size={18} />
                                        <span>Finalisasi Closing</span>
                                    </button>
                                </form>
                            </div>
                        )}

                        {canEditExpenses && (
                            <div className="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                                <h2 className="text-lg font-semibold text-slate-900 dark:text-white">
                                    Edit Pengeluaran & Note
                                </h2>
                                <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                    Pengeluaran bisa diubah kapan saja setelah shift ditutup (mis. beli galon di hari berikutnya).
                                </p>
                                <div className="mt-4 space-y-4">
                                    <div>
                                        <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Pengeluaran</label>
                                        <input
                                            type="number"
                                            min="0"
                                            value={expenseTotal}
                                            onChange={(event) => setExpenseTotal(event.target.value)}
                                            className="h-11 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 text-sm text-slate-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                            placeholder="0"
                                        />
                                    </div>
                                    <div>
                                        <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Catatan Pengeluaran</label>
                                        <textarea
                                            rows={3}
                                            value={expenseNotes}
                                            onChange={(event) => setExpenseNotes(event.target.value)}
                                            className="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none transition focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200"
                                            placeholder="mis. selisih kembalian qris, atau beli galon"
                                        />
                                        <div className="mt-3">
                                            <p className="mb-2 text-xs font-medium text-slate-500 dark:text-slate-400">Cepat isi:</p>
                                            <ExpensePresetChips onPick={(label) => setExpenseNotes(appendPreset(expenseNotes, label))} />
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={saveExpenses}
                                        disabled={savingExpenses}
                                        className="inline-flex items-center gap-2 rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-medium text-white transition-colors hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-60"
                                    >
                                        <IconCashBanknote size={18} />
                                        <span>{savingExpenses ? "Menyimpan..." : "Simpan Pengeluaran"}</span>
                                    </button>
                                </div>
                            </div>
                        )}

                        {cashierShift.status !== "open" && canSendReportEmail && (
                            <div className="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
                                <h2 className="flex items-center gap-2 text-lg font-semibold text-slate-900 dark:text-white">
                                    <IconMail size={20} />
                                    Laporan Email Tutup Kasir
                                </h2>
                                {cashierShift.report_email_sent_at ? (
                                    <div className="mt-3 rounded-xl bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-950/30 dark:text-emerald-300">
                                        <p className="font-medium">Terkirim {formatDateTime(cashierShift.report_email_sent_at)}</p>
                                        <p className="mt-1 text-xs text-emerald-600 dark:text-emerald-400">
                                            oleh {cashierShift.report_email_sent_by?.name || "-"} ke{" "}
                                            {cashierShift.report_email_sent_to || reportRecipients.join(", ")}
                                        </p>
                                    </div>
                                ) : (
                                    <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                        Belum dikirim. Kirim sekarang atau kirim manual kapan saja, misalnya di lain hari.
                                    </p>
                                )}
                                <p className="mt-3 text-xs text-slate-400 dark:text-slate-500">
                                    Penerima: {reportRecipients.join(", ")}
                                </p>
                                <div className="mt-4 flex flex-wrap gap-3">
                                    <button
                                        type="button"
                                        onClick={() => setShowPreview(true)}
                                        className="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition-colors hover:bg-slate-50 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                                    >
                                        <IconEye size={18} />
                                        <span>Lihat Preview</span>
                                    </button>
                                    <button
                                        type="button"
                                        onClick={sendReportEmail}
                                        disabled={sendingEmail}
                                        className="inline-flex items-center gap-2 rounded-xl border border-primary-500 px-4 py-2.5 text-sm font-medium text-primary-600 transition-colors hover:bg-primary-50 disabled:cursor-not-allowed disabled:opacity-60 dark:text-primary-400 dark:hover:bg-primary-950/30"
                                    >
                                        <IconMailForward size={18} />
                                        <span>
                                            {sendingEmail
                                                ? "Mengirim..."
                                                : cashierShift.report_email_sent_at
                                                  ? "Kirim Ulang"
                                                  : "Kirim Email Sekarang"}
                                        </span>
                                    </button>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

Show.layout = (page) => <DashboardLayout children={page} />;
