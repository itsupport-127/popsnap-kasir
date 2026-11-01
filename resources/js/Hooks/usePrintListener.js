import { useEffect } from "react";
import { usePage } from "@inertiajs/react";
import { can } from "@/Utils/authorization";
import { startPrintListener } from "@/Utils/printListenerRuntime";

/**
 * Pasang di layout dashboard: PC kasir yang sedang membuka halaman dashboard apa pun
 * (sudah login, QZ Tray menyala) otomatis menerima & mencetak perintah "Print dari HP".
 */
export default function usePrintListener() {
    const { auth } = usePage().props;
    const allowed = can("transactions-access", auth);

    useEffect(() => {
        if (allowed) startPrintListener();
    }, [allowed]);
}
