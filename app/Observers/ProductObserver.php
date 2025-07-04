<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\Item;
use Illuminate\Support\Facades\Log;

class ProductObserver
{
    /**
     * Handle the Product "created" event.
     */
    public function created(Product $product): void
    {
        // Jika produk tidak memiliki varian, buat item default
        if (!$product->has_variants) {
            // Cek dulu apakah sudah ada item untuk produk ini
            if ($product->items()->count() == 0) {
                $this->createDefaultItem($product);
            }
        }
        // Jika produk memiliki varian tapi tidak ada data varian,
        // buat item default sementara dengan nama "Belum Ada Varian"
        elseif ($product->has_variants) {
            // Cek dulu apakah sudah ada item untuk produk ini
            if ($product->items()->count() == 0) {
                $this->createPlaceholderItem($product);
            }
        }
    }

    /**
     * Handle the Product "updated" event.
     */
    public function updated(Product $product): void
    {
        // Jika produk diubah dari varian ke non-varian dan belum ada item
        if (!$product->has_variants && $product->items()->count() == 0) {
            $this->createDefaultItem($product);
        }
    }

    /**
     * Create default item for non-variant product
     */
    private function createDefaultItem(Product $product): void
    {
        // Ambil data dari session (disimpan dari CreateProduct)
        $formData = session('product_form_data', []);

        // Clear session data setelah digunakan
        session()->forget('product_form_data');

        Item::create([
            'product_id' => $product->id,
            'name' => '', // Empty string untuk produk standard
            'sku' => $formData['standard_sku'] ?? $this->generateDefaultSKU($product),
            'unit' => $formData['standard_unit'] ?? 'Pcs',
            'purchase_price' => $formData['standard_purchase_price'] ?? 0,
            'selling_price' => $formData['standard_selling_price'] ?? 0,
            'stock' => $formData['standard_stock'] ?? 0,
            'target_child_item_id' => null,
            'conversion_value' => null,
        ]);
    }

    /**
     * Create placeholder item for variant product without variants
     */
    private function createPlaceholderItem(Product $product): void
    {
        Item::create([
            'product_id' => $product->id,
            'name' => 'Belum Ada Varian',
            'sku' => $this->generateDefaultSKU($product) . '-TEMP',
            'unit' => 'Pcs',
            'purchase_price' => 0,
            'selling_price' => 0,
            'stock' => 0,
            'target_child_item_id' => null,
            'conversion_value' => null,
        ]);
    }

    /**
     * Generate default SKU if not provided
     */
    private function generateDefaultSKU(Product $product): string
    {
        $productCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $product->name), 0, 6));
        return $productCode . '-STD';
    }
}
