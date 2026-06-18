<?php

namespace App\Services\Payments;

use App\Exceptions\PaymentGatewayException;
use App\Models\PaymentSetting;
use App\Models\Transaction;

class PaymentGatewayManager
{
    public function __construct(
        private MidtransGateway $midtransGateway,
        private XenditGateway   $xenditGateway,
        private DokuGateway     $dokuGateway,
        private FlipGateway     $flipGateway,
    ) {}

    /**
     * Buat pembayaran sesuai gateway yang dipilih.
     * QRIS Statis tidak masuk sini karena tidak perlu API call — ditangani di frontend.
     */
    public function createPayment(Transaction $transaction, string $gateway, PaymentSetting $setting): array
    {
        return match ($gateway) {
            PaymentSetting::GATEWAY_MIDTRANS   => $this->midtransGateway->createCharge($transaction, $setting->midtransConfig()),
            PaymentSetting::GATEWAY_XENDIT     => $this->xenditGateway->createInvoice($transaction, $setting->xenditConfig()),
            PaymentSetting::GATEWAY_DOKU       => $this->dokuGateway->createQris($transaction, $setting->dokuConfig()),
            PaymentSetting::GATEWAY_FLIP       => $this->flipGateway->createBill($transaction, $setting->flipConfig()),
            PaymentSetting::GATEWAY_QRIS_STATIC => throw new PaymentGatewayException('QRIS statis tidak memerlukan API call.'),
            default => throw new PaymentGatewayException("Gateway {$gateway} belum didukung."),
        };
    }
}
