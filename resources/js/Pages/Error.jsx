import React from "react";
import { Head, Link } from "@inertiajs/react";
import {
    IconAlertTriangle,
    IconArrowLeft,
    IconCamera,
    IconLock,
    IconRefresh,
    IconServerOff,
    IconHome2,
    IconBrandInstagram,
} from "@tabler/icons-react";

const errorContent = {
    401: {
        title: "Autentikasi Dibutuhkan",
        description: "Sesi atau akses Anda tidak valid. Silakan masuk kembali untuk melanjutkan.",
        icon: IconLock,
    },
    403: {
        title: "Akses Ditolak",
        description: "Anda tidak memiliki izin untuk membuka halaman ini.",
        icon: IconLock,
    },
    404: {
        title: "Halaman Tidak Ditemukan",
        description: "Halaman yang Anda cari tidak tersedia, dipindahkan, atau URL yang dimasukkan tidak tepat.",
        icon: IconAlertTriangle,
    },
    419: {
        title: "Sesi Kedaluwarsa",
        description: "Sesi keamanan telah berakhir. Muat ulang halaman lalu coba kembali.",
        icon: IconRefresh,
    },
    429: {
        title: "Terlalu Banyak Permintaan",
        description: "Permintaan Anda dibatasi sementara. Tunggu sebentar sebelum mencoba lagi.",
        icon: IconAlertTriangle,
    },
    500: {
        title: "Terjadi Kesalahan Server",
        description: "Ada gangguan pada sistem. Silakan coba lagi dalam beberapa saat.",
        icon: IconServerOff,
    },
    503: {
        title: "Layanan Sementara Tidak Tersedia",
        description: "Aplikasi sedang dalam pemeliharaan atau belum siap melayani permintaan ini.",
        icon: IconServerOff,
    },
};

export default function Error({ status, homeUrl, homeLabel }) {
    const content = errorContent[status] ?? errorContent[500];
    const Icon = content.icon;

    return (
        <div className="min-h-screen bg-[#0d0d0d] text-white font-sans">
            <Head title={status + " - " + content.title} />

            <div className="absolute top-20 left-1/2 -translate-x-1/2 w-[500px] h-[500px] bg-pink-500/15 rounded-full blur-[120px] pointer-events-none" />
            <div className="absolute top-40 right-1/4 w-[300px] h-[300px] bg-violet-500/15 rounded-full blur-[100px] pointer-events-none" />

            <div className="relative mx-auto flex min-h-screen max-w-7xl flex-col px-6 py-6">

                <div className="flex items-center justify-between">
                    <Link href={homeUrl ?? "/"} className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-full bg-gradient-to-br from-pink-500 to-violet-500 flex items-center justify-center">
                            <IconCamera size={18} className="text-white" />
                        </div>
                        <span className="text-lg font-bold tracking-tight">Popsnap</span>
                    </Link>

                    <a
                        href="https://www.instagram.com/popsnapstudio/"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white/60 border border-white/10 rounded-full hover:border-pink-500/50 hover:text-pink-400 transition-all"
                    >
                        <IconBrandInstagram size={15} />
                        @popsnapstudio
                    </a>
                </div>

                <div className="flex flex-1 items-center justify-center py-10">
                    <div className="w-full max-w-4xl rounded-3xl border border-white/[0.08] bg-white/[0.04] p-8 backdrop-blur-xl md:p-12">
                        <div className="grid gap-10 md:grid-cols-[1.1fr_0.9fr] md:items-center">

                            <div>
                                <div className="inline-flex items-center gap-2 rounded-full border border-pink-500/30 bg-pink-500/10 px-4 py-2 text-sm font-semibold text-pink-400">
                                    <Icon size={16} />
                                    Status {status}
                                </div>

                                <h1 className="mt-6 text-4xl font-black tracking-tight md:text-5xl">
                                    {content.title}
                                </h1>

                                <p className="mt-4 max-w-2xl text-base leading-7 text-white/50 md:text-lg">
                                    {content.description}
                                </p>

                                <div className="mt-8 flex flex-col gap-3 sm:flex-row">
                                    <Link
                                        href={homeUrl ?? "/"}
                                        className="inline-flex items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-pink-500 to-violet-500 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-pink-500/25 hover:opacity-90 transition-opacity"
                                    >
                                        <IconHome2 size={17} />
                                        {homeLabel ?? "Kembali ke Beranda"}
                                    </Link>

                                    <button
                                        type="button"
                                        onClick={() => window.history.back()}
                                        className="inline-flex items-center justify-center gap-2 rounded-2xl border border-white/10 bg-white/[0.04] px-6 py-3 text-sm font-semibold text-white/70 hover:border-white/20 hover:text-white transition-all"
                                    >
                                        <IconArrowLeft size={17} />
                                        Kembali
                                    </button>
                                </div>
                            </div>

                            <div className="relative overflow-hidden rounded-3xl border border-white/[0.08] bg-gradient-to-br from-pink-500/10 via-transparent to-violet-500/10 p-8">
                                <div className="absolute right-5 top-5 rounded-full border border-pink-500/20 bg-white/[0.04] px-3 py-1 text-xs font-bold uppercase tracking-widest text-pink-400">
                                    Error State
                                </div>

                                <div className="flex min-h-[260px] flex-col justify-between">
                                    <div className="flex h-16 w-16 items-center justify-center rounded-2xl bg-gradient-to-br from-pink-500 to-violet-500 shadow-xl shadow-pink-500/25">
                                        <Icon size={34} strokeWidth={1.5} className="text-white" />
                                    </div>

                                    <div>
                                        <div className="text-7xl font-black leading-none text-white/10">
                                            {status}
                                        </div>
                                        <div className="mt-4 rounded-2xl border border-white/[0.07] bg-white/[0.04] p-4 text-sm text-white/40 leading-relaxed">
                                            Pastikan URL, hak akses, atau status layanan sudah sesuai sebelum mencoba lagi.
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div className="flex items-center justify-center pb-4">
                    <p className="text-sm text-white/20">
                        {"© " + new Date().getFullYear() + " Popsnap Photobox Studio by @papyrusjogja"}
                    </p>
                </div>

            </div>
        </div>
    );
}