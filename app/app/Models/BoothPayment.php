<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoothPayment extends Model
{
    protected $fillable = [
        'owner_id', 'outlet_id', 'device_id', 'client_ref', 'transaction_id', 'amount', 'status',
        'qr_string', 'qr_url', 'gateway', 'gateway_ref', 'expires_at', 'paid_at',
    ];
    protected $casts = ['amount' => 'integer', 'expires_at' => 'datetime', 'paid_at' => 'datetime'];

    public function transaction()
    {
        return $this->belongsTo(Transaction::class);
    }
}
