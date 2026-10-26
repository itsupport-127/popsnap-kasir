<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoothMedia extends Model
{
    protected $table = 'booth_media';
    protected $fillable = ['session_id', 'kind', 'name', 'path', 'size', 'mime', 'deleted_at'];
    protected $casts = ['deleted_at' => 'datetime', 'size' => 'integer'];

    public function session()
    {
        return $this->belongsTo(BoothSession::class, 'session_id');
    }
}
