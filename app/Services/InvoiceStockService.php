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
                    continue; // Skip if no item ID or quantity is not positive
                }

                $itemModel = Item::find($itemId);
                if ($itemModel) {
                    // Log before decrementing for traceability
                    Log::info("InvoiceStockService: Decrementing stock for item ID {$itemId} by {$quantity} for invoice ID {$invoice->id}. Current stock: {$itemModel->stock}");

                    $newStock = $itemModel->stock - $quantity;
                    $itemModel->stock = $newStock; // Directly set for precision with decimals
                    $itemModel->save();

                    // Alternative: $itemModel->decrement('stock', $quantity);
                    // Using direct assignment and save might be more explicit for decimal handling,
                    // though decrement should also work correctly with decimal casts on the model.

                    Log::info("InvoiceStockService: New stock for item ID {$itemId} is {$itemModel->stock} after invoice ID {$invoice->id}.");
                } else {
                    Log::warning("InvoiceStockService: Item ID {$itemId} not found while trying to deduct stock for invoice ID {$invoice->id}.");
                }
            }
        });
    }

    /**
     * Restore stock for items in an invoice.
     * Typically used when an invoice is deleted or items are removed/quantity reduced.
     *
     * @param Invoice $invoice The invoice instance whose items' stock needs to be restored.
     * @throws \Exception
     */
    public function restoreStockForInvoiceItems(Invoice $invoice): void
    {
        // Eager load items with pivot data to avoid N+1 queries if not already loaded.
        $invoiceItems = $invoice->items()->get();

        if ($invoiceItems->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($invoiceItems, $invoice) {
            foreach ($invoiceItems as $itemModel) {
                // The quantity to restore is from the pivot table
                $quantityToRestore = (float)($itemModel->pivot->quantity ?? 0.0);

                if ($quantityToRestore <= 0) {
                    continue; // Skip if quantity is not positive
                }

                // Log before incrementing for traceability
                Log::info("InvoiceStockService: Restoring stock for item ID {$itemModel->id} by {$quantityToRestore} from invoice ID {$invoice->id}. Current stock: {$itemModel->stock}");

                $newStock = $itemModel->stock + $quantityToRestore;
                $itemModel->stock = $newStock; // Directly set for precision
                $itemModel->save();

                // Alternative: $itemModel->increment('stock', $quantityToRestore);

                Log::info("InvoiceStockService: New stock for item ID {$itemModel->id} is {$itemModel->stock} after restoring from invoice ID {$invoice->id}.");
            }
        });
    }

    /**
     * Adjust stock for a single item.
     * Can be used for more granular updates during invoice editing.
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
            DB::transaction(function () use ($itemModel, $quantityChange, $itemId) { // Added $itemId to use()
                Log::info("InvoiceStockService: Adjusting stock for item ID {$itemId} by {$quantityChange}. Current stock: {$itemModel->stock}");

                $newStock = $itemModel->stock - $quantityChange; // if quantityChange is positive, stock decreases. if negative, stock increases.
                $itemModel->stock = $newStock;
                $itemModel->save();

                Log::info("InvoiceStockService: New stock for item ID {$itemId} is {$itemModel->stock}.");
            });
        } else {
            Log::warning("InvoiceStockService: Item ID {$itemId} not found while trying to adjust stock.");
        }
    }
}
