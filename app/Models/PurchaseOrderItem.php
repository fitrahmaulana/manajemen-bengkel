<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * @mixin IdeHelperPurchaseOrderItem
 */
class PurchaseOrderItem extends Pivot
{
    public $incrementing = true;

    protected $table = 'purchase_order_items';

    protected $casts = [
        'quantity' => 'decimal:1',
    ];

    public function getQuantityAttribute($value)
    {
        return $value + 0;
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }
}
