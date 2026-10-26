import React, { useState } from "react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import { Head, router, useForm } from "@inertiajs/react";

const TABS = [
    ["wa", "Antrean WA"],
    ["vouchers", "Voucher Sesi"],
    ["devices", "Perangkat Booth"],
    ["upload", "Unggah Manual"],
];

const card = "rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-4";
const input = "w-full rounded-lg border border-slate-300 dark:border-slate-700 dark:bg-slate-950 px-3 py-2 text-sm";
const btn = "rounded-lg bg-orange-500 hover:bg-orange-600 text-white px-3 py-2 text-sm font-medium";
const btn2 = "rounded-lg border border-slate-300 dark:border-slate-700 px-3 py-1.5 text-xs";

const mediaLabel = { pending: "Menunggu unggah", partial: "Sebagian", complete: "Lengkap" };

function WaTab({ sessions }) {
    const phone = useForm({ phone: "" });
    return (
        <div className="space-y-3">
            {sessions.length === 0 && <div className={card}>Belum ada sesi.</div>}
            {sessions.map((s) => (
                <div key={s.id} className={card + " flex flex-wrap items-center gap-3 justify-between"}>
                    <div className="text-sm">
                        <div className="font-semibold">
                            {s.template || "Sesi"} · {s.photos} foto {s.was_offline && <span className="text-amber-600">(offline)</span>}
                        </div>
                        <div className="text-slate-500">{s.at} · {s.device || "-"} · {s.phone || "tanpa nomor"}</div>
                        <div className="text-xs mt-1">
                            Media: <b>{mediaLabel[s.media_status]}</b> ({s.media_count}) · WA: <b>{s.wa_status}</b>
                        </div>
                    </div>
                    <div className="flex gap-2 items-center flex-wrap">
                        {s.wa_link ? (
                            <a href={s.wa_link} target="_blank" rel="noreferrer" className={btn}
                               onClick={() => router.post(route("booth.sessions.wa-sent", s.id), {}, { preserveScroll: true })}>
                                Kirim via WhatsApp
                            </a>
                        ) : (
                            <form className="flex gap-1" onSubmit={(e) => { e.preventDefault(); phone.post(route("booth.sessions.phone", s.id), { preserveScroll: true }); }}>
                                <input className={input + " w-40"} placeholder="08xxxx" value={phone.data.phone} onChange={(e) => phone.setData("phone", e.target.value)} />
                                <button className={btn2}>Simpan</button>
                            </form>
                        )}
                        <button className={btn2} onClick={() => navigator.clipboard?.writeText(s.share_url)}>Salin link</button>
                    </div>
                </div>
            ))}
        </div>
    );
}

