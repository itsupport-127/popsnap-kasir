<?php

namespace App\Http\Controllers;

use App\Models\Payable;
use App\Models\Receivable;
use App\Models\Transaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Picqer\Barcode\BarcodeGeneratorPNG;

class DocumentController extends Controller
{
    private function ensureFontDirectory(): void
    {
        $fontDir = storage_path('fonts');
        if (! is_dir($fontDir)) {
            @mkdir($fontDir, 0755, true);
        }
    }

    private function storeProfile(): array
    {
        $logo = \App\Models\Setting::get('store_logo');
        if ($logo && ! str_starts_with($logo, 'http') && ! str_starts_with($logo, '/storage')) {
            $logo = asset('storage/'.ltrim($logo, '/'));
        }

        $logoData = null;
        if ($logo) {
            $localPath = null;
            if (str_starts_with($logo, asset('storage'))) {
                $localPath = public_path(str_replace(asset(''), '', $logo));
            } elseif (str_starts_with($logo, '/storage')) {
                $localPath = public_path($logo);
            }

            if ($localPath && file_exists($localPath)) {
                $logoData = 'data:image/png;base64,'.base64_encode(file_get_contents($localPath));
            }
        }

        return [
            'name' => \App\Models\Setting::get('store_name', 'Toko Anda'),
            'logo' => $logo,
            'logo_data' => $logoData,
            'address' => \App\Models\Setting::get('store_address', ''),
            'phone' => \App\Models\Setting::get('store_phone', ''),
            'email' => \App\Models\Setting::get('store_email', ''),
            'website' => \App\Models\Setting::get('store_website', ''),
        ];
    }

    private function barcode(string $code): string
    {
        $generator = new BarcodeGeneratorPNG;
        $data = $generator->getBarcode($code, $generator::TYPE_CODE_128);

        return 'data:image/png;base64,'.base64_encode($data);
    }

    public function invoice(string $invoice)
    {
        $this->ensureFontDirectory();

        $transaction = Transaction::with(['details.product', 'cashier', 'customer'])
            ->where('invoice', $invoice)
            ->firstOrFail();

        $pdf = Pdf::loadView('pdf.invoice', [
            'transaction' => $transaction,
            'store' => $this->storeProfile(),
            'barcode' => $this->barcode($transaction->invoice),
        ])->setPaper('a4');

        return $pdf->stream("invoice-{$transaction->invoice}.pdf");
    }

    /**
     * Public version of invoice (no auth needed).
     */
    public function publicInvoice(string $invoice)
    {
        return $this->invoice($invoice);
    }

    public function receipt(string $invoice, string $size = '80')
    {
        $this->ensureFontDirectory();

        $transaction = Transaction::with(['details.product', 'cashier', 'customer'])
            ->where('invoice', $invoice)
            ->firstOrFail();

        $template = $size === '58' ? 'pdf.receipt_58' : 'pdf.receipt_80';
        $width    = $size === '58' ? 164.4 : 226.8; // 1mm = 2.8346pt

        // ----------------------------------------------------------------
        // Hitung tinggi secara akurat per baris konten
        // Font 11px, line-height 1.4 → ~1 baris = 11pt
        // Pre separator (margin 3px*2 = 6px = 4.5pt + line) = ~15pt
        // section margin = 5pt
        // ----------------------------------------------------------------
        $lineH = 11;   // pt per baris teks
        $sep   = 15;   // pt per garis separator (=== atau ---)
        $pad   = 12;   // body padding atas+bawah

        // Header toko
        $store    = $this->storeProfile();
        $hdrLines = 1; // nama toko
        if (!empty($store['address'])) $hdrLines++;
        if (!empty($store['phone']))   $hdrLines++;
        if (!empty($store['email']))   $hdrLines++;
        if (!empty($store['website'])) $hdrLines++;
        $headerPt = ($hdrLines * $lineH) + 5;

        // Info transaksi (No, Tgl, Kasir, Pelanggan) = 4 baris
        $infoPt = (4 * $lineH) + 5;

        // Items
        $itemsPt = 0;
        foreach ($transaction->details as $item) {
            $itemsPt += $lineH;             // nama produk
            $itemsPt += $lineH;             // qty @ harga
            if (($item->discount_total ?? 0) > 0) {
                $itemsPt += $lineH;         // baris promo
            }
        }
        $itemsPt += 5; // section margin

        // Summary
        $sumLines = 2; // Subtotal + TOTAL selalu ada
        if (($transaction->details->sum('discount_total') ?? 0) > 0) $sumLines++;
        if (($transaction->discount ?? 0) > 0)              $sumLines++;
        if (($transaction->customer_voucher_discount ?? 0) > 0) $sumLines++;
        if (($transaction->loyalty_discount_total ?? 0) > 0)    $sumLines++;
        if (($transaction->shipping_cost ?? 0) > 0)             $sumLines++;
        $sumPt = ($sumLines * $lineH) + 5;

        // Pembayaran (Bayar + Kembali jika ada)
        $payLines = 1;
        if (($transaction->change ?? 0) > 0) $payLines++;
        $payPt = ($payLines * $lineH) + 5;

        // Footer (barcode 24px + invoice text + terima kasih)
        $footerPt = 34 + $lineH + $lineH + 5;

        // Separator lines: header|info, info|items, items|summary, summary|pay, pay|footer = 5
        $totalPt = $pad + $headerPt + $sep + $infoPt + $sep + $itemsPt + $sep + $sumPt + $sep + $payPt + $sep + $footerPt;

        // Tambah buffer kecil agar tidak terpotong
        $heightPoints = (int) ceil($totalPt) + 20;

        $pdf = Pdf::loadView($template, [
            'transaction' => $transaction,
            'store'       => $store,
            'barcode'     => $this->barcode($transaction->invoice),
        ])->setPaper([0, 0, $width, $heightPoints], 'portrait');

        return $pdf->stream("receipt-{$transaction->invoice}-{$size}.pdf");
    }

    /**
     * Versi HTML murni dari struk — dipakai khusus oleh QZ Tray
     * untuk print langsung ke printer thermal tanpa lewat PDF,
     * supaya tidak terikat ukuran kertas (Form) Windows.
     */
    public function receiptHtml(string $invoice, string $size = '58')
    {
        $transaction = Transaction::with(['details.product', 'cashier', 'customer'])
            ->where('invoice', $invoice)
            ->firstOrFail();

        $template = $size === '58' ? 'pdf.receipt_58' : 'pdf.receipt_80';

        return view($template, [
            'transaction' => $transaction,
            'store'       => $this->storeProfile(),
            'barcode'     => $this->barcode($transaction->invoice),
        ]);
    }

    /**
     * Versi RAW ESC/POS dari struk — dipakai QZ Tray untuk print
     * langsung tanpa lewat rendering HTML (JavaFX), supaya tidak
     * tergantung komponen WebView yang kadang macet di beberapa komputer.
     */
    public function receiptRaw(string $invoice, string $size = '58')
    {
        $transaction = Transaction::with(['details.product', 'cashier', 'customer'])
            ->where('invoice', $invoice)
            ->firstOrFail();

        $store = $this->storeProfile();
        $width = $size === '58' ? 32 : 48;

        $ESC = "\x1B";
        $GS  = "\x1D";

        $init        = $ESC.'@';
        $alignCenter = $ESC.'a'."\x01";
        $alignLeft   = $ESC.'a'."\x00";
        $boldOn      = $ESC.'E'."\x01";
        $boldOff     = $ESC.'E'."\x00";
        $cut         = "\n\n\n".$GS.'V'."\x00";

        $line  = str_repeat('-', $width)."\n";
        $dline = str_repeat('=', $width)."\n";

        $center = function (string $text) use ($width) {
            $text = trim($text);
            $pad  = max(0, intdiv($width - strlen($text), 2));

            return str_repeat(' ', $pad).$text."\n";
        };

        $kv = function ($label, $value) use ($width) {
            $label = (string) $label;
            $value = (string) $value;
            $space = $width - strlen($label) - strlen($value);
            if ($space < 1) {
                $space = 1;
            }

            return $label.str_repeat(' ', $space).$value."\n";
        };

        $rp = fn ($num) => 'Rp '.number_format((float) ($num ?? 0), 0, ',', '.');

        $out = $init.$alignCenter;
        $out .= $boldOn.($store['name'] ?? 'Toko')."\n".$boldOff;

        if (! empty($store['address'])) {
            $out .= wordwrap($store['address'], $width, "\n", true)."\n";
        }
        if (! empty($store['phone'])) {
            $out .= $store['phone']."\n";
        }
        if (! empty($store['website'])) {
            $out .= $store['website']."\n";
        }

        $out .= $alignLeft.$dline;
        $out .= $kv('No:', $transaction->invoice);
        $out .= $kv('Tgl:', \Carbon\Carbon::parse($transaction->created_at)->format('d/m/Y H:i'));
        $out .= $kv('Kasir:', $transaction->cashier->name ?? '-');
        $out .= $kv('Pelanggan:', $transaction->customer->name ?? 'Umum');
        $out .= $dline;

        foreach ($transaction->details as $item) {
            $name = $item->product->title ?? 'Produk';
            $out .= $boldOn.$name."\n".$boldOff;

            $qty       = max(1, (int) $item->qty);
            $total     = $item->price;
            $unitPrice = $item->unit_price ?: ($qty ? $total / $qty : $total);
            $qtyLine   = $qty.'x @ '.$rp($unitPrice);

            $out .= $kv($qtyLine, $rp($total));
        }
        $out .= $line;

        // "discount_total" per item itu GABUNGAN promo otomatis + voucher +
        // diskon manual per-item — porsi voucher & diskon-item harus
        // dikurangi dulu supaya "Promo" nggak dobel-hitung sama baris
        // Voucher/Diskon Manual yang ditampilkan terpisah di bawah.
        $itemManualDiscountTotal = $transaction->details->sum('item_manual_discount');
        $promoDiscount   = $transaction->details->sum('discount_total')
            - $transaction->details->sum('voucher_discount')
            - $itemManualDiscountTotal;
        $voucherDiscount = $transaction->customer_voucher_discount ?? 0;
        $loyaltyDiscount = $transaction->loyalty_discount_total ?? 0;
        $manualDiscount  = ($transaction->discount ?? 0) + $itemManualDiscountTotal;
        $subtotal        = ($transaction->grand_total ?? 0) + $manualDiscount - ($transaction->shipping_cost ?? 0) + $promoDiscount + $voucherDiscount + $loyaltyDiscount;

        $out .= $kv('Subtotal', $rp($subtotal));
        // Sebelumnya breakdown diskon ini nggak pernah ditampilkan di struk
        // fisik (cuma ada di preview layar/PDF) — pelanggan nggak tahu asal
        // selisih antara Subtotal & TOTAL. Sekarang disamain kayak versi lain.
        if ($promoDiscount > 0) {
            $out .= $kv('Promo', '-'.$rp($promoDiscount));
        }
        if ($manualDiscount > 0) {
            $out .= $kv('Diskon Manual', '-'.$rp($manualDiscount));
        }
        if ($voucherDiscount > 0) {
            $out .= $kv('Voucher', '-'.$rp($voucherDiscount));
        }
        if ($loyaltyDiscount > 0) {
            $out .= $kv('Redeem Poin', '-'.$rp($loyaltyDiscount));
        }
        if (($transaction->shipping_cost ?? 0) > 0) {
            $out .= $kv('Ongkir', $rp($transaction->shipping_cost));
        }
        $out .= $boldOn.$kv('TOTAL', $rp($transaction->grand_total)).$boldOff;
        $out .= $line;

        $method = \App\Models\Transaction::receiptPaymentLabel($transaction->payment_method);
        // "cash" di database cuma keisi buat pembayaran tunai — buat QRIS/
        // transfer/gateway online kolom itu selalu 0, jadi kalau dipakai
        // langsung struk bakal salah nampilin "Bayar (QRIS) Rp 0" walau
        // pelanggan sudah bayar lunas. Nota belum dibayar (pay_later)
        // sengaja tetap Rp 0 karena memang belum ada yang dibayar.
        $paidAmount = in_array($transaction->payment_method, ['cash', 'pay_later'], true)
            ? $transaction->cash
            : $transaction->grand_total;

        if ($transaction->payment_method === 'dual_cash_qris') {
            $out .= $kv('Bayar (TUNAI)', $rp($transaction->dual_cash ?? 0));
            $out .= $kv('Bayar (QRIS)', $rp($transaction->dual_qris ?? 0));
        } else {
            $out .= $kv("Bayar ({$method})", $rp($paidAmount));
        }

        if (($transaction->change ?? 0) > 0) {
            $out .= $boldOn.$kv('Kembali', $rp($transaction->change)).$boldOff;
        }
        $out .= $dline;

        $out .= $alignCenter;
        $out .= $transaction->invoice."\n";
        $out .= 'Terima kasih!'."\n";

        $out .= $cut;

        return response($out, 200)
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    public function shipping(string $invoice)
    {
        $this->ensureFontDirectory();

        $transaction = Transaction::with(['details.product', 'customer', 'cashier'])
            ->where('invoice', $invoice)
            ->firstOrFail();

        $pdf = Pdf::loadView('pdf.shipping_label', [
            'transaction' => $transaction,
            'store' => $this->storeProfile(),
            'barcode' => $this->barcode($transaction->invoice),
        ]);

        // Set kertas 150mm x 100mm (dalam Points: 1mm = 2.83465pt)
        // 150mm = 425pt, 100mm = 283pt
        $pdf->setPaper([0, 0, 425, 283], 'landscape');

        return $pdf->stream("shipping-{$transaction->invoice}.pdf");
    }

    public function receivable(Receivable $receivable)
    {
        $this->ensureFontDirectory();

        $receivable->load(['customer', 'payments.bankAccount', 'payments.user']);

        $pdf = Pdf::loadView('pdf.receivable', [
            'receivable' => $receivable,
            'store' => $this->storeProfile(),
            'barcode' => $this->barcode($receivable->invoice),
        ])->setPaper('a5', 'portrait');

        return $pdf->stream("piutang-{$receivable->invoice}.pdf");
    }

    public function payable(Payable $payable)
    {
        $this->ensureFontDirectory();

        $payable->load(['supplier', 'payments.bankAccount', 'payments.user']);

        $pdf = Pdf::loadView('pdf.payable', [
            'payable' => $payable,
            'store' => $this->storeProfile(),
            'barcode' => $this->barcode($payable->document_number),
        ])->setPaper('a5', 'portrait');

        return $pdf->stream("hutang-{$payable->document_number}.pdf");
    }
}