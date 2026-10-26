<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Isi ulang `transaction_details.voucher_discount` & `item_manual_discount`
 * buat transaksi LAMA yang dibuat SEBELUM kolom itu beneran ditulis oleh
 * TransactionController::store() (dulu cuma nulis ke discount_total
 * gabungan, dua kolom breakdown ini selalu 0 walau datanya sudah benar di
 * level transaksi).
 *
 * PENTING — ini PERKIRAAN PROPORSIONAL, BUKAN rekonstruksi 100% presisi:
 * data asli soal "kode voucher mana nempel ke barang mana" sudah nggak
 * kesimpen buat transaksi lama, jadi command ini mengalokasikan total
 * voucher/diskon-item di level transaksi (yang SUDAH BENAR & nggak
 * diubah) secara proporsional ke tiap baris berdasarkan discount_total
 * baris itu. Untuk kasus 1 kode utuh ke 1 barang, hasilnya biasanya persis;
 * untuk kasus beberapa kode "Total Transaksi" nyebar rata ke banyak
 * barang, pembagiannya sudah representatif tapi bukan jaminan identik
 * dengan urutan asli.
 *
 * Nama promo per baris (pricing_rule_name) juga dirapikan: baris yang
 * kebagian porsi voucher diganti label-nya jadi daftar SEMUA kode voucher
 * yang dipakai di transaksi itu (dari transactions.customer_voucher_name)
 * — lebih jujur daripada nama tunggal lama yang bisa salah/nggak lengkap
 * kalau lebih dari 1 kode ikut nyumbang.
 *
 * SELALU jalankan tanpa --commit dulu buat preview. Command ini aman
 * dijalankan berkali-kali (idempotent) — transaksi yang breakdown-nya
 * udah keisi (voucher_discount atau item_manual_discount > 0 di salah satu
 * barisnya) otomatis dilewati.
 */
class BackfillDiscountBreakdown extends Command
{
    protected $signature = 'promo:backfill-discount-breakdown
                            {--commit : Beneran simpan perubahan (default cuma preview/dry-run)}
                            {--transaction-id= : Backfill 1 transaksi tertentu aja (buat cek dulu sebelum jalanin massal)}';

    protected $description = 'Isi ulang transaction_details.voucher_discount & item_manual_discount buat transaksi lama (perkiraan proporsional, lihat komentar di source).';

    public function handle(): int
    {
        $commit = (bool) $this->option('commit');
        $onlyTransactionId = $this->option('transaction-id');

        $query = Transaction::query()
            ->where(function ($q) {
                $q->where('customer_voucher_discount', '>', 0)
                    ->orWhere('item_manual_discount_total', '>', 0);
            })
            ->with('details')
            ->orderBy('id');

        if ($onlyTransactionId) {
            $query->where('id', $onlyTransactionId);
        }

        $transactions = $query->get();

        $this->info(
            ($commit ? 'MENYIMPAN PERUBAHAN' : 'DRY-RUN (preview saja — tambahkan --commit buat beneran simpan)')
            .' — '.$transactions->count().' transaksi kandidat diperiksa.'
        );
        $this->newLine();

        $updatedTransactions = 0;
        $updatedLines = 0;
        $skipped = 0;

        foreach ($transactions as $transaction) {
            $details = $transaction->details->values();

            // Idempotent: kalau breakdown-nya udah pernah keisi (baik dari
            // transaksi baru setelah fix, atau dari jalannya command ini
            // sebelumnya), lewati — jangan ditimpa ulang.
            $alreadyHasBreakdown = $details->sum('voucher_discount') > 0
                || $details->sum('item_manual_discount') > 0;

            if ($alreadyHasBreakdown) {
                $skipped++;

                continue;
            }

            $totalLineDiscount = (int) $details->sum('discount_total');

            if ($totalLineDiscount <= 0) {
                $skipped++;

                continue;
            }

            $voucherTotal = (int) $transaction->customer_voucher_discount;
            $itemManualTotal = (int) $transaction->item_manual_discount_total;
            $allocatedTotal = $voucherTotal + $itemManualTotal;

            if ($allocatedTotal <= 0) {
                $skipped++;

                continue;
            }

            $remainingVoucher = $voucherTotal;
            $remainingItemManual = $itemManualTotal;
            $lineCount = $details->count();
            $lineUpdates = [];

            foreach ($details as $index => $detail) {
                $lineDiscount = (int) $detail->discount_total;

                if ($lineDiscount <= 0) {
                    continue;
                }

                $isLastLine = $index === $lineCount - 1;

                // Baris terakhir yang punya diskon kebagian SISA pembagian
                // (bukan hasil pembulatan sendiri) — supaya total per-baris
                // selalu pas sama total transaksi, nggak meleset gara-gara
                // pembulatan di baris-baris sebelumnya.
                $share = $isLastLine
                    ? min($lineDiscount, $remainingVoucher + $remainingItemManual)
                    : (int) round($lineDiscount * ($allocatedTotal / $totalLineDiscount));
                $share = min($share, $lineDiscount, $remainingVoucher + $remainingItemManual);

                if ($share <= 0) {
                    continue;
                }

                // Voucher diprioritaskan dulu, sisanya (kalau masih ada)
                // baru dianggap diskon manual per-item.
                $voucherShare = min($share, $remainingVoucher);
                $itemManualShare = min($share - $voucherShare, $remainingItemManual);

                $remainingVoucher -= $voucherShare;
                $remainingItemManual -= $itemManualShare;

                if ($voucherShare <= 0 && $itemManualShare <= 0) {
                    continue;
                }

                $newName = $detail->pricing_rule_name;

                if ($voucherShare > 0 && $transaction->customer_voucher_name) {
                    $newName = $transaction->customer_voucher_name;
                }

                $lineUpdates[] = [
                    'detail' => $detail,
                    'voucher_discount' => $voucherShare,
                    'item_manual_discount' => $itemManualShare,
                    'pricing_rule_name' => $newName,
                ];
            }

            if (empty($lineUpdates)) {
                $skipped++;

                continue;
            }

            $this->line(
                "Transaksi #{$transaction->id} ({$transaction->invoice}): "
                .count($lineUpdates).' baris diupdate — voucher Rp'.number_format($voucherTotal, 0, ',', '.')
                .', item-manual Rp'.number_format($itemManualTotal, 0, ',', '.')
            );

            if ($commit) {
                DB::transaction(function () use ($lineUpdates) {
                    foreach ($lineUpdates as $update) {
                        $update['detail']->update([
                            'voucher_discount' => $update['voucher_discount'],
                            'item_manual_discount' => $update['item_manual_discount'],
                            'pricing_rule_name' => $update['pricing_rule_name'],
                        ]);
                    }
                });
            }

            $updatedTransactions++;
            $updatedLines += count($lineUpdates);
        }

        $this->newLine();
        $this->info("Selesai. Transaksi kena-update: {$updatedTransactions} | Baris kena-update: {$updatedLines} | Dilewati (udah bener/nggak relevan): {$skipped}");

        if (! $commit) {
            $this->warn('Ini masih DRY-RUN — belum ada yang disimpan ke database. Cek dulu hasilnya, lalu jalankan lagi dengan --commit kalau sudah oke.');
        }

        return self::SUCCESS;
    }
}
