<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoothOutlet extends Model
{
    protected $fillable = ['owner_id', 'name', 'wa_template'];

    public function devices()
    {
        return $this->hasMany(BoothDevice::class, 'outlet_id');
    }
}
