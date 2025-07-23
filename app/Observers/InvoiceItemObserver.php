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
        $oldQty = $invoiceItem->getOriginal('quantity') ?? 0;
        $oldItemId = $invoiceItem->getOriginal('item_id') ?? $invoiceItem->item_id;

        $newQty = $invoiceItem->quantity;
        $newItemId = $invoiceItem->item_id;

        // Kalau tidak ada perubahan qty & item_id, berhenti
        if ($oldQty == $newQty && $oldItemId == $newItemId) {
            return;
        }

        $inventoryService = app(InventoryService::class);

        // 1. Kembalikan stok lama ke item lama
        //    (oldQty sudah mengurangi stok saat pertama dibuat)
        if ($oldQty > 0) {
            $inventoryService->adjustStockForItem($oldItemId, -$oldQty);
        }

        // 2. Kurangi stok item baru sesuai qty baru
        if ($newQty > 0) {
            $inventoryService->adjustStockForItem($newItemId, $newQty);
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
