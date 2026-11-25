import React, { useEffect, useState } from "react";
import { Head, router, useForm } from "@inertiajs/react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import useLiveRefresh from "@/Hooks/useLiveRefresh";
import Table from "@/Components/Dashboard/Table";
import Pagination from "@/Components/Dashboard/Pagination";
import AddCustomerModal from "@/Components/POS/AddCustomerModal";
import Swal from "sweetalert2";
import toast from "react-hot-toast";
import { IconBrandWhatsapp, IconCamera, IconCheck, IconChevronDown, IconCopy, IconExternalLink, IconFlask, IconKey, IconPencil, IconPlus, IconSearch, IconTrash, IconUser, IconUserPlus, IconX } from "@tabler/icons-react";

const rupiah = (v = 0) =>
    new Intl.NumberFormat("id-ID", { style: "currency", currency: "IDR", minimumFractionDigits: 0 }).format(v);
// Status booth dari laporan heartbeat: titik warna + label singkat; catatan rinci tampil sebagai baris kecil di bawahnya.
const BOOTH_STATE = {
    ok: ["bg-emerald-500", "text-emerald-700", "Lancar"],
    queue: ["bg-sky-500", "text-sky-700", "Ada antrean"],
    warn: ["bg-amber-500", "text-amber-700", "Perlu dicek"],
    bad: ["bg-rose-500", "text-rose-700", "Bermasalah"],
    offline: ["bg-slate-400", "text-slate-500", "Offline"],
    unknown: ["bg-slate-300", "text-slate-500", "Belum ada laporan"],
};
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
// Sapaan WhatsApp otomatis. Nomor pelanggan diambil dari transaksi kasir (kalau ada), kalau tidak ada CS pilih sendiri kontaknya.
const waLink = (t, c = null) => {
    const name = c ? c.name : t.customer_name, wa = c ? c.wa : t.customer_wa;
    const hai = name ? `Hii ${name}!` : "Hii kak!";
    // Tanpa emoji / stiker: karakter emoji sering rusak ("?") di tautan wa.me pada sebagian perangkat
    const until = t.gallery_expires_at ? new Intl.DateTimeFormat("id-ID", { dateStyle: "long", timeStyle: "short", timeZone: "Asia/Jakarta" }).format(new Date(t.gallery_expires_at)) : null;
    const note = until ? `\n\nLink ini aktif sampai ${until} WIB, jadi jangan lupa diunduh sebelum itu ya.` : "";
    const text = `${hai} Terima kasih sudah seru-seruan bareng Popsnap!\nIni soft file foto kamu, yuk langsung dilihat dan diunduh:\n${t.gallery_url}${note}\n\nSemoga hasilnya bikin senyum-senyum sendiri. Kami tunggu kamu lagi di Popsnap, siap bergaya lebih seru!`;
    return `https://wa.me/${wa || ""}?text=${encodeURIComponent(text)}`;
};
const fmtCode = (c) => (/^[A-Z0-9]{10}$/.test(c || "") ? `${c.slice(0, 4)} ${c.slice(4, 8)} ${c.slice(8)}` : c);
const GALLERY_BADGE = {
    uploaded: ["Terupload", "bg-emerald-100 text-emerald-700"],
    pending: ["Belum terupload", "bg-amber-100 text-amber-700"],
    partial: ["Sebagian terupload", "bg-orange-100 text-orange-700"],
    expired: ["Kedaluwarsa", "bg-slate-200 text-slate-600"],
    unknown: ["Status tidak diketahui", "bg-slate-100 text-slate-500"],
};
const SOCIAL_BADGE = {
    3: ["Sosmed + Mading", "bg-emerald-100 text-emerald-700"],
    1: ["Sosmed", "bg-emerald-100 text-emerald-700"],
    2: ["Mading", "bg-emerald-100 text-emerald-700"],
    0: ["Tidak boleh", "bg-rose-100 text-rose-700"],
};

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

