<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\PurchaseOrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @mixin IdeHelperPurchaseOrder
 */
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
        'payment_status',
        'discount_value',
        'discount_type',
        'notes',
    ];

    protected $casts = [
        'status' => PurchaseOrderStatus::class,
        'payment_status' => PaymentStatus::class,
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
     */
    public function getTotalPaidAmountAttribute(): float
    {
        return $this->payments()->sum('amount_paid');
    }

    /**
     * Accessor for the balance due.
     */
    public function getBalanceDueAttribute(): float
    {
        return $this->total_amount - $this->total_paid_amount;
    }

    public function updatePaymentStatus(): void
    {
        $totalPaid = $this->total_paid_amount;

        if ($totalPaid <= 0) {
            $this->payment_status = PaymentStatus::UNPAID;
        } elseif ($totalPaid < $this->total_amount) {
            $this->payment_status = PaymentStatus::PARTIAL;
        } else {
            $this->payment_status = PaymentStatus::PAID;
        }

        $this->save();
    }

    /**
     * Accessor for the overpayment amount.
     */
    public function getOverpaymentAttribute(): float
    {
        return max(0, $this->total_paid_amount - $this->total_amount);
    }
}
