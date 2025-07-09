<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'sku',
        'purchase_price',
        'selling_price',
        'stock',
        'unit',
        'volume_value',
        'base_volume_unit',
        // 'is_convertible', // Dihapus
        // 'target_child_item_id', // Dihapus
        // 'conversion_value', // Dihapus
    ];

    protected $casts = [
        'stock' => 'integer',
        'volume_value' => 'decimal:2',
        // 'conversion_value' => 'decimal:2', // Dihapus
        // 'is_convertible' => 'boolean', // Dihapus
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'invoice_item');
    }

    // Relasi targetChild() dan sourceParents() sudah tidak relevan dan akan dihapus.
    // Jika masih ada referensi di kode lain (seperti di ItemResource untuk createOptionForm),
    // itu perlu di-refactor atau dihapus juga jika tidak lagi digunakan.
    // Untuk saat ini, kita hapus definisinya di sini.

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
