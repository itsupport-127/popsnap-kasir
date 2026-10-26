import React, { useState } from "react";
import { Head, router, useForm } from "@inertiajs/react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import Table from "@/Components/Dashboard/Table";
import Pagination from "@/Components/Dashboard/Pagination";
import { IconCamera, IconCopy, IconKey, IconPlus } from "@tabler/icons-react";

const rupiah = (v = 0) =>
    new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(v);
const dt = (v) =>
    v ? new Intl.DateTimeFormat("id-ID", { dateStyle: "medium", timeStyle: "short", timeZone: "Asia/Jakarta" }).format(new Date(v)) : "-";

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

export default function Index({ filters, range, transactions, summary, studios, canManage, apiUrl, newToken, newTokenStudio }) {
    const [showStudios, setShowStudios] = useState(!!newToken || studios.length === 0);
    const form = useForm({ name: "" });
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

            <div className="mb-4 flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200 bg-white p-3 dark:border-slate-800 dark:bg-slate-900">
                {PERIODS.map(([id, label]) => (
                    <button key={id} type="button" onClick={() => go({ period: id })} className={`rounded-xl px-3 py-2 text-sm font-medium ${filters.period === id ? "bg-primary-500 text-white" : "bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200"}`}>{label}</button>
                ))}
                {filters.period === "custom" && (
                    <>
                        <input type="date" value={filters.start_date || ""} onChange={(e) => go({ start_date: e.target.value })} className={field} />
                        <input type="date" value={filters.end_date || ""} onChange={(e) => go({ end_date: e.target.value })} className={field} />
                    </>
                )}
                <select value={filters.studio_id || ""} onChange={(e) => go({ studio_id: e.target.value })} className={field}>
                    <option value="">Semua studio</option>
                    {studios.map((s) => <option key={s.id} value={s.id}>{s.name}</option>)}
                </select>
                <select value={filters.method || ""} onChange={(e) => go({ method: e.target.value })} className={field}>
                    <option value="">Semua metode</option>
                    {Object.entries(METHOD_LABEL).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                </select>
                <select value={filters.status || ""} onChange={(e) => go({ status: e.target.value })} className={field}>
                    <option value="">Semua status</option>
                    {Object.entries(STATUS_LABEL).map(([k, v]) => <option key={k} value={k}>{v}</option>)}
                </select>
                <input defaultValue={filters.search || ""} onKeyDown={(e) => e.key === "Enter" && go({ search: e.target.value })} placeholder="Cari nomor / voucher / template (Enter)" className={`${field} min-w-[16rem] flex-1`} />
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
                            <Table.Th>Metode</Table.Th><Table.Th className="text-right">Jumlah</Table.Th><Table.Th>Status</Table.Th>
                        </tr>
                    </Table.Thead>
                    <Table.Tbody>
                        {transactions.data.length ? transactions.data.map((t) => (
                            <tr key={t.id} className="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <Table.Td><span className="font-mono text-xs font-semibold">{t.order_no}</span></Table.Td>
                                <Table.Td>{dt(t.paid_at || t.booth_created_at)}</Table.Td>
                                <Table.Td>{t.studio}</Table.Td>
                                <Table.Td>{t.template_name || "-"}{t.category ? <span className="block text-xs text-slate-500">{t.category}</span> : null}</Table.Td>
                                <Table.Td>{METHOD_LABEL[t.method] || t.method}{t.voucher_code ? <span className="block font-mono text-xs text-slate-500">{t.voucher_code}</span> : null}</Table.Td>
                                <Table.Td className="text-right">{rupiah(t.amount)}</Table.Td>
                                <Table.Td><span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${STATUS_STYLE[t.status] || ""}`}>{STATUS_LABEL[t.status] || t.status}</span></Table.Td>
                            </tr>
                        )) : <Table.Empty colSpan={7} message="Belum ada transaksi pada filter ini." />}
                    </Table.Tbody>
                </Table>
            </Table.Card>
            {transactions.last_page > 1 && <Pagination links={transactions.links} />}
        </>
    );
}

Index.layout = (page) => <DashboardLayout children={page} />;
