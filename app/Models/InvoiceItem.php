<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @mixin IdeHelperInvoiceItem
 */
class InvoiceItem extends Pivot
{
    public $timestamps = false;

    public $incrementing = true;

    protected $table = 'invoice_item';

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
