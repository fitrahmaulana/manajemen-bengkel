<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'name',
        'sku',
        'brand',
        'purchase_price',
        'selling_price',
        'stock',
        'unit',
        'location',
    ];

    protected $casts = [
        'stock' => 'integer',
    ];
}
