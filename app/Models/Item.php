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
        'parent_item_id', // Changed from parent_sku
        'conversion_value',
        'base_unit',
    ];

    protected $casts = [
        'stock' => 'integer',
        'conversion_value' => 'decimal:2', // Good to cast decimal
    ];

    public function typeItem()
    {
        return $this->belongsTo(TypeItem::class);
    }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'invoice_item');
    }

    /**
     * Get the parent item that this item belongs to (if it's an eceran item).
     */
    public function parent()
    {
        return $this->belongsTo(Item::class, 'parent_item_id');
    }

    /**
     * Get the child items (eceran items) for this item (if it's a parent item).
     */
    public function children()
    {
        return $this->hasMany(Item::class, 'parent_item_id');
    }
}
