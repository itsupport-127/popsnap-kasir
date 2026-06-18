// resources/js/Components/Dashboard/QrisStaticDisplay.jsx
// Tampil saat kasir pilih metode "QRIS Statis" — konfirmasi manual

import React from "react";
import { IconQrcode, IconCheck, IconAlertCircle } from "@tabler/icons-react";

function formatCurrency(amount) {
    return new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(amount);
}

export default function QrisStaticDisplay({ qrisConfig, totalAmount }) {
    if (!qrisConfig?.image_url) {
        return (
            <div className="rounded-xl border border-amber-200 bg-amber-50 dark:border-amber-900/40 dark:bg-amber-950/20 px-4 py-3 flex items-start gap-2">
                <IconAlertCircle size={16} className="text-amber-600 shrink-0 mt-0.5" />
                <p className="text-sm text-amber-700 dark:text-amber-400">
                    Gambar QRIS belum diupload. Silakan upload di{" "}
                    <strong>Pengaturan → Payment</strong>.
                </p>
            </div>
        );
    }

    return (
        <div className="space-y-3">
            <div className="flex items-center gap-2">
                <IconQrcode size={16} className="text-blue-500" />
                <p className="text-sm font-medium text-slate-700 dark:text-slate-300">
                    Scan QRIS untuk Membayar
                </p>
            </div>

            {/* Nominal */}
            <div className="rounded-xl bg-blue-50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/40 px-4 py-3 text-center">
                <p className="text-xs text-blue-600 dark:text-blue-400 mb-0.5">Total yang harus dibayar</p>
                <p className="text-xl font-bold text-blue-700 dark:text-blue-300">
                    {formatCurrency(totalAmount)}
                </p>
            </div>

            {/* QR Code */}
            <div className="flex flex-col items-center gap-2">
                <div className="bg-white p-2.5 rounded-2xl border-2 border-slate-200 dark:border-slate-700">
                    <img
                        src={qrisConfig.image_url}
                        alt="QRIS Code"
                        className="w-44 h-44 object-contain"
                    />
                </div>
                {qrisConfig.merchant_name && (
                    <div className="text-center">
                        <p className="text-sm font-semibold text-slate-800 dark:text-slate-200">
                            {qrisConfig.merchant_name}
                        </p>
                        {qrisConfig.nmid && (
                            <p className="text-xs text-slate-400">NMID: {qrisConfig.nmid}</p>
                        )}
                    </div>
                )}
            </div>

            {/* Steps */}
            <ol className="text-xs text-slate-500 dark:text-slate-400 space-y-1 list-decimal list-inside">
                <li>Buka aplikasi e-wallet atau m-banking pelanggan</li>
                <li>Scan QR code di atas</li>
                <li>Masukkan nominal <strong>{formatCurrency(totalAmount)}</strong></li>
                <li>Setelah selesai, tekan <strong>Konfirmasi Pembayaran</strong></li>
            </ol>

            {/* Warning manual */}
            <div className="rounded-xl border border-amber-100 bg-amber-50 dark:border-amber-900/30 dark:bg-amber-950/10 px-3 py-2 flex items-start gap-1.5">
                <IconCheck size={13} className="text-amber-600 shrink-0 mt-0.5" />
                <p className="text-xs text-amber-700 dark:text-amber-400">
                    Konfirmasi pembayaran dilakukan <strong>manual</strong> oleh kasir.
                </p>
            </div>
        </div>
    );
}
