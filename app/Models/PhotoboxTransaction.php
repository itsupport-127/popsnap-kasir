<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhotoboxTransaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_no', 'studio_id', 'template_id', 'template_name', 'category', 'mode', 'method', 'voucher_code',
        'amount', 'status', 'is_test', 'paid_at', 'booth_created_at', 'session_id', 'gallery_url', 'meta', 'edited_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'is_test' => 'boolean',
        'paid_at' => 'datetime',
        'booth_created_at' => 'datetime',
        'edited_at' => 'datetime',
        'meta' => 'array',
    ];

    /** Hanya transaksi uang asli. Transaksi uji (simulasi / sandbox) tampil di riwayat tapi tidak boleh masuk pendapatan, shift, atau dashboard. */
    public function scopeReal($query)
    {
        return $query->where('photobox_transactions.is_test', false);
    }

    public function studio()
    {
        return $this->belongsTo(PhotoboxStudio::class, 'studio_id');
    }
}
