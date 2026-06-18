<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentSettingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'default_gateway'      => 'cash',
            // QRIS Statis
            'qris_enabled'         => false,
            'qris_image_path'      => null,
            'qris_merchant_name'   => null,
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
        ];
    }
}