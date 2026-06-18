<?php

namespace App\Services\Payments;

use App\Exceptions\PaymentGatewayException;
use App\Models\Transaction;
use Illuminate\Support\Facades\Http;

/**
 * Flip.id Gateway
 *
 * Dokumentasi: https://docs.flip.id
 * Daftar akun: https://flip.id/business
 * Biaya: 0.7% per transaksi QRIS, gratis daftar
 */
class FlipGateway
{
    private const SANDBOX_URL    = 'https://bigflip.id/big_sandbox_api/v2';
    private const PRODUCTION_URL = 'https://bigflip.id/api/v2';

    public function createBill(Transaction $transaction, array $config): array
    {
        if (! ($config['enabled'] ?? false)) {
            throw new PaymentGatewayException('Flip tidak aktif atau belum dikonfigurasi.');
        }

        $baseUrl = $config['is_production'] ? self::PRODUCTION_URL : self::SANDBOX_URL;

        $response = Http::withBasicAuth($config['secret_key'], '')
            ->asForm()
            ->post("{$baseUrl}/pwf/bill", [
                'title'            => 'Transaksi #' . $transaction->invoice,
                'amount'           => (int) $transaction->grand_total,
                'type'             => 'SINGLE',
                'expired_date'     => now()->addHour()->format('Y-m-d H:i'),
                'redirect_url'     => route('transactions.print', $transaction->invoice),
                'is_address_enabled' => false,
            ]);

        if ($response->failed()) {
            throw new PaymentGatewayException(
                'Flip error: ' . $response->json('errors.0.message', $response->body())
            );
        }

        return [
            'reference'   => $response->json('id'),
            'payment_url' => $response->json('link_url'),
            'raw'         => $response->json(),
        ];
    }

    /**
     * Verifikasi webhook Flip menggunakan validation token
     */
    public function verifyWebhook(string $receivedToken, string $expectedToken): bool
    {
        return hash_equals($expectedToken, $receivedToken);
    }

    /**
     * Map status Flip ke status internal
     */
    public function mapStatus(string $status): string
    {
        return match (strtoupper($status)) {
            'SUCCESSFUL' => 'paid',
            'PENDING'    => 'pending',
            'FAILED', 'CANCELLED', 'EXPIRED' => 'failed',
            default      => 'pending',
        };
    }
}
