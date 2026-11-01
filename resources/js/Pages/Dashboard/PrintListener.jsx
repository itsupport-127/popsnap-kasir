import React, { useEffect, useSyncExternalStore } from "react";
import { Head, router } from "@inertiajs/react";
import axios from "axios";
import toast from "react-hot-toast";
import DashboardLayout from "@/Layouts/DashboardLayout";
import { getPrintListenerStatus, subscribePrintListener } from "@/Utils/printListenerRuntime";
import {
    IconAlertTriangle,
    IconCircleCheck,
    IconClock,
    IconLoader2,
    IconPlugConnected,
    IconPlugOff,
    IconPrinter,
    IconRefresh,
} from "@tabler/icons-react";

/**
 * "Print Hari Ini": riwayat perintah print dari HP -> PC kasir, khusus hari ini (reset otomatis tiap hari).
 * Pendengar printnya sendiri berjalan di layout dashboard (lihat Hooks/usePrintListener.js), jadi halaman ini
 * TIDAK perlu dibiarkan terbuka — cukup dibuka kalau mau mengecek apa saja yang sudah dicetak.
 */
const LISTENER = {
    connected: { label: "Siap — komputer ini menerima print dari HP secara otomatis.", tone: "text-success-600", icon: IconPlugConnected },
    connecting: { label: "Menghubungkan ke server...", tone: "text-warning-600", icon: IconLoader2 },
    standby: { label: "Tab lain di browser ini yang sedang menerima print. Aman, tidak perlu melakukan apa pun.", tone: "text-slate-500", icon: IconPlugConnected },
    "no-qz": { label: "QZ Tray belum terdeteksi di komputer ini. Nyalakan QZ Tray — akan tersambung sendiri. (Di HP, status ini normal: PC kasir yang mencetak.)", tone: "text-amber-600", icon: IconAlertTriangle },
    "session-expired": { label: "Sesi login habis. Login ulang (di tab ini atau tab lain) agar print dari HP kembali jalan.", tone: "text-danger-600", icon: IconAlertTriangle },
    error: { label: "Gagal tersambung ke server websocket. Coba muat ulang halaman.", tone: "text-danger-600", icon: IconPlugOff },
    disabled: { label: "Websocket (Reverb) belum dikonfigurasi. Cek VITE_REVERB_* di .env lalu build ulang.", tone: "text-slate-500", icon: IconPlugOff },
    idle: { label: "Menyiapkan...", tone: "text-slate-500", icon: IconLoader2 },
};

const STATUS = {
    queued: { label: "Menunggu PC", cls: "bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300", icon: IconClock },
    printing: { label: "Mencetak...", cls: "bg-warning-50 text-warning-700 dark:bg-warning-900/20 dark:text-warning-400", icon: IconLoader2 },
    printed: { label: "Selesai", cls: "bg-success-50 text-success-700 dark:bg-success-900/20 dark:text-success-400", icon: IconCircleCheck },
    failed: { label: "Gagal", cls: "bg-danger-50 text-danger-700 dark:bg-danger-900/20 dark:text-danger-400", icon: IconAlertTriangle },
};

const jam = (iso) => (iso ? new Date(iso).toLocaleTimeString("id-ID", { hour: "2-digit", minute: "2-digit", second: "2-digit" }) : "-");

export default function PrintListener({ jobs = [], printerName }) {
    const listener = useSyncExternalStore(subscribePrintListener, getPrintListenerStatus, getPrintListenerStatus);
    const current = LISTENER[listener.state] ?? LISTENER.idle;
    const StatusIcon = current.icon;

    const refresh = () => router.reload({ only: ["jobs"], preserveScroll: true });

    // Segarkan daftar tiap beberapa detik (hanya saat tab terlihat) dan setiap kali PC ini selesai mencetak.
    useEffect(() => {
        const id = setInterval(() => {
            if (document.visibilityState === "visible") refresh();
        }, 8000);
        return () => clearInterval(id);
    }, []);
    useEffect(() => {
        if (listener.lastJob) refresh();
    }, [listener.lastJob]);

    const retry = async (job) => {
        try {
            await axios.post(route("print-relay.request", job.invoice), { size: job.size });
            toast.success(`Perintah print ${job.invoice} dikirim ulang.`);
            refresh();
        } catch (err) {
            toast.error("Gagal mengirim ulang perintah print.");
        }
    };

    const count = (s) => jobs.filter((j) => j.status === s).length;
    const today = new Date().toLocaleDateString("id-ID", { weekday: "long", day: "numeric", month: "long", year: "numeric" });

    return (
        <>
            <Head title="Print Hari Ini" />
            <div className="space-y-5">
                <div>
                    <h1 className="flex items-center gap-2 text-2xl font-bold text-slate-900 dark:text-white">
                        <IconPrinter size={26} className="text-primary-500" /> Print Hari Ini
                    </h1>
                    <p className="text-sm text-slate-500 dark:text-slate-400">
                        {today} · riwayat perintah &quot;Print dari HP&quot;, otomatis kosong lagi tiap hari. Halaman ini tidak perlu dibiarkan terbuka agar print jalan.
                    </p>
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                    <div className={`flex items-start gap-2 text-sm font-medium ${current.tone}`}>
                        <StatusIcon size={18} className={`mt-0.5 shrink-0 ${listener.state === "connecting" ? "animate-spin" : ""}`} />
                        <span>{current.label}</span>
                    </div>
                    <p className="mt-2 text-xs text-slate-500 dark:text-slate-400">
                        Printer: <b>{printerName}</b> · berlaku untuk komputer yang membuka dashboard dengan QZ Tray menyala (nama printer diatur di Pengaturan → Printer).
                    </p>
                </div>

                <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    {[
                        ["Total", jobs.length, "text-slate-900 dark:text-white"],
                        ["Selesai", count("printed"), "text-success-600"],
                        ["Gagal", count("failed"), "text-danger-600"],
                        ["Menunggu / proses", count("queued") + count("printing"), "text-warning-600"],
                    ].map(([label, value, tone]) => (
                        <div key={label} className="rounded-2xl border border-slate-200 bg-white p-4 dark:border-slate-800 dark:bg-slate-900">
                            <p className="text-xs text-slate-500 dark:text-slate-400">{label}</p>
                            <p className={`text-2xl font-bold ${tone}`}>{value}</p>
                        </div>
                    ))}
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                    <div className="flex items-center justify-between border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                        <h2 className="text-sm font-semibold text-slate-700 dark:text-slate-300">Daftar print hari ini</h2>
                        <button type="button" onClick={refresh} className="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1.5 text-xs text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                            <IconRefresh size={14} /> Segarkan
                        </button>
                    </div>

                    {jobs.length === 0 ? (
                        <p className="px-4 py-10 text-center text-sm text-slate-400">Belum ada perintah print hari ini.</p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead className="text-xs text-slate-500 dark:text-slate-400">
                                    <tr>
                                        <th className="px-4 py-2 font-medium">Jam</th>
                                        <th className="px-4 py-2 font-medium">Invoice</th>
                                        <th className="px-4 py-2 font-medium">Ukuran</th>
                                        <th className="px-4 py-2 font-medium">Diminta oleh</th>
                                        <th className="px-4 py-2 font-medium">Dicetak oleh</th>
                                        <th className="px-4 py-2 font-medium">Status</th>
                                        <th className="px-4 py-2" />
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100 dark:divide-slate-800">
                                    {jobs.map((job) => {
                                        const st = STATUS[job.status] ?? STATUS.queued;
                                        const Icon = st.icon;
                                        return (
                                            <tr key={job.id}>
                                                <td className="whitespace-nowrap px-4 py-2.5 text-slate-500">{jam(job.created_at)}</td>
                                                <td className="px-4 py-2.5 font-mono text-xs font-semibold text-slate-800 dark:text-slate-100">{job.invoice}</td>
                                                <td className="px-4 py-2.5 text-slate-600 dark:text-slate-300">{job.size}mm</td>
                                                <td className="px-4 py-2.5 text-slate-600 dark:text-slate-300">{job.requested_by || "-"}</td>
                                                <td className="px-4 py-2.5 text-slate-600 dark:text-slate-300">{job.printed_by || "-"}</td>
                                                <td className="px-4 py-2.5">
                                                    <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium ${st.cls}`}>
                                                        <Icon size={13} className={job.status === "printing" ? "animate-spin" : ""} />
                                                        {st.label}
                                                    </span>
                                                    {job.status === "failed" && job.error && (
                                                        <p className="mt-1 max-w-xs text-xs text-danger-600 dark:text-danger-400">{job.error}</p>
                                                    )}
                                                    {job.status === "queued" && (
                                                        <p className="mt-1 max-w-xs text-xs text-slate-400">Belum ada PC dengan QZ Tray yang menerima.</p>
                                                    )}
                                                </td>
                                                <td className="px-4 py-2.5 text-right">
                                                    {(job.status === "failed" || job.status === "queued") && (
                                                        <button type="button" onClick={() => retry(job)} className="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-50 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800">
                                                            Cetak ulang
                                                        </button>
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}

PrintListener.layout = (page) => <DashboardLayout children={page} />;
