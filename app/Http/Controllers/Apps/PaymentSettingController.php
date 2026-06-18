<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\PaymentSetting;
use App\Services\AuditLogService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class PaymentSettingController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {}

    public function edit()
    {
        $setting = PaymentSetting::firstOrCreate([], [
            'default_gateway' => 'cash',
        ]);

        $midtransWebhookUrl = route('webhooks.midtrans');
        $xenditWebhookUrl = route('webhooks.xendit');
        $appUrl = (string) config('app.url');
        $webhookWarnings = [];

        if (blank($appUrl)) {
            $webhookWarnings[] = 'APP_URL belum diatur. Webhook URL yang dihasilkan bisa tidak valid untuk Midtrans/Xendit.';
        } elseif ($this->isLocalAppUrl($appUrl)) {
            $webhookWarnings[] = 'APP_URL masih mengarah ke localhost atau 127.0.0.1. Payment gateway membutuhkan URL publik yang bisa diakses dari internet.';
        }

        if ($setting->xendit_enabled && ! $setting->secretConfigured('xendit_callback_token')) {
            $webhookWarnings[] = 'Xendit aktif tetapi callback token belum diisi. Webhook Xendit akan ditolak sampai token tersedia.';
        }

        if (collect($setting->paymentSettingSources())->contains(fn (array $source) => $source['source'] === 'env')) {
            $this->auditLogService->log(
                event: 'security.payment_secret_source_overridden',
                module: 'security',
                auditable: $setting,
                description: 'Konfigurasi payment memakai env override untuk secret sensitif.',
                meta: [
                    'severity' => 'info',
                    'sources' => collect($setting->paymentSettingSources())
                        ->filter(fn (array $source) => $source['source'] === 'env')
                        ->keys()
                        ->values()
                        ->all(),
                ],
            );
        }

        return Inertia::render('Dashboard/Settings/Payment', [
            'setting' => [
                'default_gateway' => $setting->default_gateway,
                'bank_transfer_enabled' => (bool) $setting->bank_transfer_enabled,
                'midtrans_enabled' => (bool) $setting->midtrans_enabled,
                'midtrans_client_key' => $setting->midtrans_client_key,
                'midtrans_production' => (bool) $setting->midtrans_production,
                'xendit_enabled' => (bool) $setting->xendit_enabled,
                'xendit_public_key' => $setting->xendit_public_key,
                'xendit_production' => (bool) $setting->xendit_production,
                'qris_enabled'       => (bool) $setting->qris_enabled,
                'qris_image_url'     => $setting->qris_image_path ? Storage::url($setting->qris_image_path) : null,
                'qris_merchant_name' => $setting->qris_merchant_name,
                'qris_nmid'          => $setting->qris_nmid,
                'doku_enabled'       => (bool) $setting->doku_enabled,
                'doku_client_id'     => $setting->doku_client_id,
                'doku_production'    => (bool) $setting->doku_production,
                'flip_enabled'       => (bool) $setting->flip_enabled,
                'flip_production'    => (bool) $setting->flip_production,
            ],
            'paymentSettingSources' => $setting->paymentSettingSources(),
            'supportedGateways' => [
                ['value' => 'cash', 'label' => 'Tunai'],
                ['value' => PaymentSetting::GATEWAY_BANK_TRANSFER, 'label' => 'Transfer Bank'],
                ['value' => PaymentSetting::GATEWAY_MIDTRANS, 'label' => 'Midtrans'],
                ['value' => PaymentSetting::GATEWAY_XENDIT, 'label' => 'Xendit'],
                ['value' => PaymentSetting::GATEWAY_QRIS_STATIC, 'label' => 'QRIS (Statis)'],
                ['value' => PaymentSetting::GATEWAY_DOKU,        'label' => 'DOKU QRIS'],
                ['value' => PaymentSetting::GATEWAY_FLIP,        'label' => 'Flip.id'],
            ],
            'webhookUrls' => [
                'midtrans' => $midtransWebhookUrl,
                'xendit' => $xenditWebhookUrl,
            ],
            'webhookWarnings' => $webhookWarnings,
        ]);
    }

    public function update(Request $request)
    {
        $setting = PaymentSetting::firstOrCreate([], [
            'default_gateway' => 'cash',
        ]);
        $beforeState = $setting->replicate();

        $data = $request->validate([
            'default_gateway' => [
                'required',
                Rule::in([
                    'cash',
                    PaymentSetting::GATEWAY_BANK_TRANSFER,
                    PaymentSetting::GATEWAY_QRIS_STATIC,
                    PaymentSetting::GATEWAY_DOKU,
                    PaymentSetting::GATEWAY_FLIP,
                    PaymentSetting::GATEWAY_MIDTRANS,
                    PaymentSetting::GATEWAY_XENDIT,
                ]),
            ],
            'bank_transfer_enabled' => ['boolean'],
            'midtrans_enabled' => ['boolean'],
            'midtrans_server_key' => ['nullable', 'string'],
            'midtrans_client_key' => ['nullable', 'string'],
            'midtrans_production' => ['boolean'],
            'xendit_enabled' => ['boolean'],
            'xendit_secret_key' => ['nullable', 'string'],
            'xendit_public_key' => ['nullable', 'string'],
            'xendit_callback_token' => ['nullable', 'string', 'max:255'],
            'xendit_production' => ['boolean'],
            'qris_enabled'       => ['boolean'],
            'qris_image'         => ['nullable', 'image', 'mimes:png,jpg', 'max:2048'],
            'qris_merchant_name' => ['nullable', 'string', 'max:100'],
            'qris_nmid'          => ['nullable', 'string', 'max:50'],
            'doku_enabled'       => ['boolean'],
            'doku_client_id'     => ['nullable', 'string'],
            'doku_secret_key'    => ['nullable', 'string'],
            'doku_production'    => ['boolean'],
            'flip_enabled'       => ['boolean'],
            'flip_secret_key'    => ['nullable', 'string'],
            'flip_validation_token' => ['nullable', 'string'],
            'flip_production'    => ['boolean'],
        ]);

        $midtransEnabled = (bool) ($data['midtrans_enabled'] ?? false);
        $xenditEnabled = (bool) ($data['xendit_enabled'] ?? false);
        $resolvedMidtransServerKey = $setting->secretManagedByEnvironment('midtrans_server_key')
            ? $setting->resolvedSecret('midtrans_server_key')
            : ($data['midtrans_server_key'] ?: $setting->getAttributeValue('midtrans_server_key'));
        $resolvedXenditSecretKey = $setting->secretManagedByEnvironment('xendit_secret_key')
            ? $setting->resolvedSecret('xendit_secret_key')
            : ($data['xendit_secret_key'] ?: $setting->getAttributeValue('xendit_secret_key'));
        $resolvedXenditCallbackToken = $setting->secretManagedByEnvironment('xendit_callback_token')
            ? $setting->resolvedSecret('xendit_callback_token')
            : ($data['xendit_callback_token'] ?: $setting->getAttributeValue('xendit_callback_token'));

        if ($midtransEnabled && (blank($resolvedMidtransServerKey) || empty($data['midtrans_client_key']))) {
            return back()->withErrors([
                'midtrans_server_key' => 'Server key dan Client key Midtrans wajib diisi saat mengaktifkan Midtrans.',
            ])->withInput();
        }

        if ($xenditEnabled && blank($resolvedXenditSecretKey)) {
            return back()->withErrors([
                'xendit_secret_key' => 'Secret key Xendit wajib diisi saat mengaktifkan Xendit.',
            ])->withInput();
        }

        if ($xenditEnabled && blank($resolvedXenditCallbackToken)) {
            return back()->withErrors([
                'xendit_callback_token' => 'Callback token Xendit wajib diisi saat mengaktifkan Xendit.',
            ])->withInput();
        }

        $qrisEnabled  = (bool) ($data['qris_enabled'] ?? false);
        $dokuEnabled  = (bool) ($data['doku_enabled'] ?? false);
        $flipEnabled  = (bool) ($data['flip_enabled'] ?? false);

        // Handle upload gambar QRIS
        $qrisImagePath = $setting->qris_image_path;
        if ($request->hasFile('qris_image')) {
            if ($qrisImagePath) {
                Storage::disk('public')->delete($qrisImagePath);
            }
            $qrisImagePath = $request->file('qris_image')->store('qris', 'public');
        }

        if (
            $data['default_gateway'] !== 'cash'
            && ! (
                ($data['default_gateway'] === PaymentSetting::GATEWAY_BANK_TRANSFER && $setting->isBankTransferReady())
                || ($data['default_gateway'] === PaymentSetting::GATEWAY_QRIS_STATIC && ($qrisEnabled && ($qrisImagePath || $setting->qris_image_path)))
                || ($data['default_gateway'] === PaymentSetting::GATEWAY_DOKU       && $dokuEnabled)
                || ($data['default_gateway'] === PaymentSetting::GATEWAY_FLIP       && $flipEnabled)
                || ($data['default_gateway'] === PaymentSetting::GATEWAY_MIDTRANS   && $midtransEnabled)
                || ($data['default_gateway'] === PaymentSetting::GATEWAY_XENDIT     && $xenditEnabled)
            )
        ) {
            return back()->withErrors([
                'default_gateway' => 'Gateway default harus dalam kondisi aktif.',
            ])->withInput();
        }

        $setting->update([
            'default_gateway'      => $data['default_gateway'],
            'bank_transfer_enabled' => (bool) ($data['bank_transfer_enabled'] ?? false),
            // QRIS Statis
            'qris_enabled'         => $qrisEnabled,
            'qris_image_path'      => $qrisImagePath,
            'qris_merchant_name'   => $data['qris_merchant_name'] ?? null,
            'qris_nmid'            => $data['qris_nmid'] ?? null,
            // Midtrans
            'midtrans_enabled'     => $midtransEnabled,
            'midtrans_server_key'  => $setting->secretManagedByEnvironment('midtrans_server_key')
                ? $setting->getRawOriginal('midtrans_server_key')
                : ($data['midtrans_server_key'] ?: $setting->getAttributeValue('midtrans_server_key')),
            'midtrans_client_key'  => $data['midtrans_client_key'],
            'midtrans_production'  => (bool) ($data['midtrans_production'] ?? false),
            // Xendit
            'xendit_enabled'       => $xenditEnabled,
            'xendit_secret_key'    => $setting->secretManagedByEnvironment('xendit_secret_key')
                ? $setting->getRawOriginal('xendit_secret_key')
                : ($data['xendit_secret_key'] ?: $setting->getAttributeValue('xendit_secret_key')),
            'xendit_public_key'    => $data['xendit_public_key'],
            'xendit_callback_token' => $setting->secretManagedByEnvironment('xendit_callback_token')
                ? $setting->getRawOriginal('xendit_callback_token')
                : ($data['xendit_callback_token'] ?: $setting->getAttributeValue('xendit_callback_token')),
            'xendit_production'    => (bool) ($data['xendit_production'] ?? false),
            // DOKU
            'doku_enabled'         => $dokuEnabled,
            'doku_client_id'       => $data['doku_client_id'] ?? null,
            'doku_secret_key'      => $setting->secretManagedByEnvironment('doku_secret_key')
                ? $setting->getRawOriginal('doku_secret_key')
                : ($data['doku_secret_key'] ?: $setting->getAttributeValue('doku_secret_key')),
            'doku_production'      => (bool) ($data['doku_production'] ?? false),
            // Flip
            'flip_enabled'         => $flipEnabled,
            'flip_secret_key'      => $setting->secretManagedByEnvironment('flip_secret_key')
                ? $setting->getRawOriginal('flip_secret_key')
                : ($data['flip_secret_key'] ?: $setting->getAttributeValue('flip_secret_key')),
            'flip_validation_token' => $setting->secretManagedByEnvironment('flip_validation_token')
                ? $setting->getRawOriginal('flip_validation_token')
                : ($data['flip_validation_token'] ?: $setting->getAttributeValue('flip_validation_token')),
            'flip_production'      => (bool) ($data['flip_production'] ?? false),
        ]);

        $this->auditLogService->log(
            event: 'payment.setting.updated',
            module: 'payment_settings',
            auditable: $setting,
            description: 'Konfigurasi payment gateway diperbarui.',
            before: [
                'default_gateway' => $beforeState->default_gateway,
                'bank_transfer_enabled' => (bool) $beforeState->bank_transfer_enabled,
                'midtrans_enabled' => (bool) $beforeState->midtrans_enabled,
                'midtrans_production' => (bool) $beforeState->midtrans_production,
                'xendit_enabled' => (bool) $beforeState->xendit_enabled,
                'xendit_production' => (bool) $beforeState->xendit_production,
                'midtrans_server_key' => filled($beforeState->midtrans_server_key) ? 'configured' : 'empty',
                'midtrans_client_key' => filled($beforeState->midtrans_client_key) ? 'configured' : 'empty',
                'xendit_secret_key' => filled($beforeState->xendit_secret_key) ? 'configured' : 'empty',
                'xendit_public_key' => filled($beforeState->xendit_public_key) ? 'configured' : 'empty',
                'xendit_callback_token' => filled($beforeState->xendit_callback_token) ? 'configured' : 'empty',
            ],
            after: [
                'default_gateway' => $setting->default_gateway,
                'bank_transfer_enabled' => (bool) $setting->bank_transfer_enabled,
                'midtrans_enabled' => (bool) $setting->midtrans_enabled,
                'midtrans_production' => (bool) $setting->midtrans_production,
                'xendit_enabled' => (bool) $setting->xendit_enabled,
                'xendit_production' => (bool) $setting->xendit_production,
                'midtrans_server_key' => $this->auditLogService->credentialState($beforeState->midtrans_server_key, $setting->midtrans_server_key),
                'midtrans_client_key' => $this->auditLogService->credentialState($beforeState->midtrans_client_key, $setting->midtrans_client_key),
                'xendit_secret_key' => $this->auditLogService->credentialState($beforeState->xendit_secret_key, $setting->xendit_secret_key),
                'xendit_public_key' => $this->auditLogService->credentialState($beforeState->xendit_public_key, $setting->xendit_public_key),
                'xendit_callback_token' => $this->auditLogService->credentialState($beforeState->xendit_callback_token, $setting->xendit_callback_token),
            ],
        );

        if (collect($setting->paymentSettingSources())->contains(fn (array $source) => $source['source'] === 'env')) {
            $this->auditLogService->log(
                event: 'security.payment_secret_source_overridden',
                module: 'security',
                auditable: $setting,
                description: 'Perubahan payment settings tetap tunduk pada env override untuk secret sensitif.',
                meta: [
                    'severity' => 'info',
                    'sources' => collect($setting->paymentSettingSources())
                        ->filter(fn (array $source) => $source['source'] === 'env')
                        ->keys()
                        ->values()
                        ->all(),
                ],
            );
        }

        return redirect()
            ->route('settings.payments.edit')
            ->with('success', 'Konfigurasi payment gateway berhasil disimpan.');
    }

    private function isLocalAppUrl(string $appUrl): bool
    {
        $host = parse_url($appUrl, PHP_URL_HOST);

        return in_array($host, ['localhost', '127.0.0.1'], true)
            || str_ends_with((string) $host, '.test');
    }
}