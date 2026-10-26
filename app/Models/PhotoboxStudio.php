<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PhotoboxStudio extends Model
{
    protected $fillable = ['name', 'token_hash', 'token_hint', 'is_active', 'last_seen_at'];

    protected $casts = ['is_active' => 'boolean', 'last_seen_at' => 'datetime'];

    protected $hidden = ['token_hash'];

    public function transactions()
    {
        return $this->hasMany(PhotoboxTransaction::class, 'studio_id');
    }

    /** Buat token baru: simpan hash-nya, kembalikan token asli (tampil sekali). */
    public function issueToken(): string
    {
        $token = 'pbx_'.Str::random(40);
        $this->forceFill(['token_hash' => hash('sha256', $token), 'token_hint' => substr($token, -4)])->save();

        return $token;
    }

    public static function findByToken(?string $token): ?self
    {
        if (! $token) {
            return null;
        }

        return static::where('token_hash', hash('sha256', $token))->where('is_active', true)->first();
    }
}
