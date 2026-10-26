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

// Card solid warna-warni bertumpuk 3D
function RibbonFan({ mirrored }) {
    const side = mirrored ? "right" : "left";

    // index 0 = biru (paling belakang), index 3 = oranye (paling depan)
    const cards = [
        { w: "100%", h: "100%", color: "#3372A9", rot: mirrored ? -28 : 28, z: 1, isBlue: true },
        { w: "82%",  h: "83%",  color: "#EABFD3", rot: mirrored ? -18 : 18, z: 2, isBlue: false },
        { w: "65%",  h: "66%",  color: "#EE283B", rot: mirrored ? -9  : 9,  z: 3, isBlue: false },
        { w: "50%",  h: "50%",  color: "#FDB740", rot: mirrored ? -1  : 1,  z: 4, isBlue: false },
    ];

    return (
        <div
            className="hidden md:block absolute top-0 pointer-events-none select-none"
            style={{
                [side]: 0,
                width: "clamp(200px, 28vw, 420px)",
                height: "100%",
                minHeight: "440px",
            }}
            aria-hidden="true"
        >
            <div style={{ position: "absolute", inset: 0, perspective: "1000px", perspectiveOrigin: `${side} center` }}>
                {cards.map((card, i) => {
                    const shadowX = mirrored ? "-16px" : "16px";
                    const boxShadow = card.isBlue
                        ? [
                            // drop shadow
                            `${shadowX} 20px 50px -8px rgba(0,0,0,0.6)`,
                            // outline tipis di seluruh tepi card
                            `0 0 0 1.5px rgba(255,255,255,0.22)`,
                            // inner glow biru kuat di bagian bawah
                            `inset 0 -80px 100px -10px rgba(100,190,255,0.4)`,
                            `inset 0 -160px 140px -30px rgba(51,114,169,0.35)`,
                            // inner glow sisi dalam
                            `inset ${mirrored ? "50px" : "-50px"} 0 80px -20px rgba(80,160,230,0.25)`,
                          ].join(", ")
                        : `${shadowX} 20px 50px -8px rgba(0,0,0,0.5)`;

                    return (
                        <div
                            key={i}
                            style={{
                                position: "absolute",
                                top: 0,
                                [side]: 0,
                                width: card.w,
                                height: card.h,
                                backgroundColor: card.color,
                                borderRadius: mirrored ? "0 0 0 2.5rem" : "0 0 2.5rem 0",
                                transform: `rotateY(${card.rot}deg)`,
                                transformOrigin: `${side} center`,
                                boxShadow,
                                zIndex: card.z,
                            }}
                        />
                    );
                })}
            </div>
        </div>
    );
}


