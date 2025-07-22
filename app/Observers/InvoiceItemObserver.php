<?php

namespace App\Observers;

use App\Models\InvoiceItem;
use App\Services\InventoryService;

class InvoiceItemObserver
{
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
