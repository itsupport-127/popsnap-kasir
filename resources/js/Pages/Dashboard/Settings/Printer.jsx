import DashboardLayout from "@/Layouts/DashboardLayout";
import { Head, useForm } from "@inertiajs/react";
import Button from "@/Components/Dashboard/Button";
import toast from "react-hot-toast";
import {
    IconDeviceFloppy,
    IconPrinter,
    IconFileInvoice,
    IconReceipt,
    IconTruck,
} from "@tabler/icons-react";

const VIEW_OPTIONS = [
    { value: "thermal58", label: "Struk 58mm", icon: IconReceipt },
    { value: "thermal80", label: "Struk 80mm", icon: IconReceipt },
    { value: "invoice", label: "Invoice", icon: IconFileInvoice },
    { value: "shipping", label: "Resi", icon: IconTruck },
];

export default function Printer({ settings }) {
    const { data, setData, post, processing, errors } = useForm({
        default_view: settings?.defaultView || "thermal58",
        printer_name: settings?.printerName || "POS-58",
    });

    const submit = (e) => {
        e.preventDefault();
        post(route("settings.printer.update"), {
            preserveScroll: true,
            onSuccess: () => toast.success("Pengaturan printer disimpan"),
            onError: () => toast.error("Gagal menyimpan pengaturan printer"),
        });
    };

    return (
        <>
            <Head title="Pengaturan Printer" />

            <div className="space-y-6">
                <div>
                    <h1 className="text-2xl font-bold text-slate-900 dark:text-white">
                        Printer
                    </h1>
                    <p className="text-sm text-slate-500 dark:text-slate-400">
                        Atur tampilan default halaman print dan printer struk
                    </p>
                </div>

                <form
                    onSubmit={submit}
                    className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-6 space-y-8"
                >
                    {/* Default view */}
                    <div>
                        <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1">
                            Tampilan default halaman print
                        </label>
                        <p className="text-xs text-slate-500 dark:text-slate-400 mb-3">
                            Pilihan yang otomatis terbuka saat halaman print
                            transaksi dibuka. Di halaman print, pilihan ini
                            tetap bisa diganti manual.
                        </p>
                        <div className="grid grid-cols-2 md:grid-cols-4 gap-3">
                            {VIEW_OPTIONS.map(({ value, label, icon: Icon }) => {
                                const active = data.default_view === value;
                                return (
                                    <button
                                        key={value}
                                        type="button"
                                        onClick={() =>
                                            setData("default_view", value)
                                        }
                                        className={`flex flex-col items-center gap-2 rounded-xl border-2 px-3 py-4 text-sm font-medium transition-all ${
                                            active
                                                ? "border-primary-500 bg-primary-50 text-primary-700 dark:bg-primary-950/30 dark:text-primary-300"
                                                : "border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-300 hover:border-slate-300"
                                        }`}
                                    >
                                        <Icon size={24} />
                                        {label}
                                    </button>
                                );
                            })}
                        </div>
                        {errors.default_view && (
                            <p className="mt-1 text-sm text-danger-500">
                                {errors.default_view}
                            </p>
                        )}
                    </div>

                    {/* Printer name */}
                    <div className="flex items-start gap-4">
                        <div className="p-3 rounded-xl bg-primary-100 dark:bg-primary-900/30">
                            <IconPrinter
                                size={24}
                                className="text-primary-600 dark:text-primary-400"
                            />
                        </div>
                        <div className="flex-1">
                            <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Nama printer (QZ Tray)
                            </label>
                            <input
                                type="text"
                                value={data.printer_name}
                                onChange={(e) =>
                                    setData("printer_name", e.target.value)
                                }
                                placeholder="Contoh: POS-58"
                                className="w-full h-12 px-4 rounded-xl border-2 border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-900 dark:text-white placeholder-slate-400 focus:border-primary-500 focus:ring-4 focus:ring-primary-500/20 transition-all"
                            />
                            <p className="mt-2 text-xs text-slate-500 dark:text-slate-400">
                                Harus sama persis dengan nama printer di
                                Windows / QZ Tray. Dipakai untuk tombol Print
                                Langsung dan Print dari HP.
                            </p>
                            {errors.printer_name && (
                                <p className="mt-1 text-sm text-danger-500">
                                    {errors.printer_name}
                                </p>
                            )}
                        </div>
                    </div>

                    <div className="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-800">
                        <Button
                            type="submit"
                            variant="primary"
                            disabled={processing}
                            className="flex items-center gap-2"
                        >
                            <IconDeviceFloppy size={18} />
                            {processing ? "Menyimpan..." : "Simpan"}
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

Printer.layout = (page) => <DashboardLayout children={page} />;
