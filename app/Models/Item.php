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
        'is_convertible',
        'target_child_item_id',
        'conversion_value',
    ];

    protected $casts = [
        'stock' => 'integer',
        'conversion_value' => 'decimal:2',
        'is_convertible' => 'boolean',
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

    /**
     * Get the display name for this item (product name + variant if applicable)
     */
    public function getDisplayNameAttribute(): string
    {
        if (!$this->product) {
            return 'Unknown Product';
        }

        $productName = $this->product->name;
        $variantName = $this->name;

        // Jika produk tanpa varian (name kosong atau null), tampilkan hanya nama produk
        if (empty($variantName) || is_null($variantName)) {
            return $productName;
        }

        // Jika produk dengan varian, tampilkan nama produk + varian
        return $productName . ' ' . $variantName;
    }
}
