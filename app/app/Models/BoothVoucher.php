<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoothVoucher extends Model
{
    protected $fillable = ['owner_id', 'outlet_id', 'device_id', 'code', 'source', 'max_uses', 'used_count', 'expires_at', 'is_active', 'note'];
    protected $casts = ['expires_at' => 'datetime', 'is_active' => 'boolean', 'max_uses' => 'integer', 'used_count' => 'integer'];

    public function isUsable(): bool
    {
        return $this->is_active
            && $this->used_count < $this->max_uses
            && (! $this->expires_at || $this->expires_at->isFuture());
    }

    public function scopeUsable($q)
    {
        return $q->where('is_active', true)
            ->whereColumn('used_count', '<', 'max_uses')
            ->where(fn ($w) => $w->whereNull('expires_at')->orWhere('expires_at', '>', now()));
    }
}
