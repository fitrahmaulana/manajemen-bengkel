<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Item;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceStockService
{
    /**
     * Deduct stock for items in an invoice.
     *
     * @param Invoice $invoice The invoice instance (optional, for context/logging)
     * @param array $rawItemsData Array of items from the form/request,
     *                           e.g., [['item_id' => 1, 'quantity' => 2.5], ...]
     * @throws \Exception
     */
    public function deductStockForInvoiceItems(Invoice $invoice, array $rawItemsData): void
    {
        if (empty($rawItemsData)) {
            return;
        }

        DB::transaction(function () use ($rawItemsData, $invoice) {
            foreach ($rawItemsData as $rawItemData) {
                $itemId = $rawItemData['item_id'] ?? null;
                $quantity = (float)($rawItemData['quantity'] ?? 0.0);

                if (!$itemId || $quantity <= 0) {
                    continue;
                }

                $itemModel = Item::find($itemId);
                if ($itemModel) {
                    $originalStock = $itemModel->stock;
                    $newStock = $originalStock - $quantity;
                    $itemModel->stock = $newStock;
                    $itemModel->save();

                    Log::info("DEDUCT: Item ID {$itemId}, Invoice ID {$invoice->id}. Original Stock: {$originalStock}, Quantity: {$quantity}, New Stock: {$newStock}");
                } else {
                    Log::warning("DEDUCT FAILED: Item ID {$itemId} not found for Invoice ID {$invoice->id}.");
                }
            }
        });
    }

    /**
     * Restore stock for items in an invoice.
     *
     * @param Invoice $invoice The invoice instance whose items' stock needs to be restored.
     * @throws \Exception
     */
    public function restoreStockForInvoiceItems(Invoice $invoice): void
    {
        $invoiceItems = $invoice->invoiceItems()->with('item')->get();

        if ($invoiceItems->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($invoiceItems, $invoice) {
            foreach ($invoiceItems as $invoiceItem) {
                $itemModel = $invoiceItem->item;
                $quantityToRestore = (float)($invoiceItem->quantity ?? 0.0);

                if (!$itemModel || $quantityToRestore <= 0) {
                    continue;
                }

                $originalStock = $itemModel->stock;
                $newStock = $originalStock + $quantityToRestore;
                $itemModel->stock = $newStock;
                $itemModel->save();

                Log::info("RESTORE: Item ID {$itemModel->id}, Invoice ID {$invoice->id}. Original Stock: {$originalStock}, Quantity: {$quantityToRestore}, New Stock: {$newStock}");
            }
        });
    }

    /**
     * Adjust stock for a single item.
     *
     * @param int $itemId
     * @param float $quantityChange Positive to deduct, negative to restore.
     */
    public function adjustStockForItem(int $itemId, float $quantityChange): void
    {
        if ($quantityChange == 0) {
            return;
        }

        $itemModel = Item::find($itemId);
        if ($itemModel) {
            DB::transaction(function () use ($itemModel, $quantityChange, $itemId) {
                $originalStock = $itemModel->stock;
                $newStock = $originalStock - $quantityChange;
                $itemModel->stock = $newStock;
                $itemModel->save();

                Log::info("ADJUST: Item ID {$itemId}. Original Stock: {$originalStock}, Change: {$quantityChange}, New Stock: {$newStock}");
            });
        } else {
            Log::warning("ADJUST FAILED: Item ID {$itemId} not found.");
        }
    }
}
