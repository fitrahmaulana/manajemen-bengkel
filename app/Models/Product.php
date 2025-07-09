<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'brand',
        'description',
        'type_item_id',
        'has_variants',
    ];

    protected $casts = [
        'has_variants' => 'boolean',
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

