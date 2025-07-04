<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeItem extends Model
{
    protected $fillable = ['name', 'description'];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function items()
    {
        return $this->hasManyThrough(Item::class, Product::class);
    }
}
