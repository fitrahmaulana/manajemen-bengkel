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
        'is_convertible',
        'target_child_item_id',
        'conversion_value',
        // 'base_unit', // Removed
    ];

    protected $casts = [
        'stock' => 'integer',
        'is_convertible' => 'boolean',
        'conversion_value' => 'decimal:2',
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
     * Get the target eceran item for this (parent) item.
     */
    public function targetChild(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Item::class, 'target_child_item_id');
    }

    /**
     * Get all parent items that convert into this (eceran) item.
     */
    public function sourceParents(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Item::class, 'target_child_item_id');
    }
}
