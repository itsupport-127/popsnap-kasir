import React, { useEffect, useState } from "react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import { Head, useForm, usePage, Link, router } from "@inertiajs/react";
import Input from "@/Components/Dashboard/Input";
import Textarea from "@/Components/Dashboard/TextArea";
import InputSelect from "@/Components/Dashboard/InputSelect";
import toast from "react-hot-toast";
import {
    IconPackage,
    IconDeviceFloppy,
    IconArrowLeft,
    IconPhoto,
    IconBarcode,
    IconCurrencyDollar,
    IconPlus,
    IconMinus,
    IconX,
    IconAlertTriangle,
    IconHistory,
} from "@tabler/icons-react";
import { getProductImageUrl } from "@/Utils/imageUrl";

export default function Edit({ categories, product }) {
    const { errors } = usePage().props;

    const { data, setData, post, processing } = useForm({
        image: "",
        barcode: product.barcode,
        sku: product.sku,
        title: product.title,
        category_id: product.category_id,
        description: product.description,
        buy_price: product.buy_price,
        sell_price: product.sell_price,
        is_sellable: product.is_sellable ?? true,
        _method: "PUT",
    });

    const [selectedCategory, setSelectedCategory] = useState(null);
    const [stockModal, setStockModal] = useState({ open: false, type: "in" });
    const [stockQty, setStockQty] = useState("");
    const [stockNotes, setStockNotes] = useState("");
    const [stockReasons] = useState([
        "Restock dari supplier", "Bonus dari supplier", "Koreksi stok",
        "Barang rusak", "Barang hilang/menyusut", "Sampel/tester", "Lainnya",
    ]);
    const [isAdjusting, setIsAdjusting] = useState(false);

    const handleAdjustStock = () => {
        if (!stockQty || Number(stockQty) <= 0) {
            alert("Isi jumlah stok yang valid");
            return;
        }
        setIsAdjusting(true);
        router.post(
            route("products.adjust-stock", product.id),
            {
                type: stockModal.type,
                qty: Number(stockQty),
                notes: stockNotes,
            },
            {
                onSuccess: () => {
                    setStockModal({ open: false, type: "in" });
                    setStockQty("");
                    setStockNotes("");
                    setIsAdjusting(false);
                },
                onError: () => setIsAdjusting(false),
            }
        );
    };
    const [imagePreview, setImagePreview] = useState(
        product.image ? getProductImageUrl(product.image) : null
    );

    useEffect(() => {
        if (product.category_id) {
            setSelectedCategory(
                categories.find((cat) => cat.id === product.category_id)
            );
        }
    }, [product.category_id]);

    const setSelectedCategoryHandler = (value) => {
        setSelectedCategory(value);
        setData("category_id", value?.id || "");
    };

    const handleImageChange = (e) => {
        const file = e.target.files[0];
        if (file) {
            setData("image", file);
            setImagePreview(URL.createObjectURL(file));
        }
    };

    const submit = (e) => {
        e.preventDefault();
        post(route("products.update", product.id), {
            onSuccess: () => toast.success("Produk berhasil diperbarui"),
            onError: () => toast.error("Gagal memperbarui produk"),
        });
    };

    return (
        <>
            <Head title="Edit Produk" />

            <div className="mb-6">
                <Link
                    href={route("products.index")}
                    className="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-primary-600 mb-3"
                >
                    <IconArrowLeft size={16} />
                    Kembali ke Produk
                </Link>
                <h1 className="text-2xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                    <IconPackage size={28} className="text-primary-500" />
                    Edit Produk
                </h1>
                <p className="text-sm text-slate-500 mt-1">{product.title}</p>
            </div>

            <form onSubmit={submit}>
                <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {/* Left - Image */}
                    <div className="lg:col-span-1">
                        <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5">
                            <h3 className="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4 flex items-center gap-2">
                                <IconPhoto size={18} />
                                Gambar Produk
                            </h3>
                            <div className="aspect-square rounded-xl bg-slate-100 dark:bg-slate-800 border-2 border-dashed border-slate-300 dark:border-slate-700 flex items-center justify-center overflow-hidden mb-4">
                                {imagePreview ? (
                                    <img
                                        src={imagePreview}
                                        alt="Preview"
                                        className="w-full h-full object-cover"
                                    />
                                ) : (
                                    <div className="text-center p-6">
                                        <IconPhoto
                                            size={48}
                                            className="mx-auto text-slate-400 mb-2"
                                        />
                                        <p className="text-sm text-slate-500">
                                            Belum ada gambar
                                        </p>
                                    </div>
                                )}
                            </div>
                            <Input
                                type="file"
                                label="Ganti Gambar"
                                onChange={handleImageChange}
                                errors={errors.image}
                                accept="image/*"
                            />
                        </div>
                    </div>

                    {/* Right - Form */}
                    <div className="lg:col-span-2 space-y-6">
                        <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5">
                            <h3 className="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4 flex items-center gap-2">
                                <IconBarcode size={18} />
                                Informasi Dasar
                            </h3>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div className="md:col-span-2">
                                    <InputSelect
                                        label="Kategori"
                                        data={categories}
                                        selected={selectedCategory}
                                        setSelected={setSelectedCategoryHandler}
                                        placeholder="Pilih kategori"
                                        errors={errors.category_id}
                                        searchable={true}
                                        displayKey="name"
                                    />
                                </div>
                                <Input
                                    type="text"
                                    label="Barcode"
                                    value={data.barcode}
                                    onChange={(e) =>
                                        setData("barcode", e.target.value)
                                    }
                                    errors={errors.barcode}
                                    placeholder="Kode produk"
                                />
                                <Input
                                    type="text"
                                    label="SKU"
                                    value={data.sku}
                                    onChange={(e) => setData("sku", e.target.value)}
                                    errors={errors.sku}
                                    placeholder="SKU unik"
                                />
                                <Input
                                    type="text"
                                    label="Nama Produk"
                                    value={data.title}
                                    onChange={(e) =>
                                        setData("title", e.target.value)
                                    }
                                    errors={errors.title}
                                    placeholder="Nama produk"
                                />
                                <div className="md:col-span-2">
                                    <Textarea
                                        label="Deskripsi"
                                        placeholder="Deskripsi produk"
                                        errors={errors.description}
                                        onChange={(e) =>
                                            setData(
                                                "description",
                                                e.target.value
                                            )
                                        }
                                        value={data.description}
                                        rows={3}
                                    />
                                </div>
                            </div>
                        </div>

                        <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 p-5">
                            <h3 className="text-sm font-semibold text-slate-700 dark:text-slate-300 mb-4 flex items-center gap-2">
                                <IconCurrencyDollar size={18} />
                                Harga Produk
                            </h3>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <Input
                                    type="number"
                                    label="Harga Beli"
                                    value={data.buy_price}
                                    onChange={(e) =>
                                        setData("buy_price", e.target.value)
                                    }
                                    errors={errors.buy_price}
                                    placeholder="0"
                                />
                                <Input
                                    type="number"
                                    label="Harga Jual"
                                    value={data.sell_price}
                                    onChange={(e) =>
                                        setData("sell_price", e.target.value)
                                    }
                                    errors={errors.sell_price}
                                    placeholder="0"
                                />
                            </div>

                            <div className="mt-4 flex items-start gap-3 rounded-xl border border-slate-200 dark:border-slate-700 p-4">
                                <input
                                    type="checkbox"
                                    id="is_sellable"
                                    checked={data.is_sellable}
                                    onChange={(e) =>
                                        setData("is_sellable", e.target.checked)
                                    }
                                    className="mt-0.5 h-4 w-4 rounded border-slate-300 text-primary-500 focus:ring-primary-500"
                                />
                                <label htmlFor="is_sellable" className="text-sm">
                                    <span className="block font-medium text-slate-700 dark:text-slate-300">
                                        Bisa Dijual di Kasir
                                    </span>
                                    <span className="block text-slate-500 dark:text-slate-400">
                                        Matikan kalau ini cuma bahan/aksesoris
                                        internal (mis. blank keychain, frame
                                        cadangan) — stoknya tetap dilacak dan
                                        bisa masuk Stock Opname, tapi
                                        disembunyikan dari pencarian produk di
                                        halaman kasir.
                                    </span>
                                </label>
                            </div>

                            <div className="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 dark:border-slate-700 dark:bg-slate-800 space-y-3">
                                <div className="flex items-center justify-between">
                                    <p className="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                        Stok Saat Ini
                                    </p>
                                    <Link
                                        href={route("stock-mutations.index", { product_id: product.id })}
                                        className="inline-flex items-center gap-1 text-xs text-primary-600 dark:text-primary-400 hover:underline"
                                    >
                                        <IconHistory size={12} />
                                        Riwayat
                                    </Link>
                                </div>
                                <p className="text-2xl font-bold text-slate-900 dark:text-slate-100">
                                    {product.stock} <span className="text-sm font-normal text-slate-400">pcs</span>
                                </p>
                                <div className="flex gap-2">
                                    <button
                                        type="button"
                                        onClick={() => setStockModal({ open: true, type: "in" })}
                                        className="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg bg-emerald-100 hover:bg-emerald-200 dark:bg-emerald-900/30 dark:hover:bg-emerald-900/50 text-emerald-700 dark:text-emerald-400 text-sm font-medium transition-colors"
                                    >
                                        <IconPlus size={14} />
                                        Tambah Stok
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => setStockModal({ open: true, type: "out" })}
                                        className="flex-1 inline-flex items-center justify-center gap-1.5 px-3 py-2 rounded-lg bg-rose-100 hover:bg-rose-200 dark:bg-rose-900/30 dark:hover:bg-rose-900/50 text-rose-700 dark:text-rose-400 text-sm font-medium transition-colors"
                                    >
                                        <IconMinus size={14} />
                                        Kurangi Stok
                                    </button>
                                </div>
                            </div>

                            {/* Profit Estimation */}
                            {data.buy_price > 0 && data.sell_price > 0 && (
                                <div className="mt-4 p-4 rounded-xl bg-success-50 dark:bg-success-950/30 border border-success-200 dark:border-success-900">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="text-sm text-success-700 dark:text-success-400 font-medium">
                                                Estimasi Profit per Item
                                            </p>
                                            <p className="text-2xl font-bold text-success-600 dark:text-success-500 mt-1">
                                                + Rp{" "}
                                                {(
                                                    data.sell_price -
                                                    data.buy_price
                                                ).toLocaleString("id-ID")}
                                            </p>
                                        </div>
                                        <div className="text-right">
                                            <p className="text-sm text-success-700 dark:text-success-400 font-medium">
                                                Margin
                                            </p>
                                            <p className="text-xl font-bold text-success-600 dark:text-success-500 mt-1">
                                                {(
                                                    ((data.sell_price -
                                                        data.buy_price) /
                                                        data.buy_price) *
                                                    100
                                                ).toFixed(1)}
                                                %
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            )}
                        </div>

                        <div className="flex justify-end gap-3">
                            <Link
                                href={route("products.index")}
                                className="px-6 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800 font-medium transition-colors"
                            >
                                Batal
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-primary-500 hover:bg-primary-600 text-white font-medium transition-colors disabled:opacity-50"
                            >
                                <IconDeviceFloppy size={18} />
                                {processing
                                    ? "Menyimpan..."
                                    : "Simpan Perubahan"}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            {/* ===== MODAL TAMBAH/KURANGI STOK ===== */}
            {stockModal.open && (
                <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
                    <div
                        className="absolute inset-0 bg-black/50 backdrop-blur-sm"
                        onClick={() => !isAdjusting && setStockModal({ open: false, type: "in" })}
                    />
                    <div className="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
                        {/* Header */}
                        <div className={`px-6 py-5 text-white ${stockModal.type === "in" ? "bg-gradient-to-r from-emerald-600 to-emerald-700" : "bg-gradient-to-r from-rose-600 to-rose-700"}`}>
                            <div className="flex items-center justify-between">
                                <div className="flex items-center gap-2">
                                    {stockModal.type === "in"
                                        ? <IconPlus size={20} />
                                        : <IconMinus size={20} />
                                    }
                                    <h3 className="text-lg font-bold">
                                        {stockModal.type === "in" ? "Tambah Stok" : "Kurangi Stok"}
                                    </h3>
                                </div>
                                <button onClick={() => setStockModal({ open: false, type: "in" })} disabled={isAdjusting}>
                                    <IconX size={20} className="opacity-80 hover:opacity-100" />
                                </button>
                            </div>
                            <p className="text-sm opacity-80 mt-1">{product.title}</p>
                        </div>

                        {/* Content */}
                        <div className="p-6 space-y-4">
                            {/* Current stock info */}
                            <div className="flex items-center justify-between p-3 rounded-xl bg-slate-50 dark:bg-slate-800 text-sm">
                                <span className="text-slate-500">Stok saat ini</span>
                                <span className="font-bold text-slate-800 dark:text-white">{product.stock} pcs</span>
                            </div>

                            {/* Preview */}
                            {stockQty > 0 && (
                                <div className={`flex items-center justify-between p-3 rounded-xl text-sm font-medium ${
                                    stockModal.type === "in"
                                        ? "bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400"
                                        : "bg-rose-50 dark:bg-rose-900/20 text-rose-700 dark:text-rose-400"
                                }`}>
                                    <span>Stok setelah</span>
                                    <span className="font-bold text-lg">
                                        {stockModal.type === "in"
                                            ? product.stock + Number(stockQty)
                                            : Math.max(0, product.stock - Number(stockQty))
                                        } pcs
                                    </span>
                                </div>
                            )}

                            {/* Qty input */}
                            <div>
                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                                    Jumlah <span className="text-rose-500">*</span>
                                </label>
                                <input
                                    type="number"
                                    min="1"
                                    autoFocus
                                    value={stockQty}
                                    onChange={(e) => setStockQty(e.target.value)}
                                    placeholder="0"
                                    className="w-full h-11 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none"
                                />
                            </div>

                            {/* Notes */}
                            <div>
                                <label className="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-1.5">
                                    Alasan
                                </label>
                                <div className="grid grid-cols-2 gap-2 mb-2">
                                    {(stockModal.type === "in"
                                        ? ["Restock dari supplier", "Bonus dari supplier", "Koreksi stok", "Lainnya"]
                                        : ["Barang rusak", "Barang hilang", "Sampel/tester", "Koreksi stok"]
                                    ).map((r) => (
                                        <button
                                            key={r}
                                            type="button"
                                            onClick={() => setStockNotes(r)}
                                            className={`px-2 py-1.5 rounded-lg text-xs font-medium border transition-all ${
                                                stockNotes === r
                                                    ? "border-primary-500 bg-primary-50 dark:bg-primary-900/30 text-primary-700 dark:text-primary-300"
                                                    : "border-slate-200 dark:border-slate-700 text-slate-500 hover:border-slate-300"
                                            }`}
                                        >
                                            {r}
                                        </button>
                                    ))}
                                </div>
                                <input
                                    type="text"
                                    value={stockNotes}
                                    onChange={(e) => setStockNotes(e.target.value)}
                                    placeholder="Atau ketik alasan lain..."
                                    className="w-full h-10 px-4 rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-800 dark:text-white text-sm focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 outline-none"
                                />
                            </div>

                            {stockModal.type === "out" && stockQty > product.stock && (
                                <div className="flex items-center gap-2 p-3 rounded-xl bg-rose-50 dark:bg-rose-900/20 border border-rose-200 dark:border-rose-800">
                                    <IconAlertTriangle size={14} className="text-rose-500 shrink-0" />
                                    <p className="text-xs text-rose-600 dark:text-rose-400">Jumlah melebihi stok saat ini</p>
                                </div>
                            )}
                        </div>

                        {/* Footer */}
                        <div className="px-6 pb-6 flex gap-3">
                            <button
                                type="button"
                                onClick={() => { setStockModal({ open: false, type: "in" }); setStockQty(""); setStockNotes(""); }}
                                disabled={isAdjusting}
                                className="flex-1 px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-medium hover:bg-slate-50 dark:hover:bg-slate-800 transition-colors disabled:opacity-50 text-sm"
                            >
                                Batal
                            </button>
                            <button
                                type="button"
                                onClick={handleAdjustStock}
                                disabled={isAdjusting || !stockQty || Number(stockQty) <= 0 || (stockModal.type === "out" && Number(stockQty) > product.stock)}
                                className={`flex-1 px-4 py-3 rounded-xl text-white font-medium transition-colors disabled:opacity-50 text-sm flex items-center justify-center gap-2 ${
                                    stockModal.type === "in"
                                        ? "bg-emerald-500 hover:bg-emerald-600"
                                        : "bg-rose-500 hover:bg-rose-600"
                                }`}
                            >
                                {isAdjusting
                                    ? <div className="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" />
                                    : stockModal.type === "in" ? <><IconPlus size={16} />Tambah</> : <><IconMinus size={16} />Kurangi</>
                                }
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </>
    );
}

Edit.layout = (page) => <DashboardLayout children={page} />;