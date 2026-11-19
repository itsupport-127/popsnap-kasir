import { useCallback, useEffect, useRef, useState } from "react";
import { router } from "@inertiajs/react";

/**
 * Live update murni push (websocket / Reverb), tanpa polling dan tanpa reload halaman.
 * Server mengirim sinyal "PhotoboxUpdated" di channel privat; hook ini lalu memuat ulang HANYA prop `only`
 * lewat partial reload Inertia (posisi scroll, filter, dan isian tetap).
 * - sinyal beruntun digabung (debounce) supaya tidak banjir permintaan
 * - kalau `pause` true (dialog / edit terbuka) sinyal ditahan, lalu diproses saat pause selesai
 * - tab tersembunyi: ditahan, dikejar sekali saat tab dibuka lagi
 * - websocket putus lalu tersambung lagi: dikejar sekali supaya tidak ada data terlewat
 * Status: "on" (tersambung) | "connecting" | "off" (Echo/Reverb tidak aktif atau diputus) | "paused" (dimatikan user).
 */
export default function useLiveRefresh({ only, channel = "photobox", event = ".PhotoboxUpdated", pause = false, debounce = 400 }) {
    const [enabled, setEnabled] = useState(() => {
        try { return localStorage.getItem("liveRefresh") !== "off"; } catch { return true; }
    });
    const [conn, setConn] = useState(() => (typeof window !== "undefined" && window.Echo ? "connecting" : "off"));
    const [lastAt, setLastAt] = useState(() => new Date());
    const key = JSON.stringify(only);
    const state = useRef({ pending: false, busy: false, timer: null, pause, enabled });
    state.current.pause = pause;
    state.current.enabled = enabled;

    useEffect(() => {
        try { localStorage.setItem("liveRefresh", enabled ? "on" : "off"); } catch { /* abaikan */ }
    }, [enabled]);

    const flush = useCallback(() => {
        const s = state.current;
        if (!s.pending || s.busy || s.pause || !s.enabled || document.hidden) return;
        s.pending = false;
        s.busy = true;
        router.reload({
            only: JSON.parse(key),
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => setLastAt(new Date()),
            onFinish: () => { s.busy = false; if (s.pending) flush(); },
        });
    }, [key]);

    const request = useCallback(() => {
        const s = state.current;
        s.pending = true;
        clearTimeout(s.timer);
        s.timer = setTimeout(flush, debounce);
    }, [flush, debounce]);

    // pause selesai / diaktifkan lagi -> proses sinyal yang tertahan
    useEffect(() => { if (!pause && enabled) flush(); }, [pause, enabled, flush]);

    useEffect(() => {
        const echo = typeof window !== "undefined" ? window.Echo : null;
        if (!echo) { setConn("off"); return undefined; }
        let ch;
        try {
            ch = echo.private(channel).listen(event, request);
        } catch { setConn("off"); return undefined; }

        const pusher = echo.connector?.pusher;
        const sync = (st) => {
            if (st === "connected") setConn("on");
            else if (st === "connecting" || st === "unavailable") setConn("connecting");
            else setConn("off");
        };
        const onState = ({ previous, current }) => {
            sync(current);
            if (current === "connected" && previous && previous !== "connected") request(); // tersambung ulang -> kejar yang terlewat
        };
        if (pusher?.connection) {
            sync(pusher.connection.state);
            pusher.connection.bind("state_change", onState);
        } else setConn("on");

        const onVisible = () => { if (!document.hidden && state.current.pending) flush(); };
        document.addEventListener("visibilitychange", onVisible);
        return () => {
            clearTimeout(state.current.timer);
            document.removeEventListener("visibilitychange", onVisible);
            pusher?.connection?.unbind("state_change", onState);
            try { ch?.stopListening(event, request); echo.leave(channel); } catch { /* abaikan */ }
        };
    }, [channel, event, request, flush]);

    const status = !enabled ? "paused" : conn;
    return { live: enabled && conn === "on", status, enabled, setEnabled, lastAt };
}
