<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Toko = pengelompokan studio photobox (mis. POPSNAP, CCM). */
class PhotoboxStore extends Model
{
    protected $fillable = ['name'];

    public function studios()
    {
        return $this->hasMany(PhotoboxStudio::class, 'store_id');
    }
}
