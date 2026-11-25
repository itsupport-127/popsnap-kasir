import React from "react";

/**
 * Pilih studio photobox yang dipegang shift saat dibuka.
 * value = array id studio terpilih. Kosong = semua studio (perilaku lama).
 * Tidak menampilkan apa pun kalau belum ada studio.
 */
export default function ShiftStudioPicker({ studios = [], value = [], onChange, className = "" }) {
    if (!studios.length) return null;

    const toggle = (id) => {
        onChange(value.includes(id) ? value.filter((x) => x !== id) : [...value, id]);
    };

    return (
        <div className={className}>
            <label className="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-300">
                Studio photobox yang dipegang
            </label>
            <div className="flex flex-wrap gap-2">
                <button
                    type="button"
                    onClick={() => onChange([])}
                    className={`rounded-xl border px-3 py-2 text-sm transition ${
                        value.length === 0
                            ? "border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300"
                            : "border-slate-200 text-slate-600 dark:border-slate-700 dark:text-slate-300"
                    }`}
                >
                    Semua studio
                </button>
                {studios.map((studio) => {
                    const active = value.includes(studio.id);
                    return (
                        <button
                            key={studio.id}
                            type="button"
                            onClick={() => toggle(studio.id)}
                            className={`rounded-xl border px-3 py-2 text-sm transition ${
                                active
                                    ? "border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-500/10 dark:text-primary-300"
                                    : "border-slate-200 text-slate-600 dark:border-slate-700 dark:text-slate-300"
                            }`}
                        >
                            {studio.name}
                        </button>
                    );
                })}
            </div>
            <p className="mt-2 text-xs text-slate-500 dark:text-slate-400">
                Transaksi photobox hanya masuk ke shift ini kalau berasal dari studio yang dipilih. Pilih
                &quot;Semua studio&quot; kalau tidak dibatasi. Tidak bisa diubah setelah shift dibuka.
            </p>
        </div>
    );
}
