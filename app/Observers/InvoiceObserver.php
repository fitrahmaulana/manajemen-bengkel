<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Services\InventoryService;

class InvoiceObserver
{
    /**
     * Handle the Invoice "deleting" event.
     */
    public function deleting(Invoice $invoice): void
    {
        if ($invoice->isForceDeleting()) {
            return;
        }
        $inventoryService = app(InventoryService::class);
        foreach ($invoice->invoiceItems as $invoiceItem) {
            // Restore stock by passing a negative value, which gets subtracted (thus, added)
            $inventoryService->adjustStockForItem($invoiceItem->item_id, -$invoiceItem->quantity);
        }
    }

    /**
     * Handle the Invoice "restored" event.
     */
    public function restored(Invoice $invoice): void
    {
        $inventoryService = app(InventoryService::class);
        foreach ($invoice->invoiceItems as $invoiceItem) {
            // Re-deduct stock for the restored invoice
            $inventoryService->adjustStockForItem($invoiceItem->item_id, $invoiceItem->quantity);
        }
    }
}