// Kirim link galeri lewat WhatsApp: isi manual nama + nomor, atau cari dari data pelanggan kasir. Izin foto tamu tidak berpengaruh ke sini.
function WaContactModal({ t, onClose }) {
    const [name, setName] = useState(t.customer_name || "");
    const [wa, setWa] = useState(t.customer_wa ? `+${t.customer_wa}` : "");
    const [q, setQ] = useState("");
    const [found, setFound] = useState([]);
    const [open, setOpen] = useState(false);
    const [picked, setPicked] = useState(null);
    const [adding, setAdding] = useState(false);
    const [err, setErr] = useState("");
    useEffect(() => {
        if (!open) return undefined;
        const ctl = new AbortController();
        const id = setTimeout(() => {
            fetch(route("photobox-customers.search", q.trim() ? { q: q.trim() } : {}), { headers: { Accept: "application/json" }, signal: ctl.signal })
                .then((r) => { if (!r.ok) throw new Error(`HTTP ${r.status}`); setErr(""); return r.json(); }).then(setFound)
                .catch((e) => { if (e.name !== "AbortError") { setFound([]); setErr("Pencarian pelanggan gagal (" + e.message + "). Coba ketik ulang."); } });
        }, 250);
        return () => { clearTimeout(id); ctl.abort(); };
    }, [q, open]);
    const digits = wa.replace(/\D+/g, "");
    const valid = digits.length >= 9 && digits.length <= 15;
    const norm = (d = digits) => (d.startsWith("62") ? d : d.startsWith("0") ? `62${d.slice(1)}` : `62${d}`);
    const pick = (c) => { setPicked(c); setName(c.name || ""); setWa(`+${c.wa}`); setQ(""); setOpen(false); setErr(""); };
    const send = (save) => {
        if (!valid) { setErr("Nomor WhatsApp belum benar."); return; }
        const c = { name: name.trim(), wa: norm() };
        window.open(waLink(t, c), "_blank", "noopener,noreferrer"); // dibuka langsung di klik supaya tidak diblokir browser
        if (save) router.put(route("photobox-transactions.contact", t.id), { name: c.name, wa: c.wa }, { preserveScroll: true, preserveState: true, onSuccess: () => toast.success("Kontak tersimpan") });
        onClose();
    };
    const clear = () => router.put(route("photobox-transactions.contact", t.id), { name: "", wa: "" }, { preserveScroll: true, preserveState: true, onSuccess: () => { toast.success("Kontak manual dihapus"); onClose(); } });
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4" onClick={onClose}>
            <div className="w-full max-w-md rounded-2xl bg-white p-5 shadow-xl dark:bg-slate-900" onClick={(e) => e.stopPropagation()}>
                <div className="mb-3 flex items-center justify-between">
                    <h3 className="font-bold text-slate-900 dark:text-white">Kirim link via WhatsApp</h3>
                    <button type="button" onClick={onClose} className="text-slate-400 hover:text-slate-600"><IconX size={18} /></button>
                </div>
                <div className="relative text-sm">
                    <span className="mb-1 block text-slate-600 dark:text-slate-300">Pelanggan (opsional)</span>
                    <div className="flex items-center gap-2">
                        <button type="button" onClick={() => setOpen((o) => !o)} className={`flex h-12 flex-1 items-center gap-3 rounded-xl border-2 bg-white px-3 text-left dark:bg-slate-900 ${open ? "border-primary-500" : "border-slate-200 dark:border-slate-700"}`}>
                            <span className="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-lg bg-slate-100 dark:bg-slate-800"><IconUser size={18} className="text-slate-400" /></span>
                            <span className="min-w-0 flex-1">
                                {picked ? (<><span className="block truncate text-sm font-medium text-slate-800 dark:text-slate-200">{picked.name}</span><span className="block truncate text-xs text-slate-500">+{picked.wa}</span></>)
                                    : <span className="text-sm text-slate-400">Pilih pelanggan…</span>}
                            </span>
                            <IconChevronDown size={18} className={`text-slate-400 transition-transform ${open ? "rotate-180" : ""}`} />
                        </button>
                        <button type="button" onClick={() => setAdding(true)} title="Tambah pelanggan baru" className="flex h-12 w-12 items-center justify-center rounded-xl border-2 border-dashed border-primary-300 text-primary-500 hover:bg-primary-50"><IconUserPlus size={20} /></button>
                    </div>
                    {open && (
                        <div className="absolute left-0 right-0 top-full z-50 mt-2 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-900">
                            <div className="border-b border-slate-100 p-3 dark:border-slate-800">
                                <div className="relative">
                                    <IconSearch size={18} className="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                                    <input autoFocus value={q} onChange={(e) => setQ(e.target.value)} placeholder="Cari nama/telepon/nomor anggota…" className="h-10 w-full rounded-lg border border-slate-200 bg-slate-50 pl-10 pr-3 text-sm text-slate-800 outline-none focus:border-primary-500 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-200" />
                                </div>
                            </div>
                            <div className="max-h-60 overflow-y-auto">
                                {found.length > 0 ? found.map((c) => (
                                    <button key={c.id} type="button" onClick={() => pick(c)} className={`flex w-full items-center gap-3 px-4 py-2.5 text-left hover:bg-slate-50 dark:hover:bg-slate-800 ${picked?.id === c.id ? "bg-primary-50 dark:bg-primary-950/30" : ""}`}>
                                        <span className="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-slate-100 text-sm font-medium text-slate-500 dark:bg-slate-800">{picked?.id === c.id ? <IconCheck size={16} /> : String(c.name || "?").charAt(0).toUpperCase()}</span>
                                        <span className="min-w-0 flex-1">
                                            <span className="block truncate text-sm font-medium text-slate-800 dark:text-slate-200">{c.name || "(tanpa nama)"}</span>
                                            <span className="block truncate text-xs text-slate-500">{c.no_telp || `+${c.wa}`}</span>
                                            <span className="block truncate text-[11px] text-primary-500">{c.is_loyalty_member ? `${c.loyalty_tier} • ${c.loyalty_points || 0} poin` : "Non-member"}</span>
                                        </span>
                                    </button>
                                )) : (
                                    <div className="py-6 text-center text-slate-400">
                                        <p className="text-sm">Pelanggan tidak ditemukan</p>
                                        <button type="button" onClick={() => { setOpen(false); setAdding(true); }} className="mt-2 text-sm font-medium text-primary-500">+ Tambah pelanggan baru</button>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}
                </div>
                <AddCustomerModal isOpen={adding} onClose={() => setAdding(false)} showMemberRegistration={false}
                    onSuccess={(c) => { setAdding(false); pick({ id: c.id, name: c.name, wa: norm(String(c.no_telp || "").replace(/\D+/g, "")) }); }} />
                <div className="mt-3 grid grid-cols-2 gap-3">
                    <label className="block text-sm"><span className="mb-1 block text-slate-600 dark:text-slate-300">Nama</span><input value={name} onChange={(e) => setName(e.target.value)} placeholder="Nama tamu" className={`${field} w-full`} /></label>
                    <label className="block text-sm"><span className="mb-1 block text-slate-600 dark:text-slate-300">Nomor WhatsApp</span><input value={wa} onChange={(e) => { setWa(e.target.value); setErr(""); }} inputMode="tel" placeholder="08xxxxxxxxxx" className={`${field} w-full`} /></label>
                </div>
                {err && <p className="mt-2 text-xs text-rose-600">{err}</p>}
                <p className="mt-2 text-xs text-slate-500">Boleh dikirim walau tamu tidak memberi izin foto untuk sosmed / mading. Nomor 08… otomatis jadi +62.</p>
                <ul className="mt-2 space-y-0.5 text-xs text-slate-500">
                    <li><b>Pilih kontak di WhatsApp</b>: buka WhatsApp tanpa nomor, penerima dipilih sendiri di aplikasinya.</li>
                    <li><b>Kirim saja</b>: kirim ke nomor di atas, tidak disimpan.</li>
                    <li><b>Simpan &amp; kirim</b>: kirim dan simpan nama + nomor di transaksi ini.</li>
                </ul>
                <div className="mt-4 flex flex-wrap justify-end gap-2">
                    {t.customer_manual && <button type="button" onClick={clear} className="mr-auto rounded-xl px-3 py-2 text-xs text-rose-600 hover:bg-rose-50">Hapus kontak manual</button>}
                    <button type="button" onClick={() => { window.open(waLink(t, { name: "", wa: "" }), "_blank", "noopener,noreferrer"); onClose(); }} title="Buka WhatsApp tanpa nomor: pilih sendiri penerimanya di dalam aplikasi WhatsApp. Pesan + link sudah terisi." className="rounded-xl border border-slate-200 px-3 py-2 text-xs font-medium text-slate-600 dark:border-slate-700 dark:text-slate-300">Pilih kontak di WhatsApp</button>
                    <button type="button" onClick={() => send(false)} title="Kirim ke nomor di atas, tanpa menyimpan nomornya di transaksi ini." className="rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">Kirim saja</button>
                    <button type="button" onClick={() => send(true)} title="Kirim ke nomor di atas, dan simpan nama + nomor itu di transaksi ini supaya lain kali tinggal klik." className="rounded-xl bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700">Simpan &amp; kirim</button>
                </div>
            </div>
        </div>
    );
}

export default function Index({ filters, range, transactions, summary, studios, stores = [], galleryStatusOn, canManage, canEdit, canDelete, testCount = 0, apiUrl, newToken, newTokenStudio }) {
    const [showStudios, setShowStudios] = useState(!!newToken || studios.length === 0);
    const form = useForm({ name: "" });
    const [editing, setEditing] = useState(null);
    const [waFor, setWaFor] = useState(null);
    const editForm = useForm({ template_name: "", method: "qris", amount: 0, status: "paid", paid_at: "" });
    const showActions = canEdit || canDelete;
    const [searchText, setSearchText] = useState(filters.search || "");
    // LIVE (push via websocket/Reverb): begitu ada transaksi masuk / status booth berubah, server mengirim sinyal dan data di sini
    // diperbarui sendiri tanpa polling & tanpa reload halaman. Ditahan sementara saat dialog / edit terbuka supaya isian tidak hilang.
    const { status: liveStatus, enabled: liveOn, setEnabled: setLiveOn, lastAt } = useLiveRefresh({ only: ["transactions", "summary", "studios", "testCount"], pause: !!editing || !!waFor || form.processing || editForm.processing });
    useEffect(() => { setSearchText(filters.search || ""); }, [filters.search]); // ikut berubah kalau halaman dibuka dengan pencarian dari link lain
    const hasFilters = !!(filters.studio_id || filters.method || filters.status || filters.search || filters.test);
    const resetFilters = () => { setSearchText(""); go({ studio_id: "", method: "", status: "", search: "", test: "" }); };

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
    const [newStore, setNewStore] = useState("");
    const addStore = () => {
        if (!newStore.trim()) return;
        router.post(route("photobox-stores.store"), { name: newStore.trim() }, { preserveScroll: true, onSuccess: () => setNewStore("") });
    };
    const renameStore = (st) => {
        Swal.fire({ title: "Ubah nama toko", input: "text", inputValue: st.name, inputAttributes: { maxlength: 100 }, showCancelButton: true, confirmButtonText: "Simpan", cancelButtonText: "Batal",
            inputValidator: (v) => (!v || !v.trim() ? "Nama toko wajib diisi" : undefined),
        }).then((r) => { if (r.isConfirmed && r.value.trim() !== st.name) router.put(route("photobox-stores.update", st.id), { name: r.value.trim() }, { preserveScroll: true }); });
    };
    const deleteStore = (st) => {
        Swal.fire({ title: `Hapus toko ${st.name}?`, text: st.studios_count > 0 ? `${st.studios_count} studio di toko ini menjadi "Tanpa toko". Transaksi tidak berubah.` : "Toko ini belum berisi studio.", icon: "warning", showCancelButton: true, confirmButtonText: "Hapus", cancelButtonText: "Batal" })
            .then((r) => { if (r.isConfirmed) router.delete(route("photobox-stores.destroy", st.id), { preserveScroll: true }); });
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
    // Tandai / lepas tanda TEST. Dipakai kalau gateway masih sandbox padahal uang asli (atau sebaliknya): pendapatan & shift langsung menyesuaikan.
    const toggleTest = (t) => {
        const toTest = !t.is_test;
        Swal.fire({
            title: toTest ? "Tandai sebagai TEST?" : "Tandai sebagai transaksi asli?",
            html: `<b>${t.order_no}</b><br/>${toTest ? "Transaksi ini tidak akan dihitung sebagai pendapatan, shift, dan dashboard." : `Transaksi ini akan dihitung sebagai pendapatan${t.amount > 0 ? ` (${rupiah(t.amount)})` : ""}.`}`,
            icon: "question",
            showCancelButton: true,
            confirmButtonText: toTest ? "Ya, tandai TEST" : "Ya, ini transaksi asli",
            cancelButtonText: "Batal",
        }).then((r) => {
            if (r.isConfirmed) router.put(route("photobox-transactions.test", t.id), { is_test: toTest }, { preserveScroll: true });
        });
    };
    const removeAllTests = () => {
        Swal.fire({
            title: "Hapus transaksi TEST?",
            html: `${testCount} transaksi TEST${filters.studio_id ? " pada studio terpilih" : ""} akan dihapus dari riwayat (maksimal 100 sekali klik). Pendapatan tidak berubah karena memang tidak dihitung.`,
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#ef4444",
            cancelButtonColor: "#64748b",
            confirmButtonText: "Ya, hapus",
            cancelButtonText: "Batal",
        }).then((r) => {
            if (r.isConfirmed) router.delete(route("photobox-transactions.destroy-test"), { data: filters.studio_id ? { studio_id: filters.studio_id } : {}, preserveScroll: true });
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
                <div className="flex flex-wrap items-center gap-2">
                <button type="button" onClick={() => setLiveOn((v) => !v)}
                    title={liveStatus === "on" ? "Tersambung realtime. Klik untuk mematikan." : liveStatus === "off" ? "Realtime (Reverb) belum aktif di server. Muat ulang halaman manual." : liveStatus === "connecting" ? "Menyambung ke server realtime..." : "Realtime dimatikan. Klik untuk menyalakan."}
                    className={`inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-semibold ${liveStatus === "on" ? "border-emerald-300 bg-emerald-50 text-emerald-700" : liveStatus === "connecting" ? "border-amber-300 bg-amber-50 text-amber-700" : "border-slate-200 text-slate-500"}`}>
                    <span className={`h-2 w-2 rounded-full ${liveStatus === "on" ? "animate-pulse bg-emerald-500" : liveStatus === "connecting" ? "animate-pulse bg-amber-500" : "bg-slate-400"}`} />
                    {liveStatus === "on" ? `Live · ${new Intl.DateTimeFormat("id-ID", { timeStyle: "medium", timeZone: "Asia/Jakarta" }).format(lastAt)}` : liveStatus === "connecting" ? "Menyambung…" : liveStatus === "off" ? "Realtime belum aktif" : "Live mati"}
                </button>
                <button type="button" onClick={() => setShowStudios((v) => !v)} className="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800">
                    <IconKey size={16} /> Studio & token
                </button>
                </div>
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
                    <div className="mb-4 rounded-xl border border-slate-100 p-3 dark:border-slate-800" data-testid="store-manager">
                        <p className="mb-2 text-sm font-semibold text-slate-700 dark:text-slate-200">Toko</p>
                        <p className="mb-2 text-xs text-slate-500">Kelompokkan studio per toko (mis. POPSNAP, CCM). Shift kasir bisa memegang satu toko beserta semua studionya.</p>
                        <div className="flex flex-wrap gap-2">
                            {stores.length === 0 && <span className="text-xs text-slate-500">Belum ada toko.</span>}
                            {stores.map((st) => (
                                <span key={st.id} className="inline-flex items-center gap-2 rounded-xl border border-slate-200 px-3 py-1.5 text-sm dark:border-slate-700">
                                    {st.name} <span className="text-xs text-slate-500">({st.studios_count} studio)</span>
                                    {canManage && (<>
                                        <button type="button" onClick={() => renameStore(st)} className="text-amber-600" title="Ubah nama"><IconPencil size={14} /></button>
                                        <button type="button" onClick={() => deleteStore(st)} className="text-rose-600" title="Hapus toko"><IconTrash size={14} /></button>
                                    </>)}
                                </span>
                            ))}
                        </div>
                        {canManage && (
                            <form onSubmit={(e) => { e.preventDefault(); addStore(); }} className="mt-2 flex flex-wrap gap-2">
                                <input value={newStore} onChange={(e) => setNewStore(e.target.value)} placeholder="Nama toko, mis. CCM" className={`${field} w-56`} />
                                <button type="submit" disabled={!newStore.trim()} className="inline-flex items-center gap-1 rounded-xl border border-primary-500 px-3 text-sm font-semibold text-primary-600 disabled:opacity-50"><IconPlus size={16} /> Tambah toko</button>
                            </form>
                        )}
                    </div>
                    <div className="space-y-2">
                        {studios.length === 0 && <p className="text-sm text-slate-500">Belum ada studio. Tambahkan studio pertama di bawah.</p>}
                        {studios.map((s) => (
                            <div key={s.id} className="flex flex-wrap items-center justify-between gap-2 rounded-xl border border-slate-100 px-3 py-2 dark:border-slate-800">
                                <div>
                                    <p className="font-medium text-slate-800 dark:text-slate-200">{s.name} {!s.is_active && <span className="ml-1 rounded-full bg-slate-200 px-2 py-0.5 text-xs">nonaktif</span>}</p>
                                    <p className="text-xs text-slate-500">Token …{s.token_hint || "----"} · terakhir terhubung: {dt(s.last_seen_at)}</p>
                                    {stores.length > 0 && (
                                        canManage ? (
                                            <p className="text-xs text-slate-500">Toko:{" "}
                                                <select value={s.store_id ?? ""} onChange={(e) => router.put(route("photobox-studios.update", s.id), { store_id: e.target.value || null }, { preserveScroll: true })} className="rounded-lg border border-slate-200 bg-transparent px-2 py-0.5 text-xs dark:border-slate-700" data-testid={`studio-store-${s.id}`}>
                                                    <option value="">Tanpa toko</option>
                                                    {stores.map((st) => <option key={st.id} value={st.id}>{st.name}</option>)}
                                                </select>
                                            </p>
                                        ) : (
                                            <p className="text-xs text-slate-500">Toko: {stores.find((st) => st.id === s.store_id)?.name || "Tanpa toko"}</p>
                                        )
                                    )}
                                    {s.is_active && s.status && (() => { const [dot, txt, label] = BOOTH_STATE[s.status.state] || BOOTH_STATE.unknown; return (
                                        <div className="mt-1" data-testid="booth-status">
                                            <span className={`inline-flex items-center gap-1.5 text-xs font-semibold ${txt}`}><span className={`h-2 w-2 rounded-full ${dot}`} />{label}</span>
                                            {s.status.notes?.map((n, i) => <p key={i} className="text-xs text-slate-500">{n}</p>)}
                                        </div>
                                    ); })()}
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
                        ["Transaksi TEST", "test", "Tampilkan semua", [["hide", "Sembunyikan TEST"], ["only", "Hanya TEST"]]],
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
                            <input value={searchText} onChange={(e) => setSearchText(e.target.value)} placeholder="Nomor transaksi / kode link / voucher / template"
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
            {testCount > 0 && (
                <div className="mb-3 flex flex-wrap items-center justify-between gap-2 rounded-xl border border-amber-200 bg-amber-50 px-4 py-2.5 text-xs text-amber-800 dark:border-amber-900 dark:bg-amber-950/30 dark:text-amber-300">
                    <span><b>{testCount} transaksi TEST</b> (simulasi / sandbox) tampil di riwayat tapi tidak dihitung sebagai pendapatan.</span>
                    {canDelete && <button type="button" onClick={removeAllTests} className="rounded-lg border border-amber-300 bg-white px-3 py-1.5 font-medium text-amber-800 hover:bg-amber-100 dark:border-amber-800 dark:bg-transparent dark:text-amber-300">Hapus transaksi TEST</button>}
                </div>
            )}

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
                            <Table.Th>Metode</Table.Th><Table.Th className="text-right">Jumlah</Table.Th><Table.Th>Status</Table.Th><Table.Th>Galeri</Table.Th><Table.Th>WhatsApp</Table.Th><Table.Th>Izin foto</Table.Th>{showActions && <Table.Th className="text-right">Aksi</Table.Th>}
                        </tr>
                    </Table.Thead>
                    <Table.Tbody>
                        {transactions.data.length ? transactions.data.map((t) => (
                            <tr key={t.id} className="hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <Table.Td><span className="font-mono text-xs font-semibold">{t.order_no}</span>{t.edited && <span className="ml-2 rounded-full bg-sky-100 px-2 py-0.5 text-[10px] font-semibold text-sky-700">diedit</span>}{t.is_test && <span title="Transaksi uji (mode simulasi / sandbox): tidak dihitung sebagai pendapatan" className="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-[10px] font-bold text-amber-700">TEST</span>}</Table.Td>
                                <Table.Td>{dt(t.paid_at || t.booth_created_at)}</Table.Td>
                                <Table.Td>{t.studio}</Table.Td>
                                <Table.Td>{t.template_name || "-"}{t.mode === "addprint" ? <span className="ml-1.5 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold uppercase text-amber-700">Add Print</span> : null}{t.category ? <span className="block text-xs text-slate-500">{t.category}</span> : null}</Table.Td>
                                <Table.Td>{METHOD_LABEL[t.method] || t.method}{t.voucher_code ? <span className="block font-mono text-xs text-slate-500">{t.voucher_code}</span> : null}</Table.Td>
                                <Table.Td className={`text-right ${t.is_test ? "text-slate-400 line-through" : ""}`}>{rupiah(t.amount)}</Table.Td>
                                <Table.Td><span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${STATUS_STYLE[t.status] || ""}`}>{STATUS_LABEL[t.status] || t.status}</span></Table.Td>
                                <Table.Td>
                                    {t.gallery_url ? (
                                        <div className="flex flex-col gap-1.5">
                                            {galleryStatusOn && t.gallery_state && GALLERY_BADGE[t.gallery_state] && (
                                                <span className={`w-fit rounded-full px-2.5 py-1 text-xs font-semibold ${GALLERY_BADGE[t.gallery_state][1]}`}>{GALLERY_BADGE[t.gallery_state][0]}{t.gallery_state === "uploaded" ? ` · ${t.gallery_files} file` : ""}{t.gallery_state === "partial" ? ` · ${t.gallery_files} file, ${t.gallery_pending} menunggu` : ""}</span>
                                            )}
                                            {t.link_code && <button type="button" onClick={() => copyText(t.link_code)} title="Salin kode link" className="w-fit font-mono text-[11px] text-slate-500 hover:text-slate-800">{fmtCode(t.link_code)}</button>}
                                            {t.gallery_alt && <a href={t.gallery_alt.url} target="_blank" rel="noopener noreferrer" title="Foto transaksi ini sudah ada di galeri dengan kode link lain (mis. unggah manual lama). Buka untuk memeriksa." className="w-fit rounded-md bg-sky-50 px-2 py-1 text-[11px] font-semibold text-sky-700 hover:bg-sky-100">Ada di kode lain: <span className="font-mono">{fmtCode(t.gallery_alt.code)}</span> · {t.gallery_alt.files} file</a>}
                                            <div className="flex gap-1.5">
                                                <a href={t.gallery_url} target="_blank" rel="noopener noreferrer" title="Buka galeri" className="rounded-lg border border-sky-200 bg-sky-100 p-2 text-sky-600 hover:bg-sky-200 dark:border-sky-800 dark:bg-sky-900/40 dark:text-sky-400"><IconExternalLink size={16} /></a>
                                                <button type="button" onClick={() => copyText(t.gallery_url)} title="Salin link" className="rounded-lg border border-slate-200 bg-slate-100 p-2 text-slate-600 hover:bg-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"><IconCopy size={16} /></button>
                                            </div>
                                        </div>
                                    ) : <span className="text-xs text-slate-400">-</span>}
                                </Table.Td>
                                <Table.Td>
                                    {(() => {
                                        const ready = !!t.gallery_url && (!galleryStatusOn || t.gallery_state === "uploaded" || t.gallery_state === "partial" || t.gallery_state === "unknown");
                                        const base = "inline-flex items-center gap-1.5 whitespace-nowrap rounded-lg border px-3 py-2 text-xs font-semibold";
                                        if (!ready) {
                                            const why = !t.gallery_url ? "Link belum ada" : t.gallery_state === "expired" ? "Link kedaluwarsa" : "Foto belum terupload";
                                            return <span title={`${why}. Unggah dulu di website galeri (menu Link pelanggan), lalu tombol ini aktif.`} className={`${base} cursor-not-allowed border-slate-200 bg-slate-100 text-slate-400 dark:border-slate-700 dark:bg-slate-800`}><IconBrandWhatsapp size={16} />Kirim link</span>;
                                        }
                                        return (
                                            <div className="flex items-center gap-1">
                                                {t.customer_wa
                                                    ? <a href={waLink(t)} target="_blank" rel="noopener noreferrer" title={`Kirim ke ${t.customer_name || t.customer_wa}`} className={`${base} border-emerald-200 bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:border-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-400`}><IconBrandWhatsapp size={16} />Kirim link</a>
                                                    : <button type="button" onClick={() => setWaFor(t)} title="Nomor tamu belum ada: isi manual atau cari dari data pelanggan" className={`${base} border-emerald-200 bg-emerald-100 text-emerald-700 hover:bg-emerald-200 dark:border-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-400`}><IconBrandWhatsapp size={16} />Kirim link</button>}
                                                <button type="button" onClick={() => setWaFor(t)} title="Isi / ubah nama & nomor WhatsApp" className="rounded-lg border border-slate-200 bg-slate-100 p-2 text-slate-600 hover:bg-slate-200 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300"><IconPencil size={14} /></button>
                                            </div>
                                        );
                                    })()}
                                    {t.customer_wa && <span className="mt-1 block text-[11px] text-slate-500">+{t.customer_wa}</span>}
                                </Table.Td>
                                <Table.Td>
                                    {t.social == null ? <span className="text-xs text-slate-400">{t.gallery_url ? "Belum dijawab" : "-"}</span> : <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${SOCIAL_BADGE[t.social][1]}`}>{SOCIAL_BADGE[t.social][0]}</span>}
                                </Table.Td>
                                {showActions && (
                                    <Table.Td>
                                        <div className="flex justify-end gap-2">
                                            {canEdit && <button type="button" onClick={() => toggleTest(t)} title={t.is_test ? "Tandai sebagai transaksi asli" : "Tandai sebagai TEST"} className={`rounded-lg border p-2 ${t.is_test ? "border-violet-300 bg-violet-200 text-violet-700 hover:bg-violet-300 dark:border-violet-800 dark:bg-violet-900/50 dark:text-violet-300" : "border-violet-200 bg-violet-100 text-violet-600 hover:bg-violet-200 dark:border-violet-800 dark:bg-violet-900/40 dark:text-violet-400"}`}><IconFlask size={16} /></button>}
                                            {canEdit && <button type="button" onClick={() => openEdit(t)} title="Edit" className="rounded-lg border border-amber-200 bg-amber-100 p-2 text-amber-600 hover:bg-amber-200 dark:border-amber-800 dark:bg-amber-900/40 dark:text-amber-400"><IconPencil size={16} /></button>}
                                            {canDelete && <button type="button" onClick={() => removeTx(t)} title="Hapus" className="rounded-lg border border-rose-200 bg-rose-100 p-2 text-rose-600 hover:bg-rose-200 dark:border-rose-800 dark:bg-rose-900/40 dark:text-rose-400"><IconTrash size={16} /></button>}
                                        </div>
                                    </Table.Td>
                                )}
                            </tr>
                        )) : <Table.Empty colSpan={showActions ? 11 : 10} message="Belum ada transaksi pada filter ini." />}
                    </Table.Tbody>
                </Table>
            </Table.Card>
            {transactions.last_page > 1 && <Pagination links={transactions.links} />}

            {waFor && <WaContactModal key={waFor.id} t={waFor} onClose={() => setWaFor(null)} />}
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