function VoucherTab({ vouchers }) {
    const f = useForm({ code: "", count: 1, max_uses: 1, valid_days: 30, note: "" });
    return (
        <div className="space-y-4">
            <form className={card + " grid grid-cols-2 md:grid-cols-5 gap-2 items-end"} onSubmit={(e) => { e.preventDefault(); f.post(route("booth.vouchers.store"), { preserveScroll: true }); }}>
                <label className="text-xs">Kode (kosong = acak)<input className={input} value={f.data.code} onChange={(e) => f.setData("code", e.target.value)} /></label>
                <label className="text-xs">Jumlah<input type="number" min="1" className={input} value={f.data.count} onChange={(e) => f.setData("count", e.target.value)} /></label>
                <label className="text-xs">Maks. dipakai<input type="number" min="1" className={input} value={f.data.max_uses} onChange={(e) => f.setData("max_uses", e.target.value)} /></label>
                <label className="text-xs">Berlaku (hari)<input type="number" min="1" className={input} value={f.data.valid_days} onChange={(e) => f.setData("valid_days", e.target.value)} /></label>
                <button className={btn}>Buat voucher</button>
            </form>
            <div className={card + " overflow-x-auto"}>
                <table className="w-full text-sm">
                    <thead><tr className="text-left text-slate-500"><th>Kode</th><th>Asal</th><th>Dipakai</th><th>Kedaluwarsa</th><th>Status</th><th /></tr></thead>
                    <tbody>
                        {vouchers.map((v) => (
                            <tr key={v.id} className="border-t border-slate-100 dark:border-slate-800">
                                <td className="py-2 font-mono">{v.code}</td>
                                <td>{v.source}</td>
                                <td>{v.used_count}/{v.max_uses}</td>
                                <td>{v.expires_at ? v.expires_at.slice(0, 10) : "-"}</td>
                                <td>{v.is_active ? "Aktif" : "Nonaktif"}</td>
                                <td><button className={btn2} onClick={() => router.post(route("booth.vouchers.toggle", v.id), {}, { preserveScroll: true })}>{v.is_active ? "Nonaktifkan" : "Aktifkan"}</button></td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

function DeviceTab({ devices, outlet, newToken }) {
    const f = useForm({ name: "" });
    const o = useForm({ name: outlet.name, wa_template: outlet.wa_template || "" });
    return (
        <div className="space-y-4">
            {newToken && (
                <div className={card + " border-orange-400"}>
                    <div className="font-semibold">Perangkat "{newToken.name}" dibuat — salin token sekarang (tidak ditampilkan lagi):</div>
                    <code className="block mt-2 break-all rounded bg-slate-100 dark:bg-slate-800 p-2 text-sm">{newToken.token}</code>
                    <div className="text-xs mt-1 text-slate-500">Awalan voucher booth ini: <b>{newToken.prefix}-</b></div>
                </div>
            )}
            <form className={card + " flex gap-2 items-end"} onSubmit={(e) => { e.preventDefault(); f.post(route("booth.devices.store"), { preserveScroll: true, onSuccess: () => f.reset() }); }}>
                <label className="text-xs flex-1">Nama perangkat baru<input className={input} placeholder="Booth 1" value={f.data.name} onChange={(e) => f.setData("name", e.target.value)} /></label>
                <button className={btn}>Tambah perangkat</button>
            </form>
            <div className={card}>
                {devices.map((d) => (
                    <div key={d.id} className="flex items-center justify-between py-2 border-b last:border-0 border-slate-100 dark:border-slate-800 text-sm">
                        <div><b>{d.name}</b> <span className="font-mono text-slate-500">[{d.prefix}]</span>
                            <div className="text-xs text-slate-500">Terakhir online: {d.last_seen_at || "belum pernah"} {d.last_ip && `· ${d.last_ip}`}</div></div>
                        <button className={btn2} onClick={() => router.post(route("booth.devices.toggle", d.id), {}, { preserveScroll: true })}>{d.is_active ? "Nonaktifkan" : "Aktifkan"}</button>
                    </div>
                ))}
            </div>
            <form className={card + " space-y-2"} onSubmit={(e) => { e.preventDefault(); o.put(route("booth.outlet.update"), { preserveScroll: true }); }}>
                <label className="text-xs block">Nama outlet<input className={input} value={o.data.name} onChange={(e) => o.setData("name", e.target.value)} /></label>
                <label className="text-xs block">Template pesan WhatsApp (pakai {"{link}"} untuk tautan unduhan)
                    <textarea rows="3" className={input} value={o.data.wa_template} onChange={(e) => o.setData("wa_template", e.target.value)} /></label>
                <button className={btn}>Simpan</button>
            </form>
        </div>
    );
}

function UploadTab() {
    const f = useForm({ zip: null });
    return (
        <form className={card + " space-y-3"} onSubmit={(e) => { e.preventDefault(); f.post(route("booth.upload"), { forceFormData: true, onSuccess: () => f.reset() }); }}>
            <p className="text-sm text-slate-600 dark:text-slate-300">
                Booth offline? Kompres (ZIP) folder sesi dari output folder booth — harus berisi <code>session.json</code> — lalu unggah di sini.
                Setelah selesai, sesi muncul di Antrean WA dengan link unduhan yang aktif.
            </p>
            <input type="file" accept=".zip" onChange={(e) => f.setData("zip", e.target.files[0])} />
            {f.errors.zip && <div className="text-sm text-rose-600">{f.errors.zip}</div>}
            <div><button className={btn} disabled={!f.data.zip || f.processing}>{f.processing ? "Mengunggah…" : "Unggah"}</button></div>
        </form>
    );
}

export default function Index({ outlet, devices, vouchers, sessions, newToken }) {
    const [tab, setTab] = useState("wa");
    return (
        <>
            <Head title="Photobox" />
            <div className="space-y-4">
                <div className="flex gap-2 flex-wrap">
                    {TABS.map(([k, l]) => (
                        <button key={k} onClick={() => setTab(k)}
                            className={"rounded-full px-4 py-1.5 text-sm " + (tab === k ? "bg-orange-500 text-white" : "border border-slate-300 dark:border-slate-700")}>{l}</button>
                    ))}
                </div>
                {tab === "wa" && <WaTab sessions={sessions} />}
                {tab === "vouchers" && <VoucherTab vouchers={vouchers} />}
                {tab === "devices" && <DeviceTab devices={devices} outlet={outlet} newToken={newToken} />}
                {tab === "upload" && <UploadTab />}
            </div>
        </>
    );
}

Index.layout = (page) => <DashboardLayout children={page} />;
