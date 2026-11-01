/**
 * Pendengar print otomatis (HP -> PC kasir), tanpa framework supaya bisa diuji sendiri.
 *
 * Aturannya:
 *  - Jalan di halaman dashboard / layar kasir MANA PUN selama user login & punya akses transaksi.
 *    Tidak perlu tab khusus.
 *  - Hanya komputer yang QZ Tray-nya tersambung DAN printernya ada yang ikut mengambil pekerjaan.
 *    HP / PC tanpa QZ / PC tanpa printer itu diam saja, jadi tidak pernah "merebut" pekerjaan yang tidak
 *    bisa dicetaknya (yang membuat PC kasir sebenarnya kalah balapan).
 *  - Satu browser = satu pendengar (Web Locks). Tab lain menunggu giliran dan mengambil alih kalau tab itu ditutup.
 *  - Sebelum mencetak, pekerjaan diklaim ke server (atomik). Kalau ada beberapa PC/tab, hanya satu yang menang,
 *    jadi struk tidak tercetak dobel.
 *  - Jaring pengaman: "pending" dicek berkala SELAMA jadi pendengar utama, tidak peduli websocket sehat atau
 *    tidak. Jadi kalau server Reverb mati / websocket putus diam-diam, print tetap jalan (tertunda <= ~20 detik).
 */
export const LOCK_NAME = "popsnap-print-listener";
export const POLL_MS = 20000;
export const QZ_RETRY_MS = 30000;

const isSessionError = (err) => err?.code === "SESSION";

export function createPrintListener(deps) {
    const {
        echo, // window.Echo atau null
        locks, // navigator.locks atau null
        ensureQz, // async () => boolean : QZ Tray siap?
        hasPrinter = async () => true, // async (printerName) => boolean : printer ada di komputer ini?
        api, // { claim(id), finish(id, body), pending() }
        print, // async ({ invoice, size, printerName }) => void
        onState = () => {},
        onJob = () => {},
        setTimer = setTimeout,
        setRepeat = setInterval,
        qzRetryMs = QZ_RETRY_MS,
        pollMs = POLL_MS,
    } = deps;

    let started = false;
    const seen = new Set(); // job yang sudah ditangani tab ini
    let state = "idle";

    const setState = (next) => {
        if (next === state) return;
        state = next;
        onState(next);
    };

    /** Tangani satu pekerjaan. Mengembalikan hasilnya (dipakai tes & log). */
    async function handleJob(job) {
        const id = Number(job?.job_id ?? job?.id);
        if (!id) return "ignored";
        if (seen.has(id)) return "duplicate";
        seen.add(id);

        // QZ mati / printer tidak ada di komputer ini? Jangan klaim — biarkan PC yang mampu mengambilnya.
        if (!(await ensureQz())) {
            seen.delete(id);
            setState("no-qz");
            return "no-qz";
        }
        let printerOk = false;
        try {
            printerOk = await hasPrinter(job?.printer_name);
        } catch (err) {
            printerOk = false;
        }
        if (!printerOk) {
            seen.delete(id);
            return "no-printer";
        }

        let claim;
        try {
            claim = await api.claim(id);
        } catch (err) {
            seen.delete(id); // jaringan / sesi bermasalah: boleh dicoba lagi lewat pending
            if (isSessionError(err)) setState("session-expired");
            return "error";
        }
        if (!claim?.claimed) return "lost"; // PC/tab lain sudah mengambil

        try {
            await print({
                invoice: claim.invoice ?? job.invoice,
                size: claim.size ?? job.size ?? "58",
                printerName: claim.printer_name,
            });
            await api.finish(id, { status: "printed" });
            onJob({ id, invoice: claim.invoice ?? job.invoice, ok: true });
            return "printed";
        } catch (err) {
            const message = err?.message || String(err) || "Gagal mencetak";
            try {
                await api.finish(id, { status: "failed", error: message });
            } catch (e) {
                /* laporan gagal terkirim: server menandainya gagal sendiri setelah 2 menit */
            }
            onJob({ id, invoice: claim.invoice ?? job.invoice, ok: false, error: message });
            return "failed";
        }
    }

    async function catchUp() {
        let jobs = [];
        try {
            jobs = (await api.pending()) ?? [];
        } catch (err) {
            if (isSessionError(err)) setState("session-expired"); // login habis: perlu login ulang
            return 0;
        }
        if (state === "session-expired") setState("connected"); // sudah login lagi (mis. di tab lain)
        for (const job of jobs) await handleJob(job);
        return jobs.length;
    }

    async function waitForQz() {
        // QZ Tray mungkin baru dijalankan nanti: coba lagi berkala, tanpa menyibukkan CPU.
        for (;;) {
            if (await ensureQz()) return;
            setState("no-qz");
            await new Promise((resolve) => setTimer(resolve, qzRetryMs));
        }
    }

    /** Dipanggil begitu tab ini jadi pendengar utama. Tidak pernah selesai (memegang lock sampai tab ditutup). */
    async function lead() {
        try {
            setState("connecting");
            await waitForQz();

            const channel = echo.private("print-jobs");
            channel.subscribed(() => {
                setState("connected");
                catchUp();
            });
            channel.error(() => setState("error"));
            channel.listen(".PrintJobRequested", (payload) => {
                handleJob(payload);
            });

            // Jaring pengaman: jalan terus walau websocket belum/tidak tersambung, dan browser memperlambat
            // timer tab background. Murah: satu GET ringan tiap ~20 detik.
            setRepeat(() => {
                catchUp();
            }, pollMs);
            catchUp(); // jangan menunggu websocket untuk pekerjaan yang sudah menunggu
            if (typeof document !== "undefined") {
                document.addEventListener("visibilitychange", () => {
                    if (document.visibilityState === "visible") catchUp();
                });
            }
        } catch (err) {
            setState("error"); // mis. Echo gagal dibuat; lock dilepas supaya tab lain bisa mencoba
            return;
        }

        await new Promise(() => {}); // tahan lock
    }

    function start() {
        if (started) return;
        started = true;

        if (!echo) {
            setState("disabled"); // VITE_REVERB_* belum diisi
            return;
        }
        if (!locks) {
            lead(); // browser lama tanpa Web Locks; klaim atomik di server tetap mencegah dobel
            return;
        }
        setState("standby"); // menunggu giliran jadi pendengar utama
        locks.request(LOCK_NAME, () => lead());
    }

    return { start, handleJob, catchUp, getState: () => state };
}
