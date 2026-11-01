import { createPrintListener } from "@/Utils/printListener";
import { ensureQzConnected, printReceiptViaQz, qzHasPrinter } from "@/Utils/qzPrint";

/**
 * Menyambungkan pendengar print ke browser sungguhan (Echo, Web Locks, QZ Tray, API server).
 * Dipanggil sekali dari DashboardLayout; aman dipanggil berulang (singleton).
 */
let listener = null;
let current = { state: "idle", lastJob: null };
const subscribers = new Set();

const emit = () => subscribers.forEach((fn) => fn(current));
const update = (patch) => {
    current = { ...current, ...patch };
    emit();
};

export const getPrintListenerStatus = () => current;
export const subscribePrintListener = (fn) => {
    subscribers.add(fn);
    return () => subscribers.delete(fn);
};

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? "";

async function request(method, url, body) {
    const res = await fetch(url, {
        method,
        credentials: "same-origin",
        headers: {
            Accept: "application/json",
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest",
            "X-CSRF-TOKEN": csrf(),
        },
        body: body ? JSON.stringify(body) : undefined,
    });
    // Sesi login habis: server mengalihkan ke /login (atau 401/419). Jangan dianggap sukses.
    if (res.status === 401 || res.status === 419 || (res.redirected && /\/login/.test(res.url))) {
        const err = new Error("Sesi login habis");
        err.code = "SESSION";
        throw err;
    }
    return res;
}

const deviceName = () => {
    const platform = window.navigator.userAgentData?.platform || window.navigator.platform || "PC";
    return `${platform}`.slice(0, 40);
};

export function startPrintListener() {
    if (listener) return listener;

    listener = createPrintListener({
        echo: window.Echo ?? null,
        locks: window.navigator?.locks ?? null,
        ensureQz: async () => {
            try {
                await ensureQzConnected();
                return true;
            } catch (err) {
                return false;
            }
        },
        api: {
            async claim(id) {
                const res = await request("POST", route("print-relay.claim", id), { device: deviceName() });
                if (res.status === 409) return { claimed: false };
                if (!res.ok) throw new Error(`claim ${res.status}`);
                return res.json();
            },
            async finish(id, body) {
                const res = await request("POST", route("print-relay.finish", id), body);
                if (!res.ok) throw new Error(`finish ${res.status}`);
            },
            async pending() {
                const res = await request("GET", route("print-relay.pending"));
                if (!res.ok) throw new Error(`pending ${res.status}`);
                return (await res.json()).jobs;
            },
        },
        hasPrinter: (printerName) => qzHasPrinter(printerName),
        print: printReceiptViaQz,
        onState: (state) => update({ state }),
        onJob: (job) => update({ lastJob: { ...job, at: Date.now() } }),
    });

    listener.start();
    return listener;
}
