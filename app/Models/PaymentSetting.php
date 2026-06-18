<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentSetting extends Model
{
    use HasFactory;

    public const GATEWAY_MIDTRANS     = 'midtrans';
    public const GATEWAY_XENDIT       = 'xendit';
    public const GATEWAY_BANK_TRANSFER = 'bank_transfer';
    public const GATEWAY_QRIS_STATIC  = 'qris_static';
    public const GATEWAY_DOKU         = 'doku';
    public const GATEWAY_FLIP         = 'flip';

    public const SECRET_FIELDS = [
        'midtrans_server_key',
        'xendit_secret_key',
        'xendit_callback_token',
        'doku_secret_key',
        'flip_secret_key',
        'flip_validation_token',
    ];

    protected $fillable = [
        'default_gateway',
        'bank_transfer_enabled',
        'qris_enabled',
        'qris_image_path',
        'qris_merchant_name',
        'qris_nmid',
        'midtrans_enabled',
        'midtrans_server_key',
        'midtrans_client_key',
        'midtrans_production',
        'xendit_enabled',
        'xendit_secret_key',
        'xendit_public_key',
        'xendit_callback_token',
        'xendit_production',
        'doku_enabled',
        'doku_client_id',
        'doku_secret_key',
        'doku_production',
        'flip_enabled',
        'flip_secret_key',
        'flip_validation_token',
        'flip_production',
    ];

    protected $casts = [
        'bank_transfer_enabled' => 'boolean',
        'qris_enabled'          => 'boolean',
        'midtrans_enabled'      => 'boolean',
        'midtrans_production'   => 'boolean',
        'xendit_enabled'        => 'boolean',
        'xendit_production'     => 'boolean',
        'doku_enabled'          => 'boolean',
        'doku_production'       => 'boolean',
        'flip_enabled'          => 'boolean',
        'flip_production'       => 'boolean',
        'midtrans_server_key'   => 'encrypted',
        'xendit_secret_key'     => 'encrypted',
        'xendit_callback_token' => 'encrypted',
        'doku_secret_key'       => 'encrypted',
        'flip_secret_key'       => 'encrypted',
        'flip_validation_token' => 'encrypted',
    ];

    public function enabledGateways(): array
    {
        $gateways = [];

        if ($this->isBankTransferReady()) {
            $gateways[] = ['value' => self::GATEWAY_BANK_TRANSFER, 'label' => 'Transfer Bank', 'description' => 'Pembayaran manual via transfer bank.'];
        }
        if ($this->isQrisStaticReady()) {
            $gateways[] = ['value' => self::GATEWAY_QRIS_STATIC, 'label' => 'QRIS (Statis)', 'description' => 'Scan QR code toko, konfirmasi manual kasir.'];
        }
        if ($this->isGatewayReady(self::GATEWAY_DOKU)) {
            $gateways[] = ['value' => self::GATEWAY_DOKU, 'label' => 'DOKU QRIS', 'description' => 'QRIS dinamis otomatis via DOKU.'];
        }
        if ($this->isGatewayReady(self::GATEWAY_FLIP)) {
            $gateways[] = ['value' => self::GATEWAY_FLIP, 'label' => 'Flip.id', 'description' => 'Pembayaran via Flip.id.'];
        }
        if ($this->isGatewayReady(self::GATEWAY_MIDTRANS)) {
            $gateways[] = ['value' => self::GATEWAY_MIDTRANS, 'label' => 'Midtrans', 'description' => 'Bagikan tautan pembayaran Snap Midtrans.'];
        }
        if ($this->isGatewayReady(self::GATEWAY_XENDIT)) {
            $gateways[] = ['value' => self::GATEWAY_XENDIT, 'label' => 'Xendit', 'description' => 'Buat invoice otomatis menggunakan Xendit.'];
        }

        return $gateways;
    }

    public function isBankTransferReady(): bool
    {
        return $this->bank_transfer_enabled && BankAccount::active()->exists();
    }

    public function isQrisStaticReady(): bool
    {
        return $this->qris_enabled && filled($this->qris_image_path);
    }

    public function isGatewayReady(string $gateway): bool
    {
        return match ($gateway) {
            self::GATEWAY_BANK_TRANSFER => $this->isBankTransferReady(),
            self::GATEWAY_QRIS_STATIC   => $this->isQrisStaticReady(),
            self::GATEWAY_DOKU          => $this->doku_enabled
                && filled($this->resolvedSecret('doku_secret_key'))
                && filled($this->doku_client_id),
            self::GATEWAY_FLIP          => $this->flip_enabled
                && filled($this->resolvedSecret('flip_secret_key')),
            self::GATEWAY_MIDTRANS      => $this->midtrans_enabled
                && filled($this->resolvedSecret('midtrans_server_key'))
                && filled($this->midtrans_client_key),
            self::GATEWAY_XENDIT        => $this->xendit_enabled
                && filled($this->resolvedSecret('xendit_secret_key'))
                && filled($this->xendit_public_key),
            default => false,
        };
    }

    public function qrisStaticConfig(): array
    {
        return [
            'enabled'       => $this->isQrisStaticReady(),
            'image_url'     => $this->qris_image_path ? \Storage::url($this->qris_image_path) : null,
            'merchant_name' => $this->qris_merchant_name,
            'nmid'          => $this->qris_nmid,
        ];
    }

    public function dokuConfig(): array
    {
        return [
            'enabled'       => $this->isGatewayReady(self::GATEWAY_DOKU),
            'client_id'     => $this->doku_client_id,
            'secret_key'    => $this->resolvedSecret('doku_secret_key'),
            'is_production' => $this->doku_production,
        ];
    }

    public function flipConfig(): array
    {
        return [
            'enabled'          => $this->isGatewayReady(self::GATEWAY_FLIP),
            'secret_key'       => $this->resolvedSecret('flip_secret_key'),
            'validation_token' => $this->resolvedSecret('flip_validation_token'),
            'is_production'    => $this->flip_production,
        ];
    }

    public function midtransConfig(): array
    {
        return [
            'enabled'       => $this->isGatewayReady(self::GATEWAY_MIDTRANS),
            'server_key'    => $this->resolvedSecret('midtrans_server_key'),
            'client_key'    => $this->midtrans_client_key,
            'is_production' => $this->midtrans_production,
        ];
    }

    public function xenditConfig(): array
    {
        return [
            'enabled'          => $this->isGatewayReady(self::GATEWAY_XENDIT),
            'secret_key'       => $this->resolvedSecret('xendit_secret_key'),
            'public_key'       => $this->xendit_public_key,
            'callback_token'   => $this->resolvedSecret('xendit_callback_token'),
            'is_production'    => $this->xendit_production,
        ];
    }

    public function resolvedSecret(string $field): ?string
    {
        $envValue = $this->envSecretValue($field);
        if (filled($envValue)) return $envValue;
        return $this->getAttributeValue($field);
    }

    public function secretSource(string $field): string
    {
        if (filled($this->envSecretValue($field))) return 'env';
        if (filled($this->getAttributeValue($field))) return 'database';
        return 'none';
    }

    public function secretConfigured(string $field): bool
    {
        return filled($this->resolvedSecret($field));
    }

    public function secretManagedByEnvironment(string $field): bool
    {
        return $this->secretSource($field) === 'env';
    }

    public function maskedSecret(string $field): ?string
    {
        $value = $this->resolvedSecret($field);
        if (blank($value)) return null;
        $length = strlen($value);
        if ($length <= 4) return str_repeat('•', $length);
        return str_repeat('•', max($length - 4, 4)) . substr($value, -4);
    }

    public function paymentSettingSources(): array
    {
        return [
            'midtrans_server_key'   => $this->secretMetadata('midtrans_server_key'),
            'xendit_secret_key'     => $this->secretMetadata('xendit_secret_key'),
            'xendit_callback_token' => $this->secretMetadata('xendit_callback_token'),
            'doku_secret_key'       => $this->secretMetadata('doku_secret_key'),
            'flip_secret_key'       => $this->secretMetadata('flip_secret_key'),
            'flip_validation_token' => $this->secretMetadata('flip_validation_token'),
        ];
    }

    private function secretMetadata(string $field): array
    {
        return [
            'source'                 => $this->secretSource($field),
            'configured'             => $this->secretConfigured($field),
            'managed_by_environment' => $this->secretManagedByEnvironment($field),
            'masked'                 => $this->maskedSecret($field),
        ];
    }

    private function envSecretValue(string $field): ?string
    {
        return match ($field) {
            'midtrans_server_key'   => config('services.midtrans.server_key'),
            'xendit_secret_key'     => config('services.xendit.secret_key'),
            'xendit_callback_token' => config('services.xendit.callback_token'),
            'doku_secret_key'       => config('services.doku.secret_key'),
            'flip_secret_key'       => config('services.flip.secret_key'),
            'flip_validation_token' => config('services.flip.validation_token'),
            default                 => null,
        };
    }
}