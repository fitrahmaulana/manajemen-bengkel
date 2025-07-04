<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'purchase_price',
        'selling_price',
        'stock',
        'unit',
        'target_child_item_id',
        'conversion_value',
    ];

    /**
     * Check if this item is convertible (has conversion settings)
     */
    public function getIsConvertibleAttribute(): bool
    {
        return $this->target_child_item_id !== null && $this->conversion_value > 0;
    }

    protected $casts = [
        'stock' => 'integer',
        'conversion_value' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
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
