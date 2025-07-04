<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'name',
        'brand',
        'description',
        'type_item_id',
    ];

    public function typeItem()
    {
        return $this->belongsTo(TypeItem::class);
    }

    public function items()
    {
        return $this->hasMany(Item::class);
    }
}
