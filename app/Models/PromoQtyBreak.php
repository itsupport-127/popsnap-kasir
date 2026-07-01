<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromoQtyBreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'promo_id',
        'min_qty',
        'discount_type',
        'discount_value',
        'sort_order',
    ];

    protected $casts = [
        'promo_id' => 'integer',
        'min_qty' => 'integer',
        'discount_value' => 'float',
        'sort_order' => 'integer',
    ];

    public function promo()
    {
        return $this->belongsTo(Promo::class);
    }
}
