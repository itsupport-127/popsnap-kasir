// Jalankan: node --test tests/js/
import test from "node:test";
import assert from "node:assert/strict";
import { createPrintListener, LOCK_NAME } from "../../resources/js/Utils/printListener.js";

const tick = () => new Promise((r) => setImmediate(r));

function fakes(over = {}) {
    const calls = { claim: [], finish: [], print: [], states: [], timers: [], repeats: [], locks: [] };
    const channel = {
        handlers: {},
        subscribed(cb) { this.onSubscribed = cb; },
        error(cb) { this.onError = cb; },
        listen(evt, cb) { this.handlers[evt] = cb; },
    };
    const deps = {
        echo: { private: (name) => { calls.channelName = name; return channel; } },
        locks: { request: (name, cb) => { calls.locks.push(name); return cb(); } },
        ensureQz: async () => true,
        api: {
            claim: async (id) => { calls.claim.push(id); return { claimed: true, invoice: "TRX-1", size: "58", printer_name: "POS-58" }; },
            finish: async (id, body) => { calls.finish.push([id, body]); },
            pending: async () => [],
        },
        print: async (p) => { calls.print.push(p); },
        onState: (s) => calls.states.push(s),
        setTimer: (fn) => calls.timers.push(fn),
        setRepeat: (fn) => calls.repeats.push(fn),
        ...over,
    };
    return { deps, calls, channel };
}

test("mengklaim, mencetak dengan nama printer dari server, lalu melapor selesai", async () => {
    const { deps, calls } = fakes();
    const l = createPrintListener(deps);
    assert.equal(await l.handleJob({ job_id: 7, invoice: "TRX-1", size: "58" }), "printed");
    assert.deepEqual(calls.claim, [7]);
    assert.deepEqual(calls.print, [{ invoice: "TRX-1", size: "58", printerName: "POS-58" }]);
    assert.deepEqual(calls.finish, [[7, { status: "printed" }]]);
});

test("QZ Tray mati: tidak mengklaim (biar PC lain yang mengambil) dan boleh dicoba lagi nanti", async () => {
    let qz = false;
    const { deps, calls } = fakes({ ensureQz: async () => qz });
    const l = createPrintListener(deps);
    assert.equal(await l.handleJob({ job_id: 8 }), "no-qz");
    assert.equal(calls.claim.length, 0);
    assert.equal(calls.print.length, 0);
    qz = true;
    assert.equal(await l.handleJob({ job_id: 8 }), "printed"); // QZ hidup lagi: pekerjaan yang sama bisa diambil
});

test("kalah klaim dari PC/tab lain: tidak mencetak", async () => {
    const { deps, calls } = fakes();
    deps.api.claim = async (id) => { calls.claim.push(id); return { claimed: false }; };
    const l = createPrintListener(deps);
    assert.equal(await l.handleJob({ job_id: 9 }), "lost");
    assert.equal(calls.print.length, 0);
    assert.equal(calls.finish.length, 0);
});

test("gagal mencetak: melapor gagal beserta pesannya", async () => {
    const { deps, calls } = fakes();
    deps.print = async () => { throw new Error("Printer POS-58 tidak ditemukan"); };
    const l = createPrintListener(deps);
    assert.equal(await l.handleJob({ job_id: 10 }), "failed");
    assert.deepEqual(calls.finish, [[10, { status: "failed", error: "Printer POS-58 tidak ditemukan" }]]);
});

test("event yang sama datang dua kali: hanya diproses sekali", async () => {
    const { deps, calls } = fakes();
    const l = createPrintListener(deps);
    await l.handleJob({ job_id: 11 });
    assert.equal(await l.handleJob({ job_id: 11 }), "duplicate");
    assert.equal(calls.claim.length, 1);
});

test("jaringan error saat klaim: boleh dicoba lagi lewat pending", async () => {
    const { deps } = fakes();
    let fail = true;
    deps.api.claim = async () => { if (fail) throw new Error("offline"); return { claimed: true, invoice: "TRX-1", size: "58" }; };
    const l = createPrintListener(deps);
    assert.equal(await l.handleJob({ job_id: 12 }), "error");
    fail = false;
    assert.equal(await l.handleJob({ job_id: 12 }), "printed");
});

test("catchUp memproses perintah yang masih menunggu", async () => {
    const { deps, calls } = fakes();
    deps.api.pending = async () => [{ id: 20, invoice: "TRX-A", size: "58" }, { id: 21, invoice: "TRX-B", size: "80" }];
    const l = createPrintListener(deps);
    assert.equal(await l.catchUp(), 2);
    assert.deepEqual(calls.claim, [20, 21]);
});

test("tanpa Echo (Reverb belum diisi): status disabled, tidak ada yang jalan", async () => {
    const { deps, calls } = fakes({ echo: null });
    createPrintListener(deps).start();
    assert.deepEqual(calls.states, ["disabled"]);
    assert.equal(calls.locks.length, 0);
});

test("start(): menunggu lock, tunggu QZ, lalu subscribe dan menerima event", async () => {
    let qz = false;
    const { deps, calls, channel } = fakes({ ensureQz: async () => qz });
    deps.api.pending = async () => [{ id: 30, invoice: "TRX-P", size: "58" }];
    const l = createPrintListener(deps);
    l.start();
    l.start(); // dipanggil dua kali (layout render ulang): tetap satu pendengar
    await tick(); await tick();
    assert.deepEqual(calls.locks, [LOCK_NAME]);
    assert.equal(calls.states.at(-1), "no-qz");
    assert.equal(calls.channelName, undefined, "belum boleh subscribe sebelum QZ siap");

    qz = true;
    calls.timers.shift()(); // jam coba-lagi berjalan
    await tick(); await tick();
    assert.equal(calls.channelName, "print-jobs");

    channel.onSubscribed(); // websocket tersambung -> status connected + ambil yang menunggu
    await tick(); await tick();
    assert.equal(calls.states.at(-1), "connected");
    assert.deepEqual(calls.claim, [30]);

    channel.handlers[".PrintJobRequested"]({ job_id: 31, invoice: "TRX-Q", size: "58" });
    await tick(); await tick();
    assert.deepEqual(calls.claim, [30, 31]);
    assert.equal(calls.repeats.length, 1, "ada pengecekan berkala sebagai jaring pengaman");
});

test("browser tanpa Web Locks: tetap jalan langsung", async () => {
    const { deps, calls } = fakes({ locks: null });
    createPrintListener(deps).start();
    await tick(); await tick();
    assert.equal(calls.channelName, "print-jobs");
});
