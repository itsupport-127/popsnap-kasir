import React, { useEffect, useRef, useState } from "react";
import { Head, useForm, usePage } from "@inertiajs/react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import Input from "@/Components/Dashboard/Input";
import Checkbox from "@/Components/Dashboard/Checkbox";
import { useAuthorization } from "@/Utils/authorization";
import {
    IconCreditCard,
    IconDeviceFloppy,
    IconBrandStripe,
    IconCash,
    IconQrcode,
    IconUpload,
} from "@tabler/icons-react";
import toast from "react-hot-toast";

export default function Payment({
    setting,
    paymentSettingSources = {},
    supportedGateways = [],
    webhookUrls = {},
    webhookWarnings = [],
}) {
    const { flash } = usePage().props;
    const { can } = useAuthorization();
    const canUpdatePaymentSettings = can("payment-settings-update");

    const qrisFileRef = useRef(null);
    const [qrisPreview, setQrisPreview] = useState(setting?.qris_image_url ?? null);

    const { data, setData, post, errors, processing } = useForm({
        _method: "PUT",
        default_gateway: setting?.default_gateway ?? "cash",
        bank_transfer_enabled: setting?.bank_transfer_enabled ?? false,
        // QRIS Statis
        qris_enabled: setting?.qris_enabled ?? false,
        qris_image: null,
        qris_merchant_name: setting?.qris_merchant_name ?? "",
        qris_nmid: setting?.qris_nmid ?? "",
        // Midtrans
        midtrans_enabled: setting?.midtrans_enabled ?? false,
        midtrans_server_key: "",
        midtrans_client_key: setting?.midtrans_client_key ?? "",
        midtrans_production: setting?.midtrans_production ?? false,
        // Xendit
        xendit_enabled: setting?.xendit_enabled ?? false,
        xendit_secret_key: "",
        xendit_public_key: setting?.xendit_public_key ?? "",
        xendit_callback_token: "",
        xendit_production: setting?.xendit_production ?? false,
        // DOKU
        doku_enabled: setting?.doku_enabled ?? false,
        doku_client_id: setting?.doku_client_id ?? "",
        doku_secret_key: "",
        // Flip
        flip_enabled: setting?.flip_enabled ?? false,
        flip_secret_key: "",
        flip_validation_token: "",
        flip_production: setting?.flip_production ?? false,
    });

    useEffect(() => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
    }, [flash]);

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route("settings.payments.update"), {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const handleQrisImageChange = (e) => {
        const file = e.target.files[0];
        if (!file) return;
        setData("qris_image", file);
        setQrisPreview(URL.createObjectURL(file));
    };

    const isGatewaySelectable = (gateway) => {
        if (gateway === "cash") return true;
        if (gateway === "qris_static") return data.qris_enabled;
        if (gateway === "midtrans") return data.midtrans_enabled;
        if (gateway === "xendit") return data.xendit_enabled;
        if (gateway === "doku") return data.doku_enabled;
        if (gateway === "flip") return data.flip_enabled;
        return false;
    };

    const renderSecretHint = (field, keepMessage) => {
        const source = paymentSettingSources?.[field];
        if (!source) return null;
        if (source.managed_by_environment) {
            return (
                <p className="text-xs text-amber-600 dark:text-amber-400">
                    Secret dikelola oleh environment dan tidak bisa diubah dari dashboard.
                </p>
            );
        }
        if (source.configured) {
            return (
                <p className="text-xs text-slate-500 dark:text-slate-400">
                    Tersimpan:{" "}
                    <span className="font-medium">{source.masked}</span>.{" "}
                    {keepMessage}
                </p>
            );
        }
        return null;
    };

    return (
        <>
            <Head title="Pengaturan Payment" />

            <div className="mb-6">
                <h1 className="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <IconCreditCard size={28} className="text-primary-500" />
                    Pengaturan Payment Gateway
                </h1>
                <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">
                    Konfigurasi metode pembayaran dan gateway
                </p>
            </div>

            <form onSubmit={handleSubmit} className="max-w-3xl space-y-6">
                {/* Default Gateway */}
                <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
                    <h3 className="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4 flex items-center gap-2">
                        <IconCash size={18} />
                        Gateway Default
                    </h3>
                    <p className="text-sm text-slate-500 dark:text-slate-400 mb-4">
                        Gateway pembayaran default yang digunakan kasir saat membuka halaman transaksi.
                    </p>
                    {!canUpdatePaymentSettings && (
                        <div className="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-300">
                            Anda hanya memiliki akses lihat. Perubahan payment settings memerlukan permission update dan konfirmasi password ulang.
                        </div>
                    )}
                    <div>
                        <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                            Pilih Gateway
                        </label>
                        <select
                            value={data.default_gateway}
                            onChange={(e) => setData("default_gateway", e.target.value)}
                            disabled={!canUpdatePaymentSettings}
                            className="w-full h-11 px-4 text-sm rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition-all"
                        >
                            {supportedGateways.map((gw) => (
                                <option
                                    key={gw.value}
                                    value={gw.value}
                                    disabled={!isGatewaySelectable(gw.value)}
                                >
                                    {gw.label}
                                    {!isGatewaySelectable(gw.value) && " (nonaktif)"}
                                </option>
                            ))}
                        </select>
                        {errors?.default_gateway && (
                            <small className="text-xs text-danger-500 mt-1">
                                {errors.default_gateway}
                            </small>
                        )}
                    </div>
                </div>

                {/* Bank Transfer */}
                <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="text-sm font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-2">
                            🏦 Transfer Bank
                        </h3>
                        <label
                            className={`flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium cursor-pointer transition-all ${
                                data.bank_transfer_enabled
                                    ? "bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400"
                                    : "bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400"
                            }`}
                        >
                            <Checkbox
                                checked={data.bank_transfer_enabled}
                                onChange={(e) => setData("bank_transfer_enabled", e.target.checked)}
                                disabled={!canUpdatePaymentSettings}
                            />
                            {data.bank_transfer_enabled ? "Aktif" : "Nonaktif"}
                        </label>
                    </div>
                    <p className="text-sm text-slate-500 dark:text-slate-400 mb-4">
                        Pembayaran manual via transfer bank. Kasir akan memasukkan transaksi dengan status pending, kemudian admin mengkonfirmasi setelah dana diterima.
                    </p>
                    <a
                        href={route("settings.bank-accounts.index")}
                        className="inline-flex items-center gap-2 text-sm text-primary-500 hover:text-primary-600 font-medium"
                    >
                        Kelola Rekening Bank →
                    </a>
                </div>

                {/* ============ QRIS STATIS ============ */}
                <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="text-sm font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-2">
                            <IconQrcode size={18} className="text-blue-500" />
                            QRIS Statis
                        </h3>
                        <label
                            className={`flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium cursor-pointer transition-all ${
                                data.qris_enabled
                                    ? "bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400"
                                    : "bg-slate-100 text-slate-500 dark:bg-slate-800 dark:text-slate-400"
                            }`}
                        >
                            <Checkbox
                                checked={data.qris_enabled}
                                onChange={(e) => setData("qris_enabled", e.target.checked)}
                                disabled={!canUpdatePaymentSettings}
                            />
                            {data.qris_enabled ? "Aktif" : "Nonaktif"}
                        </label>
                    </div>

                    <p className="text-sm text-slate-500 dark:text-slate-400 mb-4">
                        Upload gambar QR code QRIS toko kamu. Kasir menampilkan QR ini ke pelanggan, lalu konfirmasi pembayaran secara manual.
                    </p>

                    <div className={`space-y-4 ${!data.qris_enabled ? "opacity-50 pointer-events-none" : ""}`}>
                        {/* Upload Gambar */}
                        <div>
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Gambar QR Code{" "}
                                <span className="text-red-500">*</span>
                            </label>
                            <div className="flex items-start gap-4">
                                {/* Preview */}
                                <div
                                    onClick={() => canUpdatePaymentSettings && qrisFileRef.current?.click()}
                                    className="w-32 h-32 shrink-0 rounded-xl border-2 border-dashed border-slate-300 dark:border-slate-600 flex flex-col items-center justify-center overflow-hidden bg-slate-50 dark:bg-slate-800 cursor-pointer hover:border-primary-400 transition-colors"
                                >
                                    {qrisPreview ? (
                                        <img
                                            src={qrisPreview}
                                            alt="Preview QRIS"
                                            className="w-full h-full object-contain p-1"
                                        />
                                    ) : (
                                        <>
                                            <IconQrcode size={36} className="text-slate-300 dark:text-slate-600" />
                                            <span className="text-xs text-slate-400 mt-1">Klik upload</span>
                                        </>
                                    )}
                                </div>

                                <div className="flex-1 space-y-2">
                                    <input
                                        ref={qrisFileRef}
                                        type="file"
                                        accept="image/png,image/jpeg,image/jpg"
                                        className="hidden"
                                        onChange={handleQrisImageChange}
                                        disabled={!canUpdatePaymentSettings}
                                    />
                                    <button
                                        type="button"
                                        onClick={() => qrisFileRef.current?.click()}
                                        disabled={!canUpdatePaymentSettings}
                                        className="inline-flex items-center gap-2 px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 text-sm font-medium text-slate-700 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-800 transition-colors disabled:opacity-50"
                                    >
                                        <IconUpload size={15} />
                                        {qrisPreview ? "Ganti Gambar" : "Upload Gambar QRIS"}
                                    </button>
                                    <p className="text-xs text-slate-400 dark:text-slate-500">
                                        Format PNG atau JPG. Maks 2MB.<br />
                                        Download QR dari dashboard GoPay / ShopeePay / BRI / BCA merchant kamu.
                                    </p>
                                    {errors?.qris_image && (
                                        <p className="text-xs text-red-500">{errors.qris_image}</p>
                                    )}
                                </div>
                            </div>
                        </div>

                        {/* Nama Merchant */}
                        <Input
                            label="Nama Merchant"
                            type="text"
                            value={data.qris_merchant_name}
                            onChange={(e) => setData("qris_merchant_name", e.target.value)}
                            placeholder="Contoh: Popsnap Studio"
                            errors={errors?.qris_merchant_name}
                            disabled={!canUpdatePaymentSettings}
                        />

                        {/* NMID */}
                        <Input
                            label="NMID (Nomor Merchant ID) — opsional"
                            type="text"
                            value={data.qris_nmid}
                            onChange={(e) => setData("qris_nmid", e.target.value)}
                            placeholder="Contoh: ID2026012345678"
                            errors={errors?.qris_nmid}
                            disabled={!canUpdatePaymentSettings}
                        />
                        <p className="text-xs text-slate-400 -mt-2">
                            Tertera di bawah QR code kamu. Berguna untuk verifikasi pembayaran.
                        </p>

                        {/* Info Box */}
                        <div className="rounded-xl border border-blue-100 bg-blue-50 dark:border-blue-900/30 dark:bg-blue-950/20 px-4 py-3">
                            <p className="text-xs font-semibold text-blue-700 dark:text-blue-400 mb-1">
                                💡 Cara mendapatkan QRIS gratis
                            </p>
                            <ul className="text-xs text-blue-600 dark:text-blue-500 space-y-0.5 list-disc list-inside">
                                <li>Daftar merchant di GoPay, ShopeePay, OVO, atau DANA</li>
                                <li>Daftar merchant BRI, BCA, Mandiri, atau bank lainnya</li>
                                <li>Download QR dari dashboard merchant kamu</li>
                                <li>1 QR bisa dibayar dari semua e-wallet & m-banking</li>
                            </ul>
                        </div>
                    </div>
                </div>
                {/* ============ END QRIS STATIS ============ */}

                {/* Midtrans */}
                <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="text-sm font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-2">
                            <IconBrandStripe size={18} />
                            Midtrans Snap
                        </h3>
                        <label
                            className={`flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium cursor-pointer transition-all ${
                                data.midtrans_enabled
                                    ? "bg-success-100 dark:bg-success-900/50 text-success-700 dark:text-success-400"
                                    : "bg-slate-100 dark:bg-slate-800 text-slate-500"
                            }`}
                        >
                            <Checkbox
                                checked={data.midtrans_enabled}
                                onChange={(e) => setData("midtrans_enabled", e.target.checked)}
                                disabled={!canUpdatePaymentSettings}
                            />
                            {data.midtrans_enabled ? "Aktif" : "Nonaktif"}
                        </label>
                    </div>
                    <div className={`space-y-4 ${!data.midtrans_enabled ? "opacity-50 pointer-events-none" : ""}`}>
                        <div className="grid gap-4 md:grid-cols-2">
                            <Input
                                label="Server Key"
                                type="password"
                                value={data.midtrans_server_key}
                                onChange={(e) => setData("midtrans_server_key", e.target.value)}
                                errors={errors?.midtrans_server_key}
                                placeholder={
                                    paymentSettingSources?.midtrans_server_key?.configured
                                        ? "Kosongkan untuk mempertahankan nilai saat ini"
                                        : "SB-Mid-server-xxx"
                                }
                                disabled={
                                    !canUpdatePaymentSettings ||
                                    paymentSettingSources?.midtrans_server_key?.managed_by_environment
                                }
                            />
                            <Input
                                label="Client Key"
                                type="text"
                                value={data.midtrans_client_key}
                                onChange={(e) => setData("midtrans_client_key", e.target.value)}
                                errors={errors?.midtrans_client_key}
                                placeholder="SB-Mid-client-xxx"
                                disabled={!canUpdatePaymentSettings}
                            />
                        </div>
                        {renderSecretHint("midtrans_server_key", "Isi ulang hanya jika ingin mengganti secret.")}
                        <label className="flex items-center gap-2 cursor-pointer">
                            <Checkbox
                                checked={data.midtrans_production}
                                onChange={(e) => setData("midtrans_production", e.target.checked)}
                                disabled={!canUpdatePaymentSettings}
                            />
                            <span className="text-sm text-slate-600 dark:text-slate-400">Mode Produksi</span>
                        </label>
                    </div>
                </div>

                {/* Xendit */}
                <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="text-sm font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-2">
                            <IconCreditCard size={18} />
                            Xendit Invoice
                        </h3>
                        <label
                            className={`flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium cursor-pointer transition-all ${
                                data.xendit_enabled
                                    ? "bg-success-100 dark:bg-success-900/50 text-success-700 dark:text-success-400"
                                    : "bg-slate-100 dark:bg-slate-800 text-slate-500"
                            }`}
                        >
                            <Checkbox
                                checked={data.xendit_enabled}
                                onChange={(e) => setData("xendit_enabled", e.target.checked)}
                                disabled={!canUpdatePaymentSettings}
                            />
                            {data.xendit_enabled ? "Aktif" : "Nonaktif"}
                        </label>
                    </div>
                    <div className={`space-y-4 ${!data.xendit_enabled ? "opacity-50 pointer-events-none" : ""}`}>
                        <div className="grid gap-4 md:grid-cols-2">
                            <Input
                                label="Secret Key"
                                type="password"
                                value={data.xendit_secret_key}
                                onChange={(e) => setData("xendit_secret_key", e.target.value)}
                                errors={errors?.xendit_secret_key}
                                placeholder={
                                    paymentSettingSources?.xendit_secret_key?.configured
                                        ? "Kosongkan untuk mempertahankan nilai saat ini"
                                        : "xnd_development_xxx"
                                }
                                disabled={
                                    !canUpdatePaymentSettings ||
                                    paymentSettingSources?.xendit_secret_key?.managed_by_environment
                                }
                            />
                            <Input
                                label="Public Key"
                                type="text"
                                value={data.xendit_public_key}
                                onChange={(e) => setData("xendit_public_key", e.target.value)}
                                errors={errors?.xendit_public_key}
                                placeholder="xnd_public_development_xxx"
                                disabled={!canUpdatePaymentSettings}
                            />
                        </div>
                        {renderSecretHint("xendit_secret_key", "Isi ulang hanya jika ingin mengganti secret.")}
                        <Input
                            label="Callback Token"
                            type="password"
                            value={data.xendit_callback_token}
                            onChange={(e) => setData("xendit_callback_token", e.target.value)}
                            errors={errors?.xendit_callback_token}
                            placeholder={
                                paymentSettingSources?.xendit_callback_token?.configured
                                    ? "Kosongkan untuk mempertahankan nilai saat ini"
                                    : "xendit-callback-token"
                            }
                            disabled={
                                !canUpdatePaymentSettings ||
                                paymentSettingSources?.xendit_callback_token?.managed_by_environment
                            }
                        />
                        {renderSecretHint("xendit_callback_token", "Isi ulang hanya jika ingin mengganti token.")}
                        <label className="flex items-center gap-2 cursor-pointer">
                            <Checkbox
                                checked={data.xendit_production}
                                onChange={(e) => setData("xendit_production", e.target.checked)}
                                disabled={!canUpdatePaymentSettings}
                            />
                            <span className="text-sm text-slate-600 dark:text-slate-400">Mode Produksi</span>
                        </label>
                    </div>
                </div>

                {/* DOKU */}
                <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="text-sm font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-2">
                            <IconQrcode size={18} className="text-orange-500" />
                            DOKU QRIS Dinamis
                        </h3>
                        <label
                            className={`flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium cursor-pointer transition-all ${
                                data.doku_enabled
                                    ? "bg-success-100 dark:bg-success-900/50 text-success-700 dark:text-success-400"
                                    : "bg-slate-100 dark:bg-slate-800 text-slate-500"
                            }`}
                        >
                            <Checkbox
                                checked={data.doku_enabled}
                                onChange={(e) => setData("doku_enabled", e.target.checked)}
                                disabled={!canUpdatePaymentSettings}
                            />
                            {data.doku_enabled ? "Aktif" : "Nonaktif"}
                        </label>
                    </div>
                    <div className={`space-y-4 ${!data.doku_enabled ? "opacity-50 pointer-events-none" : ""}`}>
                        <div className="grid gap-4 md:grid-cols-2">
                            <Input
                                label="Client ID"
                                value={data.doku_client_id}
                                onChange={(e) => setData("doku_client_id", e.target.value)}
                                placeholder="CLIENT-ID-xxx"
                                errors={errors?.doku_client_id}
                                disabled={!canUpdatePaymentSettings}
                            />
                            <Input
                                label="Secret Key"
                                type="password"
                                value={data.doku_secret_key}
                                onChange={(e) => setData("doku_secret_key", e.target.value)}
                                placeholder={
                                    paymentSettingSources?.doku_secret_key?.configured
                                        ? "Kosongkan untuk mempertahankan nilai saat ini"
                                        : "SK-xxx"
                                }
                                errors={errors?.doku_secret_key}
                                disabled={
                                    !canUpdatePaymentSettings ||
                                    paymentSettingSources?.doku_secret_key?.managed_by_environment
                                }
                            />
                        </div>
                        {renderSecretHint("doku_secret_key", "Isi ulang hanya jika ingin mengganti secret.")}
                        <p className="text-xs text-slate-400">
                            Dapatkan Client ID & Secret Key di{" "}
                            <a href="https://dashboard.doku.com" target="_blank" className="text-primary-500 hover:underline">
                                dashboard.doku.com
                            </a>{" "}
                            → Integration → Key
                        </p>
                    </div>
                </div>

                {/* Flip */}
                <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6">
                    <div className="flex items-center justify-between mb-4">
                        <h3 className="text-sm font-semibold text-slate-700 dark:text-slate-300 flex items-center gap-2">
                            <IconCreditCard size={18} className="text-green-500" />
                            Flip.id
                        </h3>
                        <label
                            className={`flex items-center gap-2 px-3 py-1.5 rounded-full text-xs font-medium cursor-pointer transition-all ${
                                data.flip_enabled
                                    ? "bg-success-100 dark:bg-success-900/50 text-success-700 dark:text-success-400"
                                    : "bg-slate-100 dark:bg-slate-800 text-slate-500"
                            }`}
                        >
                            <Checkbox
                                checked={data.flip_enabled}
                                onChange={(e) => setData("flip_enabled", e.target.checked)}
                                disabled={!canUpdatePaymentSettings}
                            />
                            {data.flip_enabled ? "Aktif" : "Nonaktif"}
                        </label>
                    </div>
                    <div className={`space-y-4 ${!data.flip_enabled ? "opacity-50 pointer-events-none" : ""}`}>
                        <div className="grid gap-4 md:grid-cols-2">
                            <Input
                                label="Secret Key"
                                type="password"
                                value={data.flip_secret_key}
                                onChange={(e) => setData("flip_secret_key", e.target.value)}
                                placeholder={
                                    paymentSettingSources?.flip_secret_key?.configured
                                        ? "Kosongkan untuk mempertahankan nilai saat ini"
                                        : "flip-secret-xxx"
                                }
                                errors={errors?.flip_secret_key}
                                disabled={
                                    !canUpdatePaymentSettings ||
                                    paymentSettingSources?.flip_secret_key?.managed_by_environment
                                }
                            />
                            <Input
                                label="Validation Token"
                                value={data.flip_validation_token}
                                onChange={(e) => setData("flip_validation_token", e.target.value)}
                                placeholder={
                                    paymentSettingSources?.flip_validation_token?.configured
                                        ? "Kosongkan untuk mempertahankan nilai saat ini"
                                        : "flip-token-xxx"
                                }
                                errors={errors?.flip_validation_token}
                                disabled={
                                    !canUpdatePaymentSettings ||
                                    paymentSettingSources?.flip_validation_token?.managed_by_environment
                                }
                            />
                        </div>
                        {renderSecretHint("flip_secret_key", "Isi ulang hanya jika ingin mengganti secret.")}
                        <label className="flex items-center gap-2 cursor-pointer">
                            <Checkbox
                                checked={data.flip_production}
                                onChange={(e) => setData("flip_production", e.target.checked)}
                                disabled={!canUpdatePaymentSettings}
                            />
                            <span className="text-sm text-slate-600 dark:text-slate-400">Mode Produksi</span>
                        </label>
                        <p className="text-xs text-slate-400">
                            Dapatkan Secret Key di{" "}
                            <a href="https://big.flip.id" target="_blank" className="text-primary-500 hover:underline">
                                big.flip.id
                            </a>{" "}
                            → API → Credentials
                        </p>
                    </div>
                </div>

                {/* Webhook URLs */}
                <div className="bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700 p-6">
                    <h3 className="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4 flex items-center gap-2">
                        🔗 Webhook URLs
                    </h3>
                    <p className="text-sm text-slate-500 dark:text-slate-400 mb-4">
                        Salin URL berikut dan paste ke dashboard masing-masing gateway sebagai Notification/Callback URL.
                    </p>
                    {webhookWarnings.length > 0 && (
                        <div className="mb-4 space-y-2">
                            {webhookWarnings.map((warning) => (
                                <div
                                    key={warning}
                                    className="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-300"
                                >
                                    {warning}
                                </div>
                            ))}
                        </div>
                    )}
                    <div className="space-y-3">
                        {Object.entries(webhookUrls).map(([key, url]) => (
                            <div key={key}>
                                <label className="block text-xs font-medium text-slate-600 dark:text-slate-400 mb-1 capitalize">
                                    {key} Callback URL
                                </label>
                                <div className="flex items-center gap-2">
                                    <input
                                        type="text"
                                        readOnly
                                        value={url || ""}
                                        className="flex-1 h-10 px-3 text-sm rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-600 dark:text-slate-400"
                                    />
                                    <button
                                        type="button"
                                        onClick={() => {
                                            navigator.clipboard.writeText(url || "");
                                            toast.success("URL disalin!");
                                        }}
                                        className="px-3 h-10 rounded-lg border border-slate-200 dark:border-slate-700 text-sm font-medium text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800"
                                    >
                                        Salin
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>

                {/* Submit */}
                <div className="flex justify-end">
                    <button
                        type="submit"
                        disabled={processing || !canUpdatePaymentSettings}
                        className="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-primary-500 hover:bg-primary-600 text-white font-medium transition-colors disabled:opacity-50"
                    >
                        <IconDeviceFloppy size={18} />
                        {processing ? "Menyimpan..." : "Simpan Konfigurasi"}
                    </button>
                </div>
            </form>
        </>
    );
}

Payment.layout = (page) => <DashboardLayout children={page} />;