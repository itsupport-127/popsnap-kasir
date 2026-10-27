import React, { useEffect, useState } from "react";
import { Head, router, useForm } from "@inertiajs/react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import Table from "@/Components/Dashboard/Table";
import Pagination from "@/Components/Dashboard/Pagination";
import Swal from "sweetalert2";
import toast from "react-hot-toast";
import { IconBrandWhatsapp, IconCamera, IconChevronDown, IconCopy, IconExternalLink, IconKey, IconPencil, IconPlus, IconSearch, IconTrash, IconX } from "@tabler/icons-react";

const rupiah = (v = 0) =>
    new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(v);
const dt = (v) =>
    v ? new Intl.DateTimeFormat("id-ID", { dateStyle: "medium", timeStyle: "short", timeZone: "Asia/Jakarta" }).format(new Date(v)) : "-";

const copyText = async (text, okMsg = "Link disalin") => {
    try {
        await navigator.clipboard.writeText(text);
    } catch {
        const ta = document.createElement("textarea");
        ta.value = text; ta.style.position = "fixed"; ta.style.opacity = "0";
        document.body.appendChild(ta); ta.select();
        try { document.execCommand("copy"); } finally { document.body.removeChild(ta); }
    }
    toast.success(okMsg);
};
const waLink = (url) => `https://wa.me/?text=${encodeURIComponent(`Halo! Ini foto & video kamu dari Popsnap Photobox 📸\n${url}`)}`;

const PERIODS = [
    ["today", "Hari ini"],
    ["week", "Minggu ini"],
    ["month", "Bulan ini"],
    ["year", "Tahun ini"],
    ["all", "Semua"],
    ["custom", "Pilih tanggal"],
];
const STATUS_STYLE = {
    paid: "bg-emerald-100 text-emerald-700",
    pending: "bg-amber-100 text-amber-700",
    failed: "bg-rose-100 text-rose-700",
    expired: "bg-slate-200 text-slate-600",
    refunded: "bg-violet-100 text-violet-700",
};
const STATUS_LABEL = { paid: "Lunas", pending: "Menunggu", failed: "Gagal", expired: "Kedaluwarsa", refunded: "Refund" };
const METHOD_LABEL = { qris: "QRIS", voucher: "Voucher", free: "Gratis" };
const field = "h-10 rounded-xl border border-slate-200 bg-slate-50 px-3 text-sm text-slate-800 outline-none focus:border-primary-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200";

function Stat({ label, value, sub }) {
    return (
        <div className="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
            <p className="text-xs text-slate-500 dark:text-slate-400">{label}</p>
            <p className="mt-1 text-xl font-bold text-slate-900 dark:text-white">{value}</p>
            {sub && <p className="text-xs text-slate-500 dark:text-slate-400">{sub}</p>}
        </div>
    );
}

