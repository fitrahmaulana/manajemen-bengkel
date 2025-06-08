<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeItem extends Model
{
    protected $fillable = ['name', 'description'];

    public function items()
    {
        return $this->hasMany(Item::class);
    }
}
