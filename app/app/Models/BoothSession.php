<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoothSession extends Model
{
    protected $fillable = [
        'uuid', 'owner_id', 'outlet_id', 'device_id', 'payment_id', 'voucher_code', 'template_name', 'photos_count',
        'started_at', 'finished_at', 'share_token', 'customer_phone', 'wa_status', 'was_offline',
        'media_status', 'media_expected', 'meta',
    ];
    protected $attributes = ['wa_status' => 'none', 'media_status' => 'pending', 'photos_count' => 0, 'media_expected' => 0];
    protected $casts = [
        'started_at' => 'datetime', 'finished_at' => 'datetime', 'was_offline' => 'boolean',
        'meta' => 'array', 'photos_count' => 'integer', 'media_expected' => 'integer',
    ];

    public function media()
    {
        return $this->hasMany(BoothMedia::class, 'session_id');
    }

    public function outlet()
    {
        return $this->belongsTo(BoothOutlet::class, 'outlet_id');
    }

    public function device()
    {
        return $this->belongsTo(BoothDevice::class, 'device_id');
    }

    public function payment()
    {
        return $this->belongsTo(BoothPayment::class, 'payment_id');
    }

    public function shareUrl(): string
    {
        return url('/s/'.$this->share_token);
    }

    public function refreshMediaStatus(): void
    {
        $n = $this->media()->whereNull('deleted_at')->count();
        $status = $n === 0 ? 'pending' : (($this->media_expected > 0 && $n < $this->media_expected) ? 'partial' : 'complete');
        $this->update(['media_status' => $status]);
    }
}
