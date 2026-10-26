<?php

namespace App\Console\Commands;

use App\Models\Promo;
use App\Models\Transaction;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Assign ulang voucher pada transaksi LAMA yang dulu dihitung pakai mode
 * "Total Transaksi" (sebelum discount_scope=per_item dipasang ke kode-kode
 * promo) — dari yang tadinya kepotong RATA ke semua barang eligible,
 * diubah jadi 1 kode = 1 barang penuh (sesuai niat aslinya "1 kode = 1
 * kesempatan dari 1 paket").
 *
 * ==================== INI BUKAN BUG FIX — INI TEBAKAN ====================
 * Data asli soal "kode X nempel ke barang mana" TIDAK PERNAH tersimpan buat
 * transaksi yang dibuat waktu mode-nya masih "Total Transaksi" (sistem waktu
 * itu memang menghitungnya sebagai 1 kolam bersama, bukan per barang).
 * Command ini MENEBAK dengan heuristik yang paling masuk akal:
 *   - Kode diurutkan sesuai urutan tersimpan di transactions.customer_voucher_code
 *     (urutan kasir pilih/apply kode-nya).
 *   - Barang diurutkan berdasarkan transaction_details.id ASC (urutan barang
 *     masuk keranjang).
 *   - Kode ke-N di-assign PENUH ke barang ke-N (1:1).
 * Total keseluruhan (subtotal, grand_total) TIDAK BERUBAH sama sekali —
 * cuma rincian "barang mana dapat potongan berapa" yang di-assign ulang.
 *
 * HANYA jalan buat transaksi yang jumlah kode == jumlah barang eligible
 * (kasus paling umum & paling aman ditebak). Kalau jumlahnya nggak pas
 * (mis. 3 kode buat 2 barang, atau ada barang yang kena promo otomatis
 * juga), transaksi itu DILEWATI dan dicatat di ringkasan buat dicek manual
 * — command ini nggak akan maksa nebak kasus yang ambigu.
 *
 * SELALU jalankan tanpa --commit dulu buat preview. Idempotent — transaksi
 * yang pricing_rule_name-nya udah rapi (1 nama per baris, bukan gabungan
 * koma) otomatis dilewati.
 */
class ReassignLegacyTotalTransactionVouchers extends Command
{
    protected $signature = 'promo:reassign-legacy-total-vouchers
                            {--commit : Beneran simpan perubahan (default cuma preview/dry-run)}
                            {--transaction-id= : Reassign 1 transaksi tertentu aja (buat cek dulu sebelum jalanin massal)}';

    protected $description = 'Assign ulang voucher lama mode "Total Transaksi" (kepotong rata) jadi 1 kode = 1 barang, berdasarkan urutan pemakaian di kasir. INI TEBAKAN, BUKAN REKONSTRUKSI PASTI — baca komentar di source sebelum jalanin.';

    public function handle(): int
    {
        $commit = (bool) $this->option('commit');
        $onlyTransactionId = $this->option('transaction-id');

        $query = Transaction::query()
            ->where('customer_voucher_discount', '>', 0)
            ->whereNotNull('customer_voucher_code')
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

        $updated = 0;
        $skippedAlreadyClean = 0;
        $skippedCountMismatch = [];
        $skippedCodeNotFound = [];

        foreach ($transactions as $transaction) {
            // Idempotent: cuma proses baris yang nama promonya masih
            // "gabungan" (mengandung koma, tanda peninggalan mode Total
            // Transaksi lama). Baris yang udah 1-nama-1-barang dilewati.
            $joinedNameDetails = $transaction->details
                ->filter(fn ($d) => $d->voucher_discount > 0 && str_contains((string) $d->pricing_rule_name, ','))
                ->sortBy('id')
                ->values();

            if ($joinedNameDetails->isEmpty()) {
                $skippedAlreadyClean++;

                continue;
            }

            $codes = collect(explode(',', $transaction->customer_voucher_code))
                ->map(fn ($c) => trim($c))
                ->filter()
                ->values();

            // Cuma proses kasus 1:1 yang paling aman ditebak. Jumlah kode
            // beda dari jumlah barang yang kena voucher -> ambigu, lewati.
            if ($codes->count() !== $joinedNameDetails->count()) {
                $skippedCountMismatch[] = $transaction->id;

                continue;
            }

            $promosByCode = Promo::whereIn('code', $codes)->get()->keyBy('code');

            if ($promosByCode->count() !== $codes->count()) {
                $skippedCodeNotFound[] = $transaction->id;

                continue;
            }

            $lineUpdates = [];

            foreach ($joinedNameDetails as $index => $detail) {
                $code = $codes[$index];
                $promo = $promosByCode->get($code);

                // PENTING: `price` di transaction_details itu GROSS (sebelum
                // diskon apa pun) — bukan net. Jadi harga barang ini sebelum
                // voucher ya `price` itu sendiri, TIDAK perlu ditambah
                // voucher_discount lagi (kalau ditambah malah double-count).
                $grossPrice = (int) $detail->price;

                $newVoucherDiscount = match ($promo->discount_type) {
                    Promo::TYPE_PERCENTAGE => (int) round($grossPrice * ((float) $promo->discount_value / 100)),
                    Promo::TYPE_FIXED_AMOUNT => min($grossPrice, (int) round((float) $promo->discount_value)),
                    default => 0,
                };

                $lineUpdates[] = [
                    'detail' => $detail,
                    'old_voucher_discount' => (int) $detail->voucher_discount,
                    'new_voucher_discount' => $newVoucherDiscount,
                    'new_discount_total' => (int) $detail->discount_total - (int) $detail->voucher_discount + $newVoucherDiscount,
                    'new_pricing_rule_name' => $promo->name,
                    'new_promo_id' => $promo->id,
                ];
            }

            // Jaring pengaman terakhir: total voucher hasil assign ulang
            // HARUS persis sama dengan total voucher tersimpan di transaksi
            // (subtotal/grand_total transaksi TIDAK BOLEH berubah). Kalau
            // karena pembulatan/apa pun jadi beda, transaksi ini dilewati
            // & dicatat buat dicek manual, bukan dipaksa jalan dengan angka
            // yang nggak pas.
            $reassignedTotal = collect($lineUpdates)->sum('new_voucher_discount');

            if ($reassignedTotal !== (int) $transaction->customer_voucher_discount) {
                $skippedCountMismatch[] = $transaction->id;

                continue;
            }

            $this->line("Transaksi #{$transaction->id} ({$transaction->invoice}):");
            foreach ($lineUpdates as $u) {
                $this->line(
                    '  - '.$u['detail']->product_id.': '
                    .$u['new_pricing_rule_name'].' -> -Rp'.number_format($u['new_voucher_discount'], 0, ',', '.')
                    .' (dulu rata -Rp'.number_format($u['old_voucher_discount'], 0, ',', '.').')'
                );
            }

            if ($commit) {
                DB::transaction(function () use ($lineUpdates) {
                    foreach ($lineUpdates as $u) {
                        $u['detail']->update([
                            'voucher_discount' => $u['new_voucher_discount'],
                            'discount_total' => $u['new_discount_total'],
                            'pricing_rule_name' => $u['new_pricing_rule_name'],
                            'promo_id' => $u['new_promo_id'],
                        ]);
                    }
                });
            }

            $updated++;
        }

        $this->newLine();
        $this->info("Selesai. Transaksi kena-assign-ulang: {$updated} | Sudah rapi/dilewati: {$skippedAlreadyClean}");

        if (! empty($skippedCountMismatch)) {
            $this->warn('Dilewati (jumlah kode vs barang nggak 1:1 / total nggak pas, PERLU DICEK MANUAL): '.implode(', ', $skippedCountMismatch));
        }

        if (! empty($skippedCodeNotFound)) {
            $this->warn('Dilewati (ada kode voucher yang udah dihapus dari tabel promos): '.implode(', ', $skippedCodeNotFound));
        }

        if (! $commit) {
            $this->warn('Ini masih DRY-RUN — belum ada yang disimpan ke database. Cek dulu hasilnya, lalu jalankan lagi dengan --commit kalau sudah oke.');
        }

        return self::SUCCESS;
    }
}
