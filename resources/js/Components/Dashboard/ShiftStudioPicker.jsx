import React, { useState } from "react";

const chip = (active) =>
    `rounded-xl border px-3 py-2 text-sm transition ${
        active
            ? "border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300"
            : "border-slate-200 text-slate-600 dark:border-slate-700 dark:text-slate-300"
    }`;

/**
 * Pilih cakupan photobox sebuah shift saat dibuka.
 *  - "Semua studio"            -> storeId null, studioIds []        (perilaku lama)
 *  - pilih toko                -> storeId <id>, studioIds []        (semua studio toko itu, termasuk yang ditambah belakangan)
 *  - toko + centang studio     -> storeId <id>, studioIds [..]      (hanya studio itu)
 *  - "Studio tanpa toko"       -> storeId null, studioIds [..]      (wajib ada yang dicentang)
 * `stores` = { stores: [{id,name,studios:[{id,name}]}], unassigned: [{id,name}] }.
 * Belum ada toko -> tampil daftar studio datar seperti sebelumnya (`studios`).
 */
export default function ShiftStudioPicker({ studios = [], stores = null, storeId = null, studioIds = [], onChange, className = "" }) {
    const storeList = stores?.stores ?? [];
    const unassigned = stores?.unassigned ?? [];
    const hasStores = storeList.length > 0;
    const [none, setNone] = useState(false); // mode "Studio tanpa toko"

    if (!hasStores && !studios.length) return null;

    const hint = (text) => <p className="mt-2 text-xs text-slate-500 dark:text-slate-400">{text}</p>;

    // ---- belum ada toko: daftar studio datar (perilaku awal) ----
    if (!hasStores) {
        const toggle = (id) => onChange(null, studioIds.includes(id) ? studioIds.filter((x) => x !== id) : [...studioIds, id]);
        return (
            <div className={className}>
                <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Studio photobox yang dipegang</label>
                <div className="flex flex-wrap gap-2">
                    <button type="button" onClick={() => onChange(null, [])} className={chip(studioIds.length === 0)}>Semua studio</button>
                    {studios.map((s) => (
                        <button key={s.id} type="button" onClick={() => toggle(s.id)} className={chip(studioIds.includes(s.id))}>{s.name}</button>
                    ))}
                </div>
                {hint("Transaksi photobox hanya masuk ke shift ini kalau berasal dari studio yang dipilih. Tidak bisa diubah setelah shift dibuka.")}
            </div>
        );
    }

    const store = storeList.find((s) => s.id === storeId) || null;
    const mode = store ? "store" : none ? "none" : "all";

    const pickAll = () => { setNone(false); onChange(null, []); };
    const pickStore = (s) => { setNone(false); onChange(s.id, []); };
    const pickNone = () => { setNone(true); onChange(null, unassigned.map((s) => s.id)); };

    const toggleIn = (list, id) => {
        const next = studioIds.includes(id) ? studioIds.filter((x) => x !== id) : [...studioIds, id];
        // "Studio tanpa toko" tidak boleh kosong (kosong = semua studio)
        if (mode === "none" && next.length === 0) return;
        onChange(mode === "store" ? storeId : null, next);
    };

    return (
        <div className={className}>
            <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">Toko dan studio photobox yang dipegang</label>
            <div className="flex flex-wrap gap-2">
                <button type="button" onClick={pickAll} className={chip(mode === "all")}>Semua studio</button>
                {storeList.map((s) => (
                    <button key={s.id} type="button" onClick={() => pickStore(s)} className={chip(mode === "store" && storeId === s.id)}>
                        {s.name} <span className="text-xs opacity-70">({s.studios.length} studio)</span>
                    </button>
                ))}
                {unassigned.length > 0 && (
                    <button type="button" onClick={pickNone} className={chip(mode === "none")}>Studio tanpa toko</button>
                )}
            </div>

            {mode === "store" && (
                <div className="mt-3 rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                    <p className="mb-2 text-xs font-medium text-slate-600 dark:text-slate-300">Studio di {store.name}</p>
                    {store.studios.length === 0 ? (
                        <p className="text-xs text-slate-500">Toko ini belum punya studio. Atur di Transaksi Photobox.</p>
                    ) : (
                        <div className="flex flex-wrap gap-2">
                            <button type="button" onClick={() => onChange(storeId, [])} className={chip(studioIds.length === 0)}>Semua studio toko ini</button>
                            {store.studios.map((s) => (
                                <button key={s.id} type="button" onClick={() => toggleIn(store.studios, s.id)} className={chip(studioIds.includes(s.id))}>{s.name}</button>
                            ))}
                        </div>
                    )}
                    {hint("“Semua studio toko ini” ikut mencakup studio yang ditambahkan ke toko nanti. Pilih studio tertentu untuk membatasi.")}
                </div>
            )}

            {mode === "none" && (
                <div className="mt-3 rounded-xl border border-slate-200 p-3 dark:border-slate-700">
                    <p className="mb-2 text-xs font-medium text-slate-600 dark:text-slate-300">Studio yang belum punya toko (minimal satu)</p>
                    <div className="flex flex-wrap gap-2">
                        {unassigned.map((s) => (
                            <button key={s.id} type="button" onClick={() => toggleIn(unassigned, s.id)} className={chip(studioIds.includes(s.id))}>{s.name}</button>
                        ))}
                    </div>
                </div>
            )}

            {hint("Transaksi photobox hanya masuk ke shift ini kalau berasal dari cakupan yang dipilih. Tidak bisa diubah setelah shift dibuka.")}
        </div>
    );
}
