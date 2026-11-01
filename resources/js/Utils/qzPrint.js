// Nama printer persis seperti yang muncul di Windows / QZ Tray
// Nilai default; nama printer sebenarnya diatur di Pengaturan → Printer.
export const QZ_PRINTER_NAME = "POS-58";

// Percobaan sambung ke QZ Tray yang sedang berjalan (dipakai bersama oleh semua pemanggil).
let connecting = null;

const withTimeout = (promise, label, ms = 10000) =>
    Promise.race([
        promise,
        new Promise((_, reject) =>
            setTimeout(
                () => reject(new Error(`Timeout di tahap: ${label}`)),
                ms,
            ),
        ),
    ]);

/**
 * Siapkan sertifikat + tanda tangan QZ lalu pastikan websocket ke QZ Tray tersambung.
 * Melempar error kalau QZ Tray tidak jalan. Dipakai print manual DAN pendengar print otomatis.
 */
export async function ensureQzConnected() {
    if (!window.qz) {
        throw new Error(
            "QZ Tray belum terdeteksi. Pastikan QZ Tray sudah jalan di komputer ini.",
        );
    }

    const qz = window.qz;

    qz.security.setCertificatePromise((resolve, reject) => {
        fetch(route("qz.certificate"), { credentials: "same-origin" })
            .then((res) => res.text())
            .then((cert) =>
                cert.trim()
                    ? resolve(cert)
                    : reject(
                          "Cert tidak ditemukan. Jalankan: php artisan qz:generate-cert",
                      ),
            )
            .catch(reject);
    });

    qz.security.setSignatureAlgorithm("SHA512");
    qz.security.setSignaturePromise((toSign) => (resolve, reject) => {
        fetch(route("qz.sign"), {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN":
                    document.querySelector('meta[name="csrf-token"]')
                        ?.content ?? "",
            },
            body: JSON.stringify({ toSign }),
            credentials: "same-origin",
        })
            .then((res) => res.text())
            .then((sig) => (sig.trim() ? resolve(sig) : reject("Signature kosong")))
            .catch(reject);
    });

    if (!qz.websocket.isActive()) {
        // Satu percobaan sambung pada satu waktu. Tanpa ini, pendengar otomatis & tombol Print manual yang
        // berjalan bersamaan bisa saling bentrok ("connection attempt already in progress") dan print gagal.
        if (!connecting) {
            connecting = withTimeout(
                qz.websocket.connect(),
                "connect websocket",
            ).finally(() => {
                connecting = null;
            });
        }
        await connecting;
    }

    return qz;
}

/** Apakah printer dengan nama ini ada di komputer ini? (dipakai sebelum mengklaim pekerjaan print) */
export async function qzHasPrinter(printerName) {
    const qz = await ensureQzConnected();
    try {
        const found = await withTimeout(
            qz.printers.find(printerName || QZ_PRINTER_NAME),
            "cari printer",
        );
        return Boolean(found) && (!Array.isArray(found) || found.length > 0);
    } catch (err) {
        return false;
    }
}

/**
 * Kirim struk (invoice tertentu) ke printer lewat QZ Tray.
 * Dipakai dari halaman Print.jsx (klik manual) DAN dari pendengar print otomatis
 * (dipicu event dari HP, lihat Utils/printListenerRuntime.js).
 */
export async function printReceiptViaQz({
    invoice,
    size = "58",
    printerName,
}) {
    const qz = await ensureQzConnected();

    const printer = await withTimeout(
        qz.printers.find(printerName || QZ_PRINTER_NAME),
        "cari printer",
    );

    const config = qz.configs.create(printer);

    const rawUrl = route("documents.transactions.receipt-raw", {
        invoice,
        size,
    });

    const rawResponse = await withTimeout(
        fetch(rawUrl, { credentials: "same-origin" }),
        "ambil teks struk",
    );

    if (!rawResponse.ok) {
        throw new Error(
            `Gagal ambil teks struk (status ${rawResponse.status}). Pastikan kamu masih login.`,
        );
    }

    const rawText = await rawResponse.text();

    const data = [
        {
            type: "raw",
            format: "plain",
            data: rawText,
        },
    ];

    await withTimeout(qz.print(config, data), "kirim ke printer", 15000);
}
