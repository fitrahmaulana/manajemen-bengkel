<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'po_number',
        'order_date',
        'subtotal',
        'total_amount',
        'status',
        'discount_value',
        'discount_type',
        'notes',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    /**
     * Get all of the purchase order's payments.
     */
    public function payments()
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    /**
     * Accessor for the total amount paid.
     *
     * @return float
     */
    public function getTotalPaidAmountAttribute(): float
    {
        return $this->payments()->sum('amount_paid');
    }

    /**
     * Accessor for the balance due.
     *
     * @return float
     */
    public function getBalanceDueAttribute(): float
    {
        return $this->total_amount - $this->total_paid_amount;
    }
}
