<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperItem
 */
class Item extends Model
{
    use HasFactory;

    public const LOW_STOCK_THRESHOLD = 5;

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'purchase_price',
        'selling_price',
        'stock',
        'minimum_stock',
        'unit',
        'volume_value',
        'base_volume_unit',
        'supplier_id',
    ];

    protected $casts = [
        'stock' => 'decimal:1',
        'volume_value' => 'decimal:2',
    ];

    public function getStockAttribute($value)
    {
        return $value + 0;
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'invoice_item');
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Get all stock conversions where this item was the source.
     */
    public function stockConversionsFrom(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ItemStockConversion::class, 'from_item_id');
    }

    /**
     * Get all stock conversions where this item was the destination.
     */
    public function stockConversionsTo(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ItemStockConversion::class, 'to_item_id');
    }

    /**
     * Get the display name for this item (product name + variant if applicable)
     */
    public function getDisplayNameAttribute(): string
    {
        if (! $this->product) {
            return 'Unknown Product';
        }

        $productName = $this->product->name;
        $variantName = $this->name;

        // Jika produk tanpa varian (name kosong atau null), tampilkan hanya nama produk
        if (empty($variantName) || is_null($variantName)) {
            return $productName;
        }

        // Jika produk dengan varian, tampilkan nama produk + varian
        return $productName.' '.$variantName;
    }
}
