<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BoothDevice extends Model
{
    protected $fillable = ['owner_id', 'outlet_id', 'name', 'prefix', 'token_hash', 'is_active', 'last_seen_at', 'last_ip'];
    protected $hidden = ['token_hash'];
    protected $casts = ['is_active' => 'boolean', 'last_seen_at' => 'datetime'];

    public function outlet()
    {
        return $this->belongsTo(BoothOutlet::class, 'outlet_id');
    }

    public static function hashToken(string $token): string
    {
        return hash('sha256', $token);
    }

    /** Buat perangkat baru. Token asli hanya dikembalikan sekali ini. */
    public static function register(BoothOutlet $outlet, string $name): array
    {
        $token = 'pb_'.Str::random(40);
        do {
            $prefix = strtoupper(Str::random(3));
        } while (static::where('prefix', $prefix)->exists());

        $device = static::create([
            'owner_id' => $outlet->owner_id,
            'outlet_id' => $outlet->id,
            'name' => $name,
            'prefix' => $prefix,
            'token_hash' => static::hashToken($token),
        ]);

        return [$device, $token];
    }
}
