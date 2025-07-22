<?php

namespace App\Observers;

use App\Models\InvoiceItem;
use App\Services\InventoryService;

class InvoiceItemObserver
{
    /**
     * Handle the InvoiceItem "created" event.
     */
    public function created(InvoiceItem $invoiceItem): void
    {
        if ($invoiceItem->item_id && $invoiceItem->quantity > 0) {
            $inventoryService = app(InventoryService::class);
            // Deduct stock for the new item
            $inventoryService->adjustStockForItem($invoiceItem->item_id, $invoiceItem->quantity);
        }
    }

    /**
     * Handle the InvoiceItem "updated" event.
     */
    public function updated(InvoiceItem $invoiceItem): void
    {
        if ($invoiceItem->isDirty('quantity')) {
            $originalQuantity = $invoiceItem->getOriginal('quantity', 0);
            $newQuantity = $invoiceItem->quantity;
            $difference = $newQuantity - $originalQuantity;

            if ($difference != 0) {
                $inventoryService = app(InventoryService::class);
                $inventoryService->adjustStockForItem($invoiceItem->item_id, $difference);
            }
        }
    }

    /**
     * Handle the InvoiceItem "deleted" event.
     */
    public function deleted(InvoiceItem $invoiceItem): void
    {
        if ($invoiceItem->item_id && $invoiceItem->quantity > 0) {
            $inventoryService = app(InventoryService::class);
            // Restore stock by passing a negative value to the quantity change,
            // which will be subtracted, effectively adding to the stock.
            $inventoryService->adjustStockForItem($invoiceItem->item_id, -$invoiceItem->quantity);
        }
    }
}