export default function Index({ filters, range, transactions, summary, studios, canManage, canEdit, canDelete, apiUrl, newToken, newTokenStudio }) {
    const [showStudios, setShowStudios] = useState(!!newToken || studios.length === 0);
    const form = useForm({ name: "" });
    const [editing, setEditing] = useState(null);
    const editForm = useForm({ template_name: "", method: "qris", amount: 0, status: "paid", paid_at: "" });
    const showActions = canEdit || canDelete;
    const [searchText, setSearchText] = useState(filters.search || "");
    useEffect(() => { setSearchText(filters.search || ""); }, [filters.search]); // ikut berubah kalau halaman dibuka dengan pencarian dari link lain
    const hasFilters = !!(filters.studio_id || filters.method || filters.status || filters.search);
    const resetFilters = () => { setSearchText(""); go({ studio_id: "", method: "", status: "", search: "" }); };

    const renameStudio = (s) => {
        Swal.fire({
            title: "Ubah nama studio",
            input: "text",
            inputValue: s.name,
            inputAttributes: { maxlength: 100 },
            showCancelButton: true,
            confirmButtonText: "Simpan",
            cancelButtonText: "Batal",
            inputValidator: (v) => (!v || !v.trim() ? "Nama studio wajib diisi" : undefined),
        }).then((r) => {
            if (r.isConfirmed && r.value.trim() !== s.name) {
                router.put(route("photobox-studios.update", s.id), { name: r.value.trim() }, { preserveScroll: true, onSuccess: () => toast.success("Nama studio diperbarui") });
            }
        });
    };
    const deleteStudio = (s) => {
        const hasTx = s.tx_count > 0;
        Swal.fire({
            title: `Hapus studio ${s.name}?`,
            html: hasTx
                ? `Studio ini punya <b>${s.tx_count} transaksi</b> (pendapatan lunas ${rupiah(s.tx_revenue)}).<br/>Menghapus studio akan <b>menghapus semua transaksinya</b> dan pendapatannya berkurang. Kalau hanya ingin menghentikan, pilih <b>Nonaktifkan</b>.`
                : "Studio ini belum punya transaksi. Token-nya langsung tidak berlaku.",
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ef4444",
            cancelButtonColor: "#64748b",
            confirmButtonText: hasTx ? "Hapus studio & transaksinya" : "Ya, hapus",
            cancelButtonText: "Batal",
        }).then((r) => {
            if (!r.isConfirmed) return;
            router.delete(route("photobox-studios.destroy", s.id), {
                data: { with_transactions: hasTx },
                preserveScroll: true,
                onSuccess: () => toast.success("Studio dihapus"),
                onError: (e) => Swal.fire("Gagal menghapus", Object.values(e)[0] || "Terjadi kesalahan", "error"),
            });
        });
    };

    const openEdit = (t) => {
        editForm.clearErrors();
        editForm.setData({
            template_name: t.template_name || "",
            method: t.method,
            amount: t.amount,
            status: t.status,
            paid_at: t.paid_at_local || "",
        });
        setEditing(t);
    };
    const saveEdit = (e) => {
        e.preventDefault();
        editForm.put(route("photobox-transactions.update", editing.id), { preserveScroll: true, onSuccess: () => setEditing(null) });
    };
    const removeTx = (t) => {
        Swal.fire({
            title: "Hapus transaksi?",
            html: `<b>${t.order_no}</b><br/>${t.status === "paid" && t.amount > 0 ? `Pendapatan photobox akan berkurang ${rupiah(t.amount)}.` : "Transaksi ini tidak akan dihitung lagi."}`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ef4444",
            cancelButtonColor: "#64748b",
            confirmButtonText: "Ya, hapus",
            cancelButtonText: "Batal",
        }).then((r) => {
            if (r.isConfirmed) router.delete(route("photobox-transactions.destroy", t.id), { preserveScroll: true });
        });
    };
    const go = (next) => router.get(route("photobox-transactions.index"), { ...filters, ...next }, { preserveState: true, replace: true });
    const copy = (text) => navigator.clipboard?.writeText(text);

    return (
        <>
            <Head title="Transaksi Photobox" />

            <div className="mb-5 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 className="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-white">
                        <IconCamera size={28} className="text-primary-500" /> Transaksi Photobox
                    </h1>
                    <p className="text-sm text-slate-500 dark:text-slate-400">
                        Riwayat transaksi dari app Photobox tiap studio. Nomor transaksinya sama persis dengan di app Photobox.
                    </p>
                </div>
                <button type="button" onClick={() => setShowStudios((v) => !v)} className="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                    <IconKey size={16} /> Studio & token
                </button>
            </div>

            {showStudios && (
                <div className="mb-5 rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                    {newToken && (
                        <div className="mb-4 rounded-xl border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900">
                            <p className="font-semibold">Token untuk studio “{newTokenStudio}” (hanya tampil sekarang, salin dan simpan):</p>
                            <div className="mt-2 flex flex-wrap items-center gap-2">
                                <code className="break-all rounded bg-white px-2 py-1 text-xs">{newToken}</code>
                                <button type="button" onClick={() => copy(newToken)} className="inline-flex items-center gap-1 rounded-lg border border-amber-400 px-2 py-1 text-xs"><IconCopy size={14} /> Salin</button>
                            </div>
                            <p className="mt-2 text-xs">Isi di app Photobox: Pengaturan &gt; Cloud. URL kasir: <code>{apiUrl}</code></p>
                        </div>
                    )}
                    <div className="space-y-2">
                        {studios.length === 0 && <p className="text-sm text-slate-500">Belum ada studio. Tambahkan studio pertama di bawah.</p>}
                        {studios.map((s) => (
                            <div key={s.id} className="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-100 px-3 py-2 dark:border-slate-800">
                                <div>
                                    <p className="font-medium text-slate-800 dark:text-slate-200">{s.name} {!s.is_active && <span className="ml-1 rounded-full bg-slate-200 px-2 py-0.5 text-xs">nonaktif</span>}</p>
                                    <p className="text-xs text-slate-500">Token …{s.token_hint || "----"} · terakhir terhubung: {dt(s.last_seen_at)}</p>
                                </div>
                                {canManage && (
                                    <div className="flex gap-2">
                                        <button type="button" onClick={() => confirm(`Buat token baru untuk ${s.name}? Token lama langsung tidak berlaku.`) && router.post(route("photobox-studios.token", s.id))} className="rounded-lg border border-slate-200 px-3 py-1.5 text-xs dark:border-slate-700">Token baru</button>
                                        <button type="button" onClick={() => router.post(route("photobox-studios.toggle", s.id))} className="rounded-lg border border-slate-200 px-3 py-1.5 text-xs dark:border-slate-700">{s.is_active ? "Nonaktifkan" : "Aktifkan"}</button>
                                        <button type="button" onClick={() => renameStudio(s)} className="inline-flex items-center gap-1 rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs text-amber-700 hover:bg-amber-100 dark:border-amber-800 dark:bg-amber-900/30 dark:text-amber-400"><IconPencil size={14} /> Ubah nama</button>
                                        <button type="button" onClick={() => deleteStudio(s)} className="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs text-rose-700 hover:bg-rose-100 dark:border-rose-800 dark:bg-rose-900/30 dark:text-rose-400"><IconTrash size={14} /> Hapus</button>
                                    </div>
                                )}
                            </div>
                        ))}
                    </div>
                    {canManage && (
                        <form onSubmit={(e) => { e.preventDefault(); form.post(route("photobox-studios.store"), { onSuccess: () => form.reset() }); }} className="mt-3 flex flex-wrap gap-2">
                            <input value={form.data.name} onChange={(e) => form.setData("name", e.target.value)} placeholder="Nama studio, mis. Studio Emma" className={`${field} w-64`} />
                            <button type="submit" disabled={form.processing || !form.data.name.trim()} className="inline-flex items-center gap-1 rounded-xl bg-primary-500 px-4 text-sm font-semibold text-white disabled:opacity-50"><IconPlus size={16} /> Tambah studio</button>
                            {form.errors.name && <span className="self-center text-xs text-rose-600">{form.errors.name}</span>}
                        </form>
                    )}
                </div>
            )}

            <div className="mb-4 space-y-4 rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                {/* Periode */}
                <div className="flex flex-wrap items-end justify-between gap-3">
                    <div className="min-w-0 max-w-full">
                        <span className="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Periode</span>
                        <div className="flex max-w-full gap-1 overflow-x-auto rounded-xl bg-slate-100 p-1 dark:bg-slate-800">
                            {PERIODS.map(([id, label]) => (
                                <button key={id} type="button" onClick={() => go({ period: id })}
                                    className={`whitespace-nowrap rounded-lg px-3 py-1.5 text-sm font-medium transition-colors ${filters.period === id ? "bg-primary-500 text-white shadow-sm" : "text-slate-600 hover:bg-white dark:text-slate-300 dark:hover:bg-slate-700"}`}>
                                    {label}
                                </button>
                            ))}
                        </div>
                    </div>
                    {filters.period === "custom" && (
                        <div className="grid w-full grid-cols-2 gap-3 sm:w-auto">
                            <label className="block">
                                <span className="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Dari tanggal</span>
                                <input type="date" value={filters.start_date || ""} onChange={(e) => go({ start_date: e.target.value })} className={`${field} w-full`} />
                            </label>
                            <label className="block">
                                <span className="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Sampai tanggal</span>
                                <input type="date" value={filters.end_date || ""} onChange={(e) => go({ end_date: e.target.value })} className={`${field} w-full`} />
                            </label>
                        </div>
                    )}
                </div>

                {/* Filter */}
                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    {[
                        ["Studio", "studio_id", "Semua studio", studios.map((s) => [s.id, s.name])],
                        ["Metode bayar", "method", "Semua metode", Object.entries(METHOD_LABEL)],
                        ["Status", "status", "Semua status", Object.entries(STATUS_LABEL)],
                    ].map(([label, key, allLabel, options]) => (
                        <label key={key} className="block">
                            <span className="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">{label}</span>
                            <div className="relative">
                                <select value={filters[key] || ""} onChange={(e) => go({ [key]: e.target.value })}
                                    className={`${field} w-full cursor-pointer appearance-none pr-9 ${filters[key] ? "!border-primary-500 !bg-primary-50 font-medium !text-primary-700 dark:!bg-primary-950/30 dark:!text-primary-300" : ""}`}>
                                    <option value="">{allLabel}</option>
                                    {options.map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                                </select>
                                <IconChevronDown size={16} className="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 text-slate-400" />
                            </div>
                        </label>
                    ))}
                    <form className="block" onSubmit={(e) => { e.preventDefault(); go({ search: searchText.trim() }); }}>
                        <span className="mb-1 block text-xs font-medium text-slate-500 dark:text-slate-400">Cari</span>
                        <div className="relative">
                            <IconSearch size={16} className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                            <input value={searchText} onChange={(e) => setSearchText(e.target.value)} placeholder="Nomor / voucher / template"
                                className={`${field} w-full pl-9 ${searchText ? "pr-16" : "pr-3"}`} />
                            {searchText && (
                                <button type="button" aria-label="Hapus pencarian" onClick={() => { setSearchText(""); if (filters.search) go({ search: "" }); }}
                                    className="absolute right-9 top-1/2 -translate-y-1/2 rounded p-1 text-slate-400 hover:text-slate-600"><IconX size={14} /></button>
                            )}
                            <button type="submit" aria-label="Cari" className="absolute right-1.5 top-1/2 -translate-y-1/2 rounded-lg bg-primary-500 p-1.5 text-white"><IconSearch size={14} /></button>
                        </div>
                    </form>
                </div>

                {hasFilters && (
                    <div className="flex items-center justify-between gap-3 border-t border-slate-100 pt-3 text-xs dark:border-slate-800">
                        <span className="text-slate-500 dark:text-slate-400">Filter aktif — hasil di bawah sudah disaring.</span>
                        <button type="button" onClick={resetFilters} className="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1.5 font-medium text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"><IconX size={14} /> Reset filter</button>
                    </div>
                )}
            </div>
            <p className="mb-3 text-xs text-slate-500">Periode: {range.start} s/d {range.end}</p>

            <div className="mb-5 grid grid-cols-2 gap-3 md:grid-cols-4">
                <Stat label="Pendapatan photobox (lunas)" value={rupiah(summary.revenue)} />
                <Stat label="Sesi terjual" value={summary.sessions.toLocaleString("id-ID")} sub="semua metode, status lunas" />
                {summary.byMethod.map((m) => <Stat key={m.method} label={`Via ${METHOD_LABEL[m.method] || m.method}`} value={rupiah(m.total)} sub={`${m.count} sesi`} />)}
            </div>

            <div className="mb-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
                <div>
                    <Table.Card title="Per studio">
                        <Table>
                            <Table.Thead><tr><Table.Th>Studio</Table.Th><Table.Th className="text-right">Sesi</Table.Th><Table.Th className="text-right">Pendapatan</Table.Th></tr></Table.Thead>
                            <Table.Tbody>
                                {summary.byStudio.length ? summary.byStudio.map((s) => (
                                    <tr key={s.id}><Table.Td>{s.name}</Table.Td><Table.Td className="text-right">{s.sessions}</Table.Td><Table.Td className="text-right">{rupiah(s.revenue)}</Table.Td></tr>
                                )) : <Table.Empty colSpan={3} message="Belum ada transaksi lunas pada periode ini." />}
                            </Table.Tbody>
                        </Table>
                    </Table.Card>
                </div>
                <div>
                    <Table.Card title="Produk terjual (template/paket)">
                        <Table>
                            <Table.Thead><tr><Table.Th>Produk</Table.Th><Table.Th className="text-right">Terjual</Table.Th><Table.Th className="text-right">Pendapatan</Table.Th></tr></Table.Thead>
                            <Table.Tbody>
                                {summary.byTemplate.length ? summary.byTemplate.map((t) => (
                                    <tr key={t.name}><Table.Td>{t.name}</Table.Td><Table.Td className="text-right">{t.qty}</Table.Td><Table.Td className="text-right">{rupiah(t.revenue)}</Table.Td></tr>
                                )) : <Table.Empty colSpan={3} message="Belum ada data." />}
                            </Table.Tbody>
                        </Table>
                    </Table.Card>
                </div>
            </div>

            <Table.Card title="Riwayat transaksi">
                <Table>
                    <Table.Thead>
                        <tr>
                            <Table.Th>No. Transaksi</Table.Th><Table.Th>Waktu</Table.Th><Table.Th>Studio</Table.Th><Table.Th>Produk</Table.Th>
                            <Table.Th>Metode</Table.Th><Table.Th className="text-right">Jumlah</Table.Th><Table.Th>Status</Table.Th><Table.Th>Galeri</Table.Th>{showActions && <Table.Th className="text-right">Aksi</Table.Th>}
                        </tr>
                    </Table.Thead>
                    <Table.Tbody>
                        {transactions.data.length ? transactions.data.map((t) => (
                            <tr key={t.id} className="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <Table.Td><span className="font-mono text-xs font-semibold">{t.order_no}</span>{t.edited && <span className="ml-2 rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-semibold text-sky-700">diedit</span>}</Table.Td>
                                <Table.Td>{dt(t.paid_at || t.booth_created_at)}</Table.Td>
                                <Table.Td>{t.studio}</Table.Td>
                                <Table.Td>{t.template_name || "-"}{t.category ? <span className="block text-xs text-slate-500">{t.category}</span> : null}</Table.Td>
                                <Table.Td>{METHOD_LABEL[t.method] || t.method}{t.voucher_code ? <span className="block font-mono text-xs text-slate-500">{t.voucher_code}</span> : null}</Table.Td>
                                <Table.Td className="text-right">{rupiah(t.amount)}</Table.Td>
                                <Table.Td><span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${STATUS_STYLE[t.status] || ""}`}>{STATUS_LABEL[t.status] || t.status}</span></Table.Td>
                                <Table.Td>
                                    {t.gallery_url ? (
                                        <div className="flex gap-1.5">
                                            <a href={t.gallery_url} target="_blank" rel="noopener noreferrer" title="Buka galeri" className="rounded-lg border border-sky-200 bg-sky-100 p-2 text-sky-600 hover:bg-sky-200 dark:border-sky-800 dark:bg-sky-900/40 dark:text-sky-400"><IconExternalLink size={16} /></a>
                                            <button type="button" onClick={() => copyText(t.gallery_url)} title="Salin link" className="rounded-lg border border-slate-200 bg-slate-100 p-2 text-slate-600 hover:bg-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"><IconCopy size={16} /></button>
                                            <a href={waLink(t.gallery_url)} target="_blank" rel="noopener noreferrer" title="Kirim lewat WhatsApp" className="rounded-lg border border-emerald-200 bg-emerald-100 p-2 text-emerald-600 hover:bg-emerald-200 dark:border-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-400"><IconBrandWhatsapp size={16} /></a>
                                        </div>
                                    ) : <span className="text-xs text-slate-400">-</span>}
                                </Table.Td>
                                {showActions && (
                                    <Table.Td>
                                        <div className="flex justify-end gap-2">
                                            {canEdit && <button type="button" onClick={() => openEdit(t)} title="Edit" className="rounded-lg border border-amber-200 bg-amber-100 p-2 text-amber-600 hover:bg-amber-200 dark:border-amber-800 dark:bg-amber-900/40 dark:text-amber-400"><IconPencil size={16} /></button>}
                                            {canDelete && <button type="button" onClick={() => removeTx(t)} title="Hapus" className="rounded-lg border border-rose-200 bg-rose-100 p-2 text-rose-600 hover:bg-rose-200 dark:border-rose-800 dark:bg-rose-900/40 dark:text-rose-400"><IconTrash size={16} /></button>}
                                        </div>
                                    </Table.Td>
                                )}
                            </tr>
                        )) : <Table.Empty colSpan={showActions ? 9 : 8} message="Belum ada transaksi pada filter ini." />}
                    </Table.Tbody>
                </Table>
            </Table.Card>
            {transactions.last_page > 1 && <Pagination links={transactions.links} />}

            {editing && (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4" onClick={() => setEditing(null)}>
                    <form onSubmit={saveEdit} onClick={(e) => e.stopPropagation()} className="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl dark:bg-slate-900">
                        <div className="mb-4 flex items-start justify-between gap-3">
                            <div>
                                <h2 className="text-lg font-bold text-slate-900 dark:text-white">Edit transaksi</h2>
                                <p className="font-mono text-xs text-slate-500">{editing.order_no} · {editing.studio}</p>
                            </div>
                            <button type="button" onClick={() => setEditing(null)} className="rounded-lg p-1 text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"><IconX size={18} /></button>
                        </div>
                        <div className="space-y-3">
                            <label className="block text-sm">
                                <span className="mb-1 block text-slate-600 dark:text-slate-300">Produk (template/paket)</span>
                                <input value={editForm.data.template_name} onChange={(e) => editForm.setData("template_name", e.target.value)} className={`${field} w-full`} />
                                {editForm.errors.template_name && <span className="text-xs text-rose-600">{editForm.errors.template_name}</span>}
                            </label>
                            <div className="grid grid-cols-2 gap-3">
                                <label className="block text-sm">
                                    <span className="mb-1 block text-slate-600 dark:text-slate-300">Metode</span>
                                    <select value={editForm.data.method} onChange={(e) => editForm.setData("method", e.target.value)} className={`${field} w-full`}>
                                        {Object.entries(METHOD_LABEL).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                                    </select>
                                </label>
                                <label className="block text-sm">
                                    <span className="mb-1 block text-slate-600 dark:text-slate-300">Status</span>
                                    <select value={editForm.data.status} onChange={(e) => editForm.setData("status", e.target.value)} className={`${field} w-full`}>
                                        {Object.entries(STATUS_LABEL).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                                    </select>
                                </label>
                            </div>
                            <label className="block text-sm">
                                <span className="mb-1 block text-slate-600 dark:text-slate-300">Jumlah (Rp)</span>
                                <input type="number" min="0" step="1" value={editForm.data.amount} onChange={(e) => editForm.setData("amount", e.target.value)} className={`${field} w-full`} />
                                {editForm.errors.amount && <span className="text-xs text-rose-600">{editForm.errors.amount}</span>}
                            </label>
                            <label className="block text-sm">
                                <span className="mb-1 block text-slate-600 dark:text-slate-300">Waktu bayar</span>
                                <input type="datetime-local" value={editForm.data.paid_at} onChange={(e) => editForm.setData("paid_at", e.target.value)} className={`${field} w-full`} />
                            </label>
                            <p className="text-xs text-slate-500">Pendapatan hanya dihitung untuk status Lunas. Transaksi yang sudah diedit tidak akan ditimpa lagi oleh kiriman ulang dari app Photobox.</p>
                        </div>
                        <div className="mt-5 flex justify-end gap-2">
                            <button type="button" onClick={() => setEditing(null)} className="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">Batal</button>
                            <button type="submit" disabled={editForm.processing} className="rounded-xl bg-primary-500 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">{editForm.processing ? "Menyimpan..." : "Simpan"}</button>
                        </div>
                    </form>
                </div>
            )}
        </>
    );
}

Index.layout = (page) => <DashboardLayout children={page} />;
