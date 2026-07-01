<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromoBundleItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'promo_id',
        'product_id',
        'quantity',
        'sort_order',
    ];

    protected $casts = [
        'promo_id' => 'integer',
        'product_id' => 'integer',
        'quantity' => 'integer',
        'sort_order' => 'integer',
    ];

    public function promo()
    {
        return $this->belongsTo(Promo::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
