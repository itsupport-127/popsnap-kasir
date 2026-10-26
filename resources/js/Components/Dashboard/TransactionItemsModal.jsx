import React from "react";
import { IconX, IconPhoto } from "@tabler/icons-react";

const formatCurrency = (value = 0) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(value);

/**
 * Modal detail item — dipakai di halaman Riwayat Transaksi, Laporan Penjualan,
 * dan Laporan Profit. Menampilkan isi barang/jasa dari satu transaksi
 * (nama, qty x harga satuan, dan subtotal per baris) plus total keseluruhan.
 */
const TransactionItemsModal = ({ open, onClose, transaction }) => {
    if (!open || !transaction) return null;

    const details = transaction.details ?? [];

    // PENTING: discount_total per baris itu GABUNGAN (promo otomatis +
    // voucher + diskon manual per-item). Kalau langsung dijumlah lalu
    // voucherDiscountTotal ditambah LAGI di bawah, voucher-nya kehitung
    // 2x (dobel) — itu sebabnya dulu "Diskon Promo" dan "Voucher" bisa
    // nampilin angka yang sama padahal harusnya beda porsi. Sekarang porsi
    // promo OTOMATIS murni dihitung dengan mengurangi voucher_discount &
    // item_manual_discount dari discount_total tiap baris dulu.
    const promoDiscountTotal = details.reduce((sum, d) => {
        const combined = Number(d.discount_total ?? 0);
        const voucherPart = Number(d.voucher_discount ?? 0);
        const itemManualPart = Number(d.item_manual_discount ?? 0);
        return sum + Math.max(0, combined - voucherPart - itemManualPart);
    }, 0);
    const itemManualDiscountTotal = details.reduce(
        (sum, d) => sum + Number(d.item_manual_discount ?? 0),
        0,
    );
    const voucherDiscountTotal = Number(transaction.customer_voucher_discount ?? 0);
    const manualDiscountTotal = Number(transaction.discount ?? 0);
    const totalDiscount =
        promoDiscountTotal + voucherDiscountTotal + itemManualDiscountTotal + manualDiscountTotal;

    const total =
        transaction.grand_total ??
        details.reduce(
            (sum, d) => sum + Math.max(0, (d.price ?? 0) - (d.discount_total ?? 0)),
            0,
        );

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div
                className="absolute inset-0 bg-black/50 backdrop-blur-sm"
                onClick={onClose}
            />
            <div className="relative bg-white dark:bg-slate-900 rounded-2xl shadow-2xl w-full max-w-md overflow-hidden max-h-[85vh] flex flex-col">
                {/* Header */}
                <div className="bg-gradient-to-r from-primary-700 to-primary-800 px-6 py-5 text-white flex items-start justify-between shrink-0">
                    <div>
                        <h3 className="text-lg font-bold">Detail Item</h3>
                        <p className="text-sm opacity-80">
                            {transaction.invoice}
                        </p>
                    </div>
                    <button
                        onClick={onClose}
                        className="text-white/80 hover:text-white transition-colors"
                    >
                        <IconX size={20} />
                    </button>
                </div>

                {/* Item list */}
                <div className="p-4 space-y-2 overflow-y-auto">
                    {details.length > 0 ? (
                        details.map((detail) => {
                            const lineDiscount = Number(detail.discount_total ?? 0);
                            // PENTING: detail.price di database itu harga
                            // KOTOR/SEBELUM diskon (line_total pas checkout),
                            // BUKAN harga bersih setelah diskon. Sebelumnya
                            // kode ini salah asumsi detail.price = harga
                            // bersih, jadi "harga sebelum diskon" dihitung
                            // dobel (price + discount lagi), dan "harga
                            // akhir" malah nampilin harga kotornya, bukan
                            // yang beneran dibayar.
                            const grossPrice = Number(detail.price ?? 0);
                            const netPrice = Math.max(0, grossPrice - lineDiscount);

                            return (
                                <div
                                    key={detail.id}
                                    className="flex items-center gap-3 p-2 rounded-xl bg-slate-50 dark:bg-slate-800/60"
                                >
                                    <div className="w-12 h-12 rounded-lg overflow-hidden bg-slate-200 dark:bg-slate-700 shrink-0 flex items-center justify-center">
                                        {detail.product?.image ? (
                                            <img
                                                src={detail.product.image}
                                                alt={detail.product?.title ?? ""}
                                                className="w-full h-full object-cover"
                                            />
                                        ) : (
                                            <IconPhoto
                                                size={20}
                                                className="text-slate-400"
                                            />
                                        )}
                                    </div>
                                    <div className="flex-1 min-w-0">
                                        <p className="text-sm font-semibold text-slate-900 dark:text-white truncate">
                                            {detail.product?.title ??
                                                "Produk dihapus"}
                                        </p>
                                        <p className="text-xs text-slate-500 dark:text-slate-400">
                                            {detail.qty} x{" "}
                                            {formatCurrency(
                                                detail.unit_price ??
                                                    detail.base_unit_price ??
                                                    0
                                            )}
                                        </p>
                                        {lineDiscount > 0 && (
                                            <p className="text-xs text-emerald-600 dark:text-emerald-400">
                                                {detail.pricing_rule_name
                                                    ? `${detail.pricing_rule_name} · `
                                                    : ""}
                                                Diskon -{formatCurrency(lineDiscount)}
                                            </p>
                                        )}
                                    </div>
                                    <div className="text-right shrink-0">
                                        {lineDiscount > 0 && (
                                            <p className="text-xs text-slate-400 line-through">
                                                {formatCurrency(grossPrice)}
                                            </p>
                                        )}
                                        <p className="text-sm font-semibold text-slate-900 dark:text-white">
                                            {formatCurrency(netPrice)}
                                        </p>
                                    </div>
                                </div>
                            );
                        })
                    ) : (
                        <p className="text-sm text-slate-500 dark:text-slate-400 text-center py-6">
                            Tidak ada data item.
                        </p>
                    )}
                </div>

                {/* Ringkasan diskon + Total */}
                <div className="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50 dark:bg-slate-800/60 shrink-0 space-y-1.5">
                    {totalDiscount > 0 && (
                        <>
                            {promoDiscountTotal > 0 && (
                                <div className="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                                    <span>Diskon Promo</span>
                                    <span>-{formatCurrency(promoDiscountTotal)}</span>
                                </div>
                            )}
                            {voucherDiscountTotal > 0 && (
                                <div className="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                                    <span>
                                        Voucher
                                        {transaction.customer_voucher_code
                                            ? ` (${transaction.customer_voucher_code})`
                                            : ""}
                                    </span>
                                    <span>-{formatCurrency(voucherDiscountTotal)}</span>
                                </div>
                            )}
                            {itemManualDiscountTotal > 0 && (
                                <div className="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                                    <span>Diskon Per Item</span>
                                    <span>-{formatCurrency(itemManualDiscountTotal)}</span>
                                </div>
                            )}
                            {manualDiscountTotal > 0 && (
                                <div className="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400">
                                    <span>Diskon Manual</span>
                                    <span>-{formatCurrency(manualDiscountTotal)}</span>
                                </div>
                            )}
                        </>
                    )}
                    <div className="flex items-center justify-between pt-1">
                        <span className="text-sm text-slate-500 dark:text-slate-400">
                            Total
                        </span>
                        <span className="text-lg font-bold text-slate-900 dark:text-white">
                            {formatCurrency(total)}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default TransactionItemsModal;
