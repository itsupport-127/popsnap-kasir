import { Head, Link } from "@inertiajs/react";
import {
    IconCamera,
    IconMapPin,
    IconClock,
    IconUsers,
    IconPhoto,
    IconBrandInstagram,
    IconArrowRight,
    IconSparkles,
    IconGift,
    IconStar,
} from "@tabler/icons-react";

export default function Welcome() {
    const features = [
        {
            icon: IconCamera,
            title: "Photobox Tanpa Booking",
            desc: "Langsung datang, langsung foto. Tanpa antre panjang, tanpa ribet.",
        },
        {
            icon: IconPhoto,
            title: "Cetak Instan",
            desc: "Hasil foto langsung tercetak dalam hitungan detik.",
        },
        {
            icon: IconUsers,
            title: "Cocok untuk Semua",
            desc: "Pas buat bareng teman, keluarga, pasangan, atau solo selfie.",
        },
        {
            icon: IconSparkles,
            title: "Banyak Pilihan Frame",
            desc: "Pilih template lucu, estetik, atau polos sesuai mood-mu.",
        },
        {
            icon: IconGift,
            title: "Cocok untuk Event",
            desc: "Ulang tahun, wisuda, bridesmaid, atau gathering kantor.",
        },
        {
            icon: IconStar,
            title: "Harga Terjangkau",
            desc: "Kenangan seru nggak harus mahal.",
        },
    ];

    const locations = [
        { name: "Ringroad Utara", city: "Yogyakarta", color: "bg-pink-500" },
        {
            name: "The Raquette Temple",
            city: "Bintaro",
            color: "bg-violet-500",
        },
    ];

    return (
        <div className="min-h-screen bg-[#0d0d0d] text-white font-sans">
            <Head title="Popsnap Photobox Studio" />

            <nav className="fixed top-0 left-0 right-0 z-50 bg-[#0d0d0d]/80 backdrop-blur-xl border-b border-white/10">
                <div className="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="w-10 h-10 rounded-full bg-white flex items-center justify-center overflow-hidden">
                            <img
                                src="/assets/logo/LogoPopsnap.png"
                                alt="Popsnap Logo"
                                className="w-6 h-6 object-contain"
                            />
                        </div>
                        <span className="text-xl font-bold tracking-tight">
                            Popsnap
                        </span>
                    </div>

                    <div className="hidden md:flex items-center gap-8">
                        <a
                            href="#fitur"
                            className="text-sm text-white/60 hover:text-white transition-colors"
                        >
                            Fitur
                        </a>
                        <a
                            href="#lokasi"
                            className="text-sm text-white/60 hover:text-white transition-colors"
                        >
                            Lokasi
                        </a>
                        <a
                            href="#jam-buka"
                            className="text-sm text-white/60 hover:text-white transition-colors"
                        >
                            Jam Buka
                        </a>
                    </div>

                    <div className="flex items-center gap-3">
                        <Link
                            href="/login"
                            className="px-5 py-2.5 text-sm font-semibold text-white/70 border border-white/10 rounded-full hover:border-white/20 hover:text-white transition-all"
                        >
                            Masuk Kasir
                        </Link>
                        <a
                            href="https://www.instagram.com/popsnapstudio/"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="hidden sm:flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-gradient-to-r from-pink-500 to-violet-500 rounded-full hover:opacity-90 transition-opacity"
                        >
                            <IconBrandInstagram size={16} />
                            Follow Kami
                        </a>
                    </div>
                </div>
            </nav>

            <section className="pt-36 pb-24 px-6 relative overflow-hidden">
                <div className="absolute top-20 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-pink-500/20 rounded-full blur-[120px] pointer-events-none" />
                <div className="absolute top-40 left-1/2 -translate-x-1/4 w-[400px] h-[400px] bg-violet-500/20 rounded-full blur-[100px] pointer-events-none" />

                <div className="max-w-4xl mx-auto text-center relative z-10">
                    <div className="inline-flex items-center gap-2 px-4 py-2 rounded-full border border-pink-500/30 bg-pink-500/10 text-pink-400 text-sm font-medium mb-8">
                        <IconSparkles size={14} />
                        Photobox Tanpa Booking · Open Every Day
                    </div>

                    <h1 className="text-5xl md:text-7xl font-black leading-tight tracking-tighter">
                        Pop in.{" "}
                        <span className="bg-gradient-to-r from-pink-400 via-fuchsia-400 to-violet-400 bg-clip-text text-transparent">
                            Snap.
                        </span>
                        <br />
                        Cherish.
                    </h1>

                    <p className="mt-6 text-lg text-white/60 max-w-xl mx-auto leading-relaxed">
                        Studio photobox seru di Jogja dan Bintaro. Datang kapan
                        saja, foto sebanyak-banyaknya, cetak langsung di tempat.
                    </p>

                    <div className="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                        <a
                            href="https://www.instagram.com/popsnapstudio/"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="w-full sm:w-auto px-8 py-4 text-base font-semibold text-white bg-gradient-to-r from-pink-500 to-violet-500 rounded-2xl hover:opacity-90 transition-all flex items-center justify-center gap-2 shadow-xl shadow-pink-500/30"
                        >
                            <IconBrandInstagram size={20} />
                            Lihat di Instagram
                        </a>
                        <a
                            href="#lokasi"
                            className="w-full sm:w-auto px-8 py-4 text-base font-semibold text-white/80 bg-white/5 border border-white/10 rounded-2xl hover:bg-white/10 transition-all flex items-center justify-center gap-2"
                        >
                            <IconMapPin size={20} />
                            Temukan Lokasi
                        </a>
                    </div>
                </div>
            </section>

            <section
                id="jam-buka"
                className="py-6 px-6 border-y border-white/5 bg-white/[0.02]"
            >
                <div className="max-w-7xl mx-auto flex flex-col sm:flex-row items-center justify-center gap-3 text-center">
                    <div className="flex items-center gap-2 text-white/70 text-sm">
                        <IconClock size={16} className="text-pink-400" />
                        <span>Buka setiap hari:</span>
                    </div>
                    <span className="text-white font-semibold text-sm">
                        Senin - Minggu · 10.00 - 21.00 WIB
                    </span>
                    <span className="hidden sm:block text-white/20">·</span>
                    <span className="text-pink-400 text-sm font-medium">
                        Tanpa Booking
                    </span>
                </div>
            </section>

            <section id="fitur" className="py-24 px-6">
                <div className="max-w-7xl mx-auto">
                    <div className="text-center mb-16">
                        <h2 className="text-3xl md:text-4xl font-bold tracking-tight">
                            Kenapa Popsnap?
                        </h2>
                        <p className="mt-4 text-white/50 max-w-xl mx-auto">
                            Semua yang bikin sesi foto kamu jadi memorable.
                        </p>
                    </div>

                    <div className="grid md:grid-cols-2 lg:grid-cols-3 gap-5">
                        {features.map((feature, i) => (
                            <div
                                key={i}
                                className="group p-6 rounded-2xl bg-white/[0.04] border border-white/[0.07] hover:border-pink-500/30 hover:bg-white/[0.07] transition-all"
                            >
                                <div className="w-11 h-11 rounded-xl bg-gradient-to-br from-pink-500/20 to-violet-500/20 border border-white/10 flex items-center justify-center mb-4">
                                    <feature.icon
                                        size={22}
                                        className="text-pink-400"
                                    />
                                </div>
                                <h3 className="text-base font-semibold text-white mb-2">
                                    {feature.title}
                                </h3>
                                <p className="text-sm text-white/50 leading-relaxed">
                                    {feature.desc}
                                </p>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            <section
                id="lokasi"
                className="py-24 px-6 bg-white/[0.02] border-y border-white/5"
            >
                <div className="max-w-7xl mx-auto">
                    <div className="text-center mb-14">
                        <h2 className="text-3xl md:text-4xl font-bold tracking-tight">
                            Lokasi Kami
                        </h2>
                        <p className="mt-4 text-white/50">
                            Dua studio, dua kota. Pilih yang terdekat.
                        </p>
                    </div>

                    <div className="grid md:grid-cols-2 gap-6 max-w-3xl mx-auto">
                        {locations.map((loc, i) => (
                            <div
                                key={i}
                                className="p-8 rounded-2xl bg-white/[0.04] border border-white/[0.08] flex flex-col gap-3"
                            >
                                <div
                                    className={`w-10 h-10 rounded-xl ${loc.color} flex items-center justify-center`}
                                >
                                    <IconMapPin
                                        size={20}
                                        className="text-white"
                                    />
                                </div>
                                <div>
                                    <p className="text-xs text-white/40 uppercase tracking-widest mb-1">
                                        {loc.city}
                                    </p>
                                    <h3 className="text-xl font-bold">
                                        {loc.name}
                                    </h3>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            <section className="py-24 px-6">
                <div className="max-w-3xl mx-auto text-center">
                    <div className="rounded-3xl p-12 bg-gradient-to-br from-pink-500/20 to-violet-500/20 border border-white/10">
                        <h2 className="text-3xl md:text-4xl font-black tracking-tight">
                            Yuk, Pop in dan Snap!
                        </h2>
                        <p className="mt-4 text-white/60 text-lg">
                            Ikuti @popsnapstudio untuk promo terbaru dan
                            inspirasi foto seru.
                        </p>
                        <a
                            href="https://www.instagram.com/popsnapstudio/"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="mt-8 inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-pink-500 to-violet-500 text-white font-semibold rounded-2xl hover:opacity-90 transition-all shadow-xl shadow-pink-500/30"
                        >
                            <IconBrandInstagram size={20} />
                            @popsnapstudio
                            <IconArrowRight size={18} />
                        </a>
                    </div>
                </div>
            </section>

            <footer className="py-8 px-6 border-t border-white/[0.06]">
                <div className="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                        <div className="w-8 h-8 rounded-full bg-white flex items-center justify-center">
                            <img
                                src="/assets/logo/LogoPopsnap.png"
                                alt="Popsnap Logo"
                                className="w-6 h-6 object-contain"
                            />
                        </div>
                        <span className="font-bold text-white/80 tracking-tight">
                            Popsnap
                        </span>
                    </div>
                    <p className="text-sm text-white/30">
                        {"© " +
                            new Date().getFullYear() +
                            " Popsnap Photobox Studio by @papyrusjogja"}
                    </p>
                    <a
                        href="https://www.instagram.com/popsnapstudio/"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="flex items-center gap-2 text-sm text-white/40 hover:text-pink-400 transition-colors"
                    >
                        <IconBrandInstagram size={16} />
                        @popsnapstudio
                    </a>
                </div>
            </footer>
        </div>
    );
}
