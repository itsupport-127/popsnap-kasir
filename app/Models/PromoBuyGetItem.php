<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PromoBuyGetItem extends Model
{
    use HasFactory;

    public const ROLE_BUY = 'buy';

    public const ROLE_GET = 'get';

    protected $fillable = [
        'promo_id',
        'product_id',
        'role',
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
