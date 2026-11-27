import React from "react";
import { Head, Link } from "@inertiajs/react";
import {
    IconAlertTriangle,
    IconArrowLeft,
    IconLock,
    IconMoon,
    IconRefresh,
    IconServerOff,
    IconHome2,
    IconSun,
} from "@tabler/icons-react";
import { useTheme } from "@/Context/ThemeSwitcherContext";

const errorContent = {
    401: {
        title: "Autentikasi Dibutuhkan",
        description: "Sesi atau akses Anda tidak valid. Silakan masuk kembali untuk melanjutkan.",
        hint: "Masuk lagi dengan akun Anda.",
        icon: IconLock,
    },
    403: {
        title: "Akses Ditolak",
        description: "Akun Anda tidak punya izin untuk membuka halaman ini.",
        hint: "Kalau Anda merasa harus bisa mengakses, minta admin menambahkan izinnya.",
        icon: IconLock,
    },
    404: {
        title: "Halaman Tidak Ditemukan",
        description: "Halaman yang Anda cari tidak tersedia, dipindahkan, atau alamatnya tidak tepat.",
        hint: "Gunakan tombol di bawah untuk kembali ke halaman yang bisa Anda buka.",
        icon: IconAlertTriangle,
    },
    419: {
        title: "Sesi Kedaluwarsa",
        description: "Sesi keamanan telah berakhir. Muat ulang halaman lalu coba kembali.",
        hint: "Tekan muat ulang, atau masuk lagi bila diminta.",
        icon: IconRefresh,
    },
    429: {
        title: "Terlalu Banyak Permintaan",
        description: "Permintaan Anda dibatasi sementara. Tunggu sebentar sebelum mencoba lagi.",
        hint: "Tunggu beberapa saat lalu ulangi.",
        icon: IconAlertTriangle,
    },
    500: {
        title: "Terjadi Kesalahan Server",
        description: "Ada gangguan pada sistem. Silakan coba lagi dalam beberapa saat.",
        hint: "Kalau berulang, catat jam kejadiannya dan hubungi IT.",
        icon: IconServerOff,
    },
    503: {
        title: "Layanan Sementara Tidak Tersedia",
        description: "Aplikasi sedang dalam pemeliharaan atau belum siap melayani permintaan ini.",
        hint: "Coba lagi beberapa menit lagi.",
        icon: IconServerOff,
    },
};

/** Halaman error mengikuti tema (terang/gelap) dan logo aplikasi kasir, sama seperti halaman lain. */
export default function Error({ status, homeUrl, homeLabel }) {
    const content = errorContent[status] ?? errorContent[500];
    const Icon = content.icon;
    const { darkMode, themeSwitcher } = useTheme();

    return (
        <div className={`${darkMode ? "dark" : ""}`}>
            <div className="flex min-h-screen flex-col bg-slate-50 font-sans text-slate-900 dark:bg-slate-950 dark:text-white">
                <Head title={`${status} - ${content.title}`} />

                <header className="mx-auto flex w-full max-w-5xl items-center justify-between px-5 py-5">
                    <Link href={homeUrl ?? "/"} className="flex items-center gap-3">
                        <span className="flex h-11 w-11 items-center justify-center overflow-hidden rounded-full bg-white shadow-sm ring-1 ring-slate-200 dark:ring-slate-700">
                            <img src="/assets/logo/LogoPopsnap.png" alt="Popsnap" className="h-[60%] w-[60%] object-contain" />
                        </span>
                        <span className="text-lg font-bold tracking-tight">Aplikasi Kasir Popsnap</span>
                    </Link>
                    <button
                        type="button"
                        onClick={themeSwitcher}
                        aria-label="Ganti tema"
                        className="rounded-xl border border-slate-200 bg-white p-2.5 text-slate-600 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800"
                    >
                        {darkMode ? <IconSun size={18} /> : <IconMoon size={18} />}
                    </button>
                </header>

                <main className="flex flex-1 items-center justify-center px-5 pb-10">
                    <div className="w-full max-w-xl rounded-3xl border border-slate-200 bg-white p-7 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-10">
                        <div className="flex h-14 w-14 items-center justify-center rounded-2xl bg-primary-50 text-primary-600 dark:bg-primary-500/10 dark:text-primary-400">
                            <Icon size={28} strokeWidth={1.6} />
                        </div>

                        <p className="mt-5 text-sm font-semibold uppercase tracking-widest text-primary-600 dark:text-primary-400">Error {status}</p>
                        <h1 className="mt-1 text-3xl font-bold tracking-tight sm:text-4xl">{content.title}</h1>
                        <p className="mt-3 text-base leading-7 text-slate-600 dark:text-slate-400">{content.description}</p>
                        <p className="mt-3 rounded-xl bg-slate-50 p-3 text-sm text-slate-500 dark:bg-slate-800/60 dark:text-slate-400">{content.hint}</p>

                        <div className="mt-7 flex flex-col gap-3 sm:flex-row">
                            <Link
                                href={homeUrl ?? "/"}
                                className="inline-flex items-center justify-center gap-2 rounded-xl bg-primary-500 px-5 py-3 text-sm font-semibold text-white transition-colors hover:bg-primary-600"
                            >
                                <IconHome2 size={17} />
                                {homeLabel ?? "Kembali ke Halaman Utama"}
                            </Link>
                            {status === 419 || status === 500 || status === 503 ? (
                                <button
                                    type="button"
                                    onClick={() => window.location.reload()}
                                    className="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                >
                                    <IconRefresh size={17} />
                                    Muat Ulang
                                </button>
                            ) : (
                                <button
                                    type="button"
                                    onClick={() => window.history.back()}
                                    className="inline-flex items-center justify-center gap-2 rounded-xl border border-slate-200 px-5 py-3 text-sm font-semibold text-slate-700 hover:bg-slate-100 dark:border-slate-700 dark:text-slate-300 dark:hover:bg-slate-800"
                                >
                                    <IconArrowLeft size={17} />
                                    Kembali
                                </button>
                            )}
                        </div>
                    </div>
                </main>

                <footer className="pb-5 text-center text-xs text-slate-400">{`© ${new Date().getFullYear()} Popsnap Photobox Studio`}</footer>
            </div>
        </div>
    );
}
