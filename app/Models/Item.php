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
        // 'parent_item_id', // Removed
        // 'conversion_value', // Removed
        // 'base_unit', // Removed
    ];

    protected $casts = [
        'stock' => 'integer',
        // 'conversion_value' => 'decimal:2', // Removed
    ];

    public function typeItem()
    {
        return $this->belongsTo(TypeItem::class);
    }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'invoice_item');
    }

    // Old parent() and children() relationships based on parent_item_id are removed.

    /**
     * The child items that this item can be converted into.
     * (This item is the PARENT in the conversion)
     */
    public function conversionChildren()
    {
        return $this->belongsToMany(Item::class, 'item_conversions', 'parent_item_id', 'child_item_id')
                    ->withPivot('conversion_value', 'id') // 'id' here is the id of the pivot record
                    ->withTimestamps();
    }

    /**
     * The parent items from which this item can be sourced.
     * (This item is the CHILD in the conversion)
     */
    public function conversionParents()
    {
        return $this->belongsToMany(Item::class, 'item_conversions', 'child_item_id', 'parent_item_id')
                    ->withPivot('conversion_value', 'id') // 'id' here is the id of the pivot record
                    ->withTimestamps();
    }
}
