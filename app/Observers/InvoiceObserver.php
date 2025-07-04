<?php

namespace App\Observers;

use App\Models\Invoice;
use App\Models\Item; // Make sure Item model is imported

class InvoiceObserver
{
    /**
     * Handle the Invoice "deleting" event.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return void
     */
    public function deleting(Invoice $invoice)
    {
        // Iterate through each item associated with the invoice
        foreach ($invoice->items as $itemPivot) {
            // $itemPivot is an instance of Item, with pivot data loaded
            // Access pivot data like quantity: $itemPivot->pivot->quantity
            $itemModel = Item::find($itemPivot->id); // Get a fresh instance of the item to be safe
            if ($itemModel) {
                $quantityToRestore = $itemPivot->pivot->quantity;
                $itemModel->stock += $quantityToRestore;
                $itemModel->save();
            }
        }
    }

    /**
     * Handle the Invoice "created" event.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return void
     */
    public function created(Invoice $invoice)
    {
        //
    }

    /**
     * Handle the Invoice "updated" event.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return void
     */
    public function updated(Invoice $invoice)
    {
        //
    }

    /**
     * Handle the Invoice "deleted" event.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return void
     */
    public function deleted(Invoice $invoice)
    {
        //
    }

    /**
     * Handle the Invoice "restored" event.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return void
     */
    public function restored(Invoice $invoice)
    {
        // Re-decrement stock for items on the restored invoice
        foreach ($invoice->items as $itemPivot) {
            $itemModel = Item::find($itemPivot->id);
            if ($itemModel) {
                $quantityToDecrement = $itemPivot->pivot->quantity;
                $itemModel->stock -= $quantityToDecrement;
                $itemModel->save();
                // Consider adding a check here if stock would go negative and how to handle it,
                // though for now, we'll assume it's allowed or handled elsewhere.
            }
        }
    }

    /**
     * Handle the Invoice "force deleted" event.
     *
     * @param  \App\Models\Invoice  $invoice
     * @return void
     */
    public function forceDeleted(Invoice $invoice)
    {
        //
    }
}
