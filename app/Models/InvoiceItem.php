<?php

namespace App\Models;

use App\Services\InvoiceStockService;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class InvoiceItem extends Pivot
{
    public $timestamps = false;
    public $incrementing = true;

    protected $table = 'invoice_item';
    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::deleted(function (InvoiceItem $invoiceItem) {
            // This logic runs AFTER the record has been deleted.
            // We use a negative quantity to restore the stock.
            if ($invoiceItem->item_id && $invoiceItem->quantity > 0) {
                $stockService = app(InvoiceStockService::class);
                $stockService->adjustStockForItem($invoiceItem->item_id, -$invoiceItem->quantity);
            }
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function getQuantityAttribute($value)
    {
        return $value + 0;
    }
}
