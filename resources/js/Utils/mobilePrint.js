import axios from "axios";

/**
 * Deteksi kasar Android vs iOS/lainnya dari user agent.
 * RawBT cuma ada di Android, jadi tombolnya cuma perlu muncul di situ.
 */
export function isAndroid() {
    return /Android/i.test(window.navigator.userAgent || "");
}

export function isMobileDevice() {
    return /Android|iPhone|iPad|iPod/i.test(window.navigator.userAgent || "");
}

/**
 * Jalur 1 (utama, Android & iPhone): minta PC yang jalanin print.
 * HP cuma kirim sinyal "tolong print invoice X" ke server, lalu server
 * broadcast event lewat websocket (Reverb) ke tab /print-listener yang
 * terbuka di PC kasir. PC itu yang benar-benar ngirim ke QZ Tray.
 */
export async function requestRemotePrint(invoice, size = "58") {
    const { data } = await axios.post(route("print-relay.request", invoice), { size });
    return data;
}

/**
 * Jalur 2 (cadangan, Android saja): kirim langsung dari HP ke printer
 * Bluetooth via app RawBT (harus sudah di-install & printer sudah
 * di-pairing sekali dari dalam RawBT).
 *
 * RawBT nerima data lewat custom URL scheme. Kita pakai format "intent:"
 * supaya Chrome Android mau membuka app-nya (link "rawbt://" polos sering
 * diblokir Chrome kalau tidak dari format intent).
 */
export async function printViaRawBt(rawReceiptUrl) {
    const response = await fetch(rawReceiptUrl, { credentials: "same-origin" });
    if (!response.ok) {
        throw new Error(`Gagal ambil teks struk (status ${response.status})`);
    }
    const rawText = await response.text();

    // RawBT terima base64 dari teks ESC/POS mentah.
    const base64 = btoa(unescape(encodeURIComponent(rawText)));

    const intentUrl =
        `intent:${base64}#Intent;` +
        `scheme=rawbt;package=ru.a402d.rawbtprinter;end;`;

    window.location.href = intentUrl;
}
