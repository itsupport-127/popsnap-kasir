<?php

namespace Database\Seeders;

use App\Models\PaymentSetting;
use Illuminate\Database\Seeder;

class PaymentSettingSeeder extends Seeder
{
    public function run(): void
    {
        PaymentSetting::firstOrCreate([], [
            'default_gateway'      => 'cash',
            'bank_transfer_enabled' => false,
            // QRIS Statis
            'qris_enabled'         => false,
            'qris_image_path'      => null,
            'qris_merchant_name'   => 'Popsnap',
            'qris_nmid'            => null,
            // DOKU
            'doku_enabled'         => false,
            'doku_client_id'       => null,
            'doku_secret_key'      => null,
            'doku_production'      => false,
            // Flip
            'flip_enabled'         => false,
            'flip_secret_key'      => null,
            'flip_validation_token' => null,
            'flip_production'      => false,
            // Midtrans
            'midtrans_enabled'     => false,
            'midtrans_server_key'  => null,
            'midtrans_client_key'  => null,
            'midtrans_production'  => false,
            // Xendit
            'xendit_enabled'       => false,
            'xendit_secret_key'    => null,
            'xendit_public_key'    => null,
            'xendit_callback_token' => null,
            'xendit_production'    => false,
        ]);
    }
}