import React from "react";

/**
 * Input rupiah: hanya angka, tampil berpemisah titik ("205.000"). Titik/koma/huruf yang diketik dibuang,
 * jadi tidak ada lagi nilai yang diam-diam jadi 0 (masalah `type="number"` saat mengetik "205.000" atau "205,000").
 * `value` = string angka murni ("205000"); `onChange` menerima string angka murni.
 */
export default function RupiahInput({ value, onChange, className = "", placeholder = "0", ...rest }) {
    const digits = String(value ?? "").replace(/\D/g, "");
    const shown = digits === "" ? "" : Number(digits).toLocaleString("id-ID");

    return (
        <div className="relative">
            <span className="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-sm text-slate-400">Rp</span>
            <input
                {...rest}
                type="text"
                inputMode="numeric"
                autoComplete="off"
                value={shown}
                onChange={(e) => onChange(e.target.value.replace(/\D/g, "").replace(/^0+(?=\d)/, "").slice(0, 12))}
                placeholder={placeholder}
                className={`${className} pl-11`}
            />
        </div>
    );
}
