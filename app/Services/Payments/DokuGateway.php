<?php

namespace App\Services\Payments;

use App\Exceptions\PaymentGatewayException;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * DOKU Gateway — QRIS Dinamis
 *
 * Dokumentasi: https://developers.doku.com/accept-payment/direct-api/snap
 * Daftar akun: https://dashboard.doku.com
 */
class DokuGateway
{
    private const SANDBOX_URL    = 'https://api-sandbox.doku.com';
    private const PRODUCTION_URL = 'https://api.doku.com';

    public function createQris(Transaction $transaction, array $config): array
    {
        if (! ($config['enabled'] ?? false)) {
            throw new PaymentGatewayException('DOKU tidak aktif atau belum dikonfigurasi.');
        }

        $baseUrl   = $config['is_production'] ? self::PRODUCTION_URL : self::SANDBOX_URL;
        $clientId  = $config['client_id'];
        $secretKey = $config['secret_key'];
        $requestId = (string) Str::uuid();
        $requestTarget = '/checkout/v1/payment';
        $timestamp  = now()->utc()->format('Y-m-d\TH:i:s\Z');

        $body = [
            'order'  => [
                'invoice_number' => $transaction->invoice,
                'line_items'     => [['name' => 'Transaksi #'.$transaction->invoice, 'price' => (int) $transaction->grand_total, 'quantity' => 1]],
                'amount'         => (int) $transaction->grand_total,
                'currency'       => 'IDR',
                'callback_url'   => route('webhooks.doku'),
                'auto_redirect'  => false,
            ],
            'payment'  => ['payment_due_date' => 60],
            'customer' => [
                'name'  => optional($transaction->customer)->name ?? 'Customer',
                'email' => optional($transaction->customer)->email ?? config('mail.from.address', 'noreply@example.com'),
            ],
        ];

        // DOKU pakai HMAC-SHA256 signature
        $bodyDigest = base64_encode(hash('sha256', json_encode($body), true));
        $signatureComponents = "Client-Id:{$clientId}\nRequest-Id:{$requestId}\nRequest-Timestamp:{$timestamp}\nRequest-Target:{$requestTarget}\nDigest:{$bodyDigest}";
        $signature = base64_encode(hash_hmac('sha256', $signatureComponents, $secretKey, true));

        $response = Http::withHeaders([
            'Client-Id'         => $clientId,
            'Request-Id'        => $requestId,
            'Request-Timestamp' => $timestamp,
            'Signature'         => "HMACSHA256={$signature}",
            'Content-Type'      => 'application/json',
        ])->post("{$baseUrl}{$requestTarget}", $body);

        if ($response->failed()) {
            throw new PaymentGatewayException(
                'DOKU error: ' . $response->json('error.message', $response->body())
            );
        }

        return [
            'reference'   => $response->json('order.invoice_number', $transaction->invoice),
            'payment_url' => $response->json('response.payment_url'),
            'raw'         => $response->json(),
        ];
    }

    /**
     * Verifikasi webhook dari DOKU
     */
    public function verifyWebhook(array $headers, string $body, string $secretKey): bool
    {
        $clientId  = $headers['client-id'] ?? '';
        $requestId = $headers['request-id'] ?? '';
        $timestamp = $headers['request-timestamp'] ?? '';
        $digest    = base64_encode(hash('sha256', $body, true));

        $signatureComponents = "Client-Id:{$clientId}\nRequest-Id:{$requestId}\nRequest-Timestamp:{$timestamp}\nDigest:{$digest}";
        $expected = 'HMACSHA256=' . base64_encode(hash_hmac('sha256', $signatureComponents, $secretKey, true));
        $received = $headers['signature'] ?? '';

        return hash_equals($expected, $received);
    }

    /**
     * Map status DOKU ke status internal
     */
    public function mapStatus(string $acquirementStatus): string
    {
        return match (strtoupper($acquirementStatus)) {
            'SUCCESS' => 'paid',
            'PENDING' => 'pending',
            'FAILED', 'EXPIRED' => 'failed',
            default   => 'pending',
        };
    }
}
