import React from "react";
import { Head, Link } from "@inertiajs/react";
import DashboardLayout from "@/Layouts/DashboardLayout";
import Table from "@/Components/Dashboard/Table";
import { IconArrowLeft, IconReceipt } from "@tabler/icons-react";

const formatCurrency = (value = 0) =>
    new Intl.NumberFormat("id-ID", {
        style: "currency",
        currency: "IDR",
        minimumFractionDigits: 0,
    }).format(value);

const formatDate = (value) =>
    value
        ? new Date(value).toLocaleString("id-ID", {
              day: "2-digit",
              month: "short",
              year: "numeric",
              hour: "2-digit",
              minute: "2-digit",
          })
        : "-";

const paymentLabels = {
    cash: "Tunai",
    bank_transfer: "Transfer",
    qris_static: "QRIS",
    dual_cash_qris: "Tunai + QRIS",
    pay_later: "Piutang",
};

export default function Usage({ promo, transactions, summary }) {
    return (
        <DashboardLayout>
            <Head title={`Pemakaian — ${promo.name}`} />

            <div className="mb-6 flex items-start justify-between gap-4 flex-wrap">
                <div>
                    <Link
                        href={route("promos.index")}
                        className="inline-flex items-center gap-1.5 text-sm text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 mb-2"
                    >
                        <IconArrowLeft size={16} />
                        Kembali ke Promo
                    </Link>
                    <h1 className="text-xl font-bold text-slate-900 dark:text-white">
                        Pemakaian: {promo.name}
                    </h1>
                    <p className="text-sm text-slate-500 dark:text-slate-400 font-mono">
                        {promo.code || "(tanpa kode)"} — dipakai {promo.used_count}
                        {promo.max_uses ? ` / ${promo.max_uses}` : "x"}
                    </p>
                </div>

                <div className="flex gap-3">
                    <div className="rounded-xl border border-slate-200 dark:border-slate-700 px-4 py-2 text-right">
                        <p className="text-xs text-slate-500 dark:text-slate-400">
                            Total Transaksi
                        </p>
                        <p className="text-lg font-bold text-slate-900 dark:text-white">
                            {summary.transaction_count}
                        </p>
                    </div>
                    <div className="rounded-xl border border-slate-200 dark:border-slate-700 px-4 py-2 text-right">
                        <p className="text-xs text-slate-500 dark:text-slate-400">
                            Total Potongan
                        </p>
                        <p className="text-lg font-bold text-emerald-600 dark:text-emerald-400">
                            {formatCurrency(summary.total_discount)}
                        </p>
                    </div>
                </div>
            </div>

            <div className="bg-white dark:bg-slate-900 rounded-2xl border border-slate-200 dark:border-slate-800 overflow-hidden">
                {transactions.length === 0 ? (
                    <div className="p-10 text-center text-sm text-slate-500 dark:text-slate-400">
                        Kode ini belum pernah dipakai di transaksi mana pun.
                    </div>
                ) : (
                    <Table>
                        <Table.Thead>
                            <tr>
                                <Table.Th>Invoice</Table.Th>
                                <Table.Th>Tanggal</Table.Th>
                                <Table.Th>Pelanggan</Table.Th>
                                <Table.Th>Kasir</Table.Th>
                                <Table.Th>Barang</Table.Th>
                                <Table.Th>Metode Bayar</Table.Th>
                                <Table.Th className="text-right">
                                    Potongan dari Kode Ini
                                </Table.Th>
                                <Table.Th className="text-right">
                                    Total Transaksi
                                </Table.Th>
                                <Table.Th />
                            </tr>
                        </Table.Thead>
                        <Table.Tbody>
                            {transactions.map((tx) => (
                                <tr key={tx.transaction_id}>
                                    <Table.Td className="font-mono text-xs">
                                        {tx.invoice}
                                    </Table.Td>
                                    <Table.Td className="text-sm">
                                        {formatDate(tx.created_at)}
                                    </Table.Td>
                                    <Table.Td className="text-sm">
                                        {tx.customer_name || (
                                            <span className="text-slate-400">
                                                Walk-in
                                            </span>
                                        )}
                                    </Table.Td>
                                    <Table.Td className="text-sm">
                                        {tx.cashier_name || "-"}
                                    </Table.Td>
                                    <Table.Td className="text-xs text-slate-500 dark:text-slate-400 max-w-xs !whitespace-normal">
                                        {tx.items
                                            .map(
                                                (item) =>
                                                    `${item.product_name} x${item.qty}`
                                            )
                                            .join(", ")}
                                    </Table.Td>
                                    <Table.Td className="text-sm">
                                        {paymentLabels[tx.payment_method] ||
                                            tx.payment_method}
                                    </Table.Td>
                                    <Table.Td className="text-right text-sm font-semibold text-emerald-600 dark:text-emerald-400">
                                        -{formatCurrency(tx.discount_total)}
                                    </Table.Td>
                                    <Table.Td className="text-right text-sm font-semibold text-slate-900 dark:text-white">
                                        {formatCurrency(tx.grand_total)}
                                    </Table.Td>
                                    <Table.Td>
                                        <Link
                                            href={route(
                                                "transactions.print",
                                                tx.invoice
                                            )}
                                            target="_blank"
                                            className="inline-flex items-center gap-1 text-xs text-primary-600 hover:text-primary-700 dark:text-primary-400"
                                        >
                                            <IconReceipt size={14} />
                                            Struk
                                        </Link>
                                    </Table.Td>
                                </tr>
                            ))}
                        </Table.Tbody>
                    </Table>
                )}
            </div>
        </DashboardLayout>
    );
}
