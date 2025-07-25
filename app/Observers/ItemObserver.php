<?php

namespace App\Observers;

use App\Models\Item;
use App\Models\Product;

class ItemObserver
{
    /**
     * Handle the Item "created" event.
     */
    public function created(Item $item): void
    {
        $this->updateProductVariantStatus($item->product);
    }

    /**
     * Handle the Item "deleted" event.
     */
    public function deleted(Item $item): void
    {
        $this->updateProductVariantStatus($item->product);
    }

    /**
     * Update the has_variant status on the product.
     */
    protected function updateProductVariantStatus(Product $product): void
    {
        $product->update([
            'has_variants' => $product->items()->count() > 1,
        ]);
    }
}
