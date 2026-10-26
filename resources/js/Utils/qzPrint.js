// Nama printer persis seperti yang muncul di Windows / QZ Tray
// Nilai default; nama printer sebenarnya diatur di Pengaturan → Printer.
export const QZ_PRINTER_NAME = "POS-58";

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
 * Kirim struk (invoice tertentu) ke printer lewat QZ Tray.
 * Dipakai dari halaman Print.jsx (klik manual) DAN dari PrintListener.jsx
 * (otomatis, dipicu event dari HP).
 */
export async function printReceiptViaQz({
    invoice,
    size = "58",
    printerName,
}) {
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
        await withTimeout(qz.websocket.connect(), "connect websocket");
    }

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