function PopsnapLogo({ className = "w-10 h-10" }) {
    return (
        <div
            className={`${className} rounded-full bg-white flex items-center justify-center overflow-hidden flex-shrink-0`}
        >
            <img
                src="/assets/logo/LogoPopsnap.png"
                alt="Popsnap Logo"
                className="w-[60%] h-[60%] object-contain"
            />
        </div>
    );
}

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
        {
            name: "Ringroad Utara",
            city: "Yogyakarta",
            color: "from-sky-500 to-blue-600",
        },
        {
            name: "The Raquette Temple",
            city: "Bintaro",
            color: "from-blue-600 to-indigo-600",
        },
    ];

    return (
        <div className="min-h-screen bg-[#0a1120] text-white font-sans">
            <Head title="Popsnap Photobox Studio" />

            <nav className="fixed top-0 left-0 right-0 z-50 bg-[#0a1120]/85 backdrop-blur-xl border-b border-white/10">
                <div className="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <PopsnapLogo />
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
                            className="px-5 py-2.5 text-sm font-semibold text-white/70 bg-[#0f1d33] border-2 border-[#20364f] rounded-full hover:border-white/30 hover:text-white transition-all"
                        >
                            Masuk Kasir
                        </Link>
                        <a
                            href="https://www.instagram.com/popsnapstudio/"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="hidden sm:flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-[#3372a9] rounded-full hover:bg-[#3d84c4] transition-colors"
                        >
                            <IconBrandInstagram size={16} />
                            Follow Kami
                        </a>
                    </div>
                </div>
            </nav>

            <section className="relative pt-36 pb-24 px-6 overflow-hidden">
                {/* glow lembut di tengah */}
                <div
                    className="absolute inset-0 pointer-events-none"
                    style={{
                        background:
                            "radial-gradient(ellipse 60% 55% at 50% 40%, rgba(51,114,169,0.16) 0%, transparent 70%)",
                    }}
                />

                {/* bilah kain warna-warni kiri & kanan, murni CSS gradient (bukan gambar) */}
                <RibbonFan />
                <RibbonFan mirrored />

                <div className="max-w-4xl mx-auto text-center relative z-10">
                    <div className="inline-flex items-center gap-2 px-6 py-3 rounded-full bg-[#0d213d] border border-[#295d8d] text-[#6fa3d4] text-sm font-medium mb-8">
                        <IconSparkles size={14} />
                        Photobox Tanpa Booking · Open Every Day
                    </div>

                    <h1 className="text-5xl md:text-7xl font-black leading-tight tracking-tighter text-[#f9fafa]">
                        Pop in.{" "}
                        <span className="bg-gradient-to-r from-sky-300 via-blue-400 to-indigo-400 bg-clip-text text-transparent">
                            Snap.
                        </span>
                        <br />
                        Cherish.
                    </h1>

                    <p className="mt-6 text-lg text-[#7f96c2] max-w-xl mx-auto leading-relaxed">
                        Studio photobox seru di Jogja dan Bintaro. Datang kapan
                        saja, foto sebanyak-banyaknya, cetak langsung di tempat.
                    </p>

                    <div className="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                        <a
                            href="https://www.instagram.com/popsnapstudio/"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="w-full sm:w-auto px-8 py-4 text-base font-semibold text-white bg-[#3372a9] border border-[#275785] rounded-2xl hover:bg-[#3d84c4] transition-all flex items-center justify-center gap-2 shadow-xl shadow-blue-500/20"
                        >
                            <IconBrandInstagram size={20} />
                            Lihat di Instagram
                        </a>
                        <a
                            href="#lokasi"
                            className="w-full sm:w-auto px-8 py-4 text-base font-semibold text-white/80 bg-[#283b53] border-2 border-[#3b4e63] rounded-2xl hover:bg-[#324863] transition-all flex items-center justify-center gap-2"
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
                        <IconClock size={16} className="text-blue-400" />
                        <span>Buka setiap hari:</span>
                    </div>
                    <span className="text-white font-semibold text-sm">
                        Senin - Minggu · 10.00 - 21.00 WIB
                    </span>
                    <span className="hidden sm:block text-white/20">·</span>
                    <span className="text-blue-400 text-sm font-medium">
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

                    <div className="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        {features.map((feature, i) => (
                            <div
                                key={i}
                                className="group p-6 rounded-[28px] bg-white/[0.04] border border-white/10 backdrop-blur-sm hover:bg-white/[0.07] hover:border-blue-500/30 transition-all duration-300"
                            >
                                <div className="w-12 h-12 rounded-2xl bg-gradient-to-br from-sky-500 via-blue-500 to-indigo-500 shadow-lg shadow-blue-500/20 flex items-center justify-center mb-5">
                                    <feature.icon
                                        size={22}
                                        className="text-white"
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

                    <div className="grid gap-6 sm:grid-cols-2 max-w-3xl mx-auto">
                        {locations.map((loc, i) => (
                            <div
                                key={i}
                                className="p-8 rounded-[28px] bg-white/[0.04] border border-white/10 backdrop-blur-sm flex flex-col gap-4 hover:bg-white/[0.07] transition-all duration-300"
                            >
                                <div
                                    className={`w-11 h-11 rounded-xl bg-gradient-to-br ${loc.color} flex items-center justify-center`}
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
                    <div className="rounded-3xl p-12 bg-gradient-to-br from-blue-500/20 to-indigo-500/20 border border-white/10">
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
                            className="mt-8 inline-flex items-center gap-2 px-8 py-4 bg-[#3372a9] text-white font-semibold rounded-2xl hover:bg-[#3d84c4] transition-all shadow-xl shadow-blue-500/20"
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
                        <PopsnapLogo className="w-8 h-8" />
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
                        className="flex items-center gap-2 text-sm text-white/40 hover:text-blue-400 transition-colors"
                    >
                        <IconBrandInstagram size={16} />
                        @popsnapstudio
                    </a>
                </div>
            </footer>
        </div>
    );
}