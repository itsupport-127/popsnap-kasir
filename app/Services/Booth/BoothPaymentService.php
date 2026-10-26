<?php

namespace App\Services\Booth;

use App\Exceptions\PaymentGatewayException;
use App\Models\BoothDevice;
use App\Models\BoothPayment;
use App\Models\PaymentSetting;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\Profit;
use App\Models\User;
use App\Services\StockMutationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Pembayaran sesi booth lewat Midtrans QRIS (Core API). Setiap pembayaran
 * dicatat sebagai Transaction di POS (produk "Sesi Photobox"), sehingga masuk
 * laporan penjualan kasir. Webhook Midtrans yang sudah ada (invoice = order_id)
 * ikut memperbarui status transaksinya.
 */
class BoothPaymentService
{
    public function __construct(private StockMutationService $stock) {}

    public function create(BoothDevice $device, string $clientRef, int $amount, ?string $sku = null): BoothPayment
    {
        $existing = BoothPayment::where('device_id', $device->id)->where('client_ref', $clientRef)->first();
        if ($existing) {
            return $this->refresh($existing);
        }

        $setting = PaymentSetting::first();
        $cfg = $setting?->midtransConfig();
        if (! $cfg || ! $cfg['enabled']) {
            throw new PaymentGatewayException('Midtrans belum aktif di pengaturan pembayaran.');
        }

        // Produk POS: pakai produk yang dipetakan booth (SKU template, mis. PHB-0001) supaya laporan per produk
        // tetap sama seperti penjualan kasir. Kalau SKU tidak dikirim/tidak ada, pakai produk umum "Sesi Photobox".
        $product = $sku ? Product::where('sku', $sku)->first() : null;
        if ($product) {
            $amount = (int) $product->sell_price; // harga dari server (otoritatif)
        } else {
            $product = Product::where('sku', config('booth.product_sku', 'SESI-PHOTOBOX'))->first();
        }
        if (! $product) {
            throw new PaymentGatewayException('Produk "Sesi Photobox" belum ada. Jalankan: php artisan booth:setup');
        }
        if ($amount < 1000) {
            throw new PaymentGatewayException('Harga produk tidak valid untuk QRIS.');
        }

        $cashierId = config('booth.cashier_id') ?: User::orderBy('id')->value('id');

        return DB::transaction(function () use ($device, $clientRef, $amount, $cfg, $product, $cashierId) {
            $invoice = 'BTH-'.strtoupper(Str::random(10));

            $trx = Transaction::create([
                'cashier_id' => $cashierId,
                'invoice' => $invoice,
                'cash' => $amount,
                'change' => 0,
                'discount' => 0,
                'grand_total' => $amount,
                'payment_method' => 'midtrans',
                'payment_status' => 'pending',
            ]);
            TransactionDetail::create([
                'transaction_id' => $trx->id,
                'product_id' => $product->id,
                'qty' => 1,
                'price' => $amount,
                'unit_price' => $amount,
                'base_unit_price' => $amount,
            ]);
            Profit::create(['transaction_id' => $trx->id, 'total' => $amount - (int) $product->buy_price]);

            // Stok dipotong seperti checkout kasir; dikembalikan otomatis kalau pembayaran gagal/expired.
            $before = (int) $product->stock;
            $product->update(['stock' => $before - 1]);
            $this->stock->recordSale($product, $trx, 1, $before, $before - 1, $cashierId);

            $expiry = config('booth.qris_minutes');
            $base = $cfg['is_production'] ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
            $res = Http::withBasicAuth($cfg['server_key'], '')->acceptJson()->post($base.'/v2/charge', [
                'payment_type' => 'qris',
                'transaction_details' => ['order_id' => $invoice, 'gross_amount' => $amount],
                'qris' => ['acquirer' => 'gopay'],
                'custom_expiry' => ['expiry_duration' => $expiry, 'unit' => 'minute'],
            ]);

            if ($res->failed() || ! in_array((string) $res->json('status_code'), ['200', '201'], true)) {
                throw new PaymentGatewayException('Midtrans: '.$res->json('status_message', $res->body()));
            }

            $qrUrl = collect($res->json('actions', []))->firstWhere('name', 'generate-qr-code')['url'] ?? null;

            $trx->update(['payment_reference' => $res->json('transaction_id')]);

            return BoothPayment::create([
                'owner_id' => $device->owner_id,
                'outlet_id' => $device->outlet_id,
                'device_id' => $device->id,
                'client_ref' => $clientRef,
                'transaction_id' => $trx->id,
                'amount' => $amount,
                'status' => 'pending',
                'qr_string' => $res->json('qr_string'),
                'qr_url' => $qrUrl,
                'gateway' => 'midtrans',
                'gateway_ref' => $res->json('transaction_id'),
                'expires_at' => now()->addMinutes($expiry),
            ]);
        });
    }

    /** Sinkronkan status dari transaksi (diperbarui webhook) + tanya Midtrans langsung kalau masih pending. */
    public function refresh(BoothPayment $payment): BoothPayment
    {
        if ($payment->status !== 'pending') {
            return $payment;
        }

        $trx = $payment->transaction;
        if ($trx && $trx->payment_status === 'pending') {
            $this->pollMidtrans($payment, $trx);
            $trx->refresh();
        }

        $status = $trx?->payment_status ?? 'pending';
        if ($status === 'pending' && $payment->expires_at && $payment->expires_at->isPast()) {
            $status = 'expired';
            $this->failTransaction($trx, 'failed');
        }

        if ($status !== 'pending') {
            $payment->update([
                'status' => $status === 'paid' ? 'paid' : ($status === 'expired' ? 'expired' : 'failed'),
                'paid_at' => $status === 'paid' ? now() : null,
            ]);
        }

        return $payment;
    }

    private function pollMidtrans(BoothPayment $payment, Transaction $trx): void
    {
        $cfg = PaymentSetting::first()?->midtransConfig();
        if (! $cfg || ! $cfg['enabled']) {
            return;
        }
        $base = $cfg['is_production'] ? 'https://api.midtrans.com' : 'https://api.sandbox.midtrans.com';
        try {
            $res = Http::withBasicAuth($cfg['server_key'], '')->acceptJson()->timeout(8)->get($base.'/v2/'.$trx->invoice.'/status');
        } catch (\Throwable) {
            return;
        }
        if (! $res->ok()) {
            return;
        }
        $new = match ($res->json('transaction_status')) {
            'capture', 'settlement' => 'paid',
            'deny', 'cancel', 'failure' => 'failed',
            'expire' => 'expired',
            default => 'pending',
        };
        if ($new === 'paid') {
            $trx->update(['payment_status' => 'paid']);
        } elseif ($new !== 'pending') {
            $this->failTransaction($trx, 'failed');
        }
    }

    /** Tandai gagal + kembalikan stok sekali saja (aman kalau webhook yang sama datang lagi). */
    private function failTransaction(?Transaction $trx, string $status): void
    {
        if (! $trx) {
            return;
        }
        DB::transaction(function () use ($trx, $status) {
            $t = Transaction::where('id', $trx->id)->lockForUpdate()->first();
            if (! $t || in_array($t->payment_status, ['failed', 'expired', 'paid'], true)) {
                return;
            }
            $t->update(['payment_status' => $status]);
            $this->stock->restoreStockForTransaction($t);
        });
    }
}
