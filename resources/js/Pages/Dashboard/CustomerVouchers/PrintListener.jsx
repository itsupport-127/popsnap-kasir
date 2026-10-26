import React, { useEffect, useRef, useState } from "react";
import { Head } from "@inertiajs/react";
import { IconPlugConnected, IconPlugOff, IconPrinter } from "@tabler/icons-react";
import { printReceiptViaQz } from "@/Utils/qzPrint";

/**
 * Halaman ini dibiarkan terbuka di PC kasir (tab tersendiri, boleh di-
 * minimize). Dia dengerin channel "print-jobs" lewat websocket (Reverb).
 * Setiap kali ada yang tekan "Print dari HP" di transaksi manapun, event
 * PrintJobRequested masuk ke sini dan otomatis dikirim ke QZ Tray — persis
 * seperti kalau kamu klik tombol "Print Langsung" manual di PC.
 */
export default function PrintListener() {
    const [status, setStatus] = useState("connecting"); // connecting | connected | error | disabled
    const [log, setLog] = useState([]);

    const pushLog = (entry) => {
        setLog((prev) => [
            { ...entry, at: new Date().toLocaleTimeString("id-ID") },
            ...prev,
        ].slice(0, 30));
    };

    useEffect(() => {
        if (!window.Echo) {
            setStatus("disabled");
            pushLog({
                type: "error",
                message:
                    "Websocket (Reverb) belum dikonfigurasi. Cek VITE_REVERB_* di .env lalu build ulang.",
            });
            return;
        }

        const channel = window.Echo.private("print-jobs");

        channel.subscribed(() => setStatus("connected"));
        channel.error((err) => {
            console.error("[PrintListener] channel error:", err);
            setStatus("error");
        });

        channel.listen(".PrintJobRequested", async (payload) => {
            pushLog({
                type: "incoming",
                message: `Perintah print masuk: ${payload.invoice} (${payload.size}mm) dari ${payload.requested_by}`,
            });

            try {
                await printReceiptViaQz({
                    invoice: payload.invoice,
                    size: payload.size,
                });
                pushLog({
                    type: "success",
                    message: `Struk ${payload.invoice} berhasil dikirim ke printer.`,
                });
            } catch (err) {
                console.error("[PrintListener] print error:", err);
                pushLog({
                    type: "error",
                    message: `Gagal print ${payload.invoice}: ${err?.message || "cek QZ Tray"}`,
                });
            }
        });

        return () => {
            window.Echo.leave("print-jobs");
        };
    }, []);

    const statusConfig = {
        connecting: { label: "Menghubungkan...", color: "text-warning-600", icon: IconPlugConnected },
        connected: { label: "Tersambung — siap menerima print dari HP", color: "text-success-600", icon: IconPlugConnected },
        error: { label: "Gagal tersambung ke server websocket", color: "text-danger-600", icon: IconPlugOff },
        disabled: { label: "Fitur ini belum dikonfigurasi (lihat log)", color: "text-slate-500", icon: IconPlugOff },
    };
    const current = statusConfig[status];
    const StatusIcon = current.icon;

    return (
        <>
            <Head title="Print Listener" />
            <div className="min-h-screen bg-slate-100 dark:bg-slate-950 py-10 px-4">
                <div className="max-w-2xl mx-auto space-y-6">
                    <div className="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 p-6">
                        <div className="flex items-center gap-3 mb-2">
                            <div className="w-12 h-12 rounded-xl bg-primary-50 dark:bg-primary-900/30 flex items-center justify-center">
                                <IconPrinter size={24} className="text-primary-600" />
                            </div>
                            <div>
                                <h1 className="text-lg font-bold text-slate-900 dark:text-white">
                                    Print Listener
                                </h1>
                                <p className="text-sm text-slate-500 dark:text-slate-400">
                                    Biarkan tab ini terbuka di komputer kasir yang konek ke printer via QZ Tray.
                                </p>
                            </div>
                        </div>

                        <div className={`flex items-center gap-2 mt-4 text-sm font-medium ${current.color}`}>
                            <StatusIcon size={18} />
                            {current.label}
                        </div>

                        {status !== "disabled" && !window.qz && (
                            <p className="text-xs text-amber-600 mt-2">
                                QZ Tray belum terdeteksi jalan di komputer ini. Pastikan aplikasi QZ Tray sudah dibuka.
                            </p>
                        )}
                    </div>

                    <div className="bg-white dark:bg-slate-900 rounded-2xl shadow-sm border border-slate-200 dark:border-slate-800 p-6">
                        <h2 className="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-3">
                            Aktivitas terakhir
                        </h2>
                        {log.length === 0 ? (
                            <p className="text-sm text-slate-400">Belum ada aktivitas.</p>
                        ) : (
                            <ul className="space-y-2">
                                {log.map((entry, idx) => (
                                    <li
                                        key={idx}
                                        className={`text-xs rounded-lg px-3 py-2 ${
                                            entry.type === "error"
                                                ? "bg-danger-50 text-danger-700 dark:bg-danger-900/20 dark:text-danger-400"
                                                : entry.type === "success"
                                                  ? "bg-success-50 text-success-700 dark:bg-success-900/20 dark:text-success-400"
                                                  : "bg-slate-50 text-slate-600 dark:bg-slate-800 dark:text-slate-300"
                                        }`}
                                    >
                                        <span className="opacity-60 mr-2">{entry.at}</span>
                                        {entry.message}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
