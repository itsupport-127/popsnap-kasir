<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PhotoboxTransaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_no', 'studio_id', 'template_id', 'template_name', 'category', 'mode', 'method', 'voucher_code',
        'amount', 'status', 'paid_at', 'booth_created_at', 'session_id', 'meta', 'edited_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'paid_at' => 'datetime',
        'booth_created_at' => 'datetime',
        'edited_at' => 'datetime',
        'meta' => 'array',
    ];

    public function studio()
    {
        return $this->belongsTo(PhotoboxStudio::class, 'studio_id');
    }
}
