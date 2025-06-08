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
        'type_item_id',
    ];

    public function typeItem()
    {
        return $this->belongsTo(TypeItem::class);
    }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'invoice_item');
    }
}
