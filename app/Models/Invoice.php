<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory; // Import HasFactory
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Import SoftDeletes

class Invoice extends Model
{
    use HasFactory, SoftDeletes; // Use HasFactory and SoftDeletes trait

    protected $fillable = ['customer_id', 'vehicle_id', 'invoice_number', 'invoice_date', 'due_date', 'status', 'subtotal', 'discount_type', 'discount_value', 'total_amount', 'terms'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function invoiceServices()
    {
        return $this->hasMany(InvoiceService::class);
    }

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /**
     * Get all of the invoice's payments.
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

    /**
     * Accessor for the overpayment amount.
     *
     * @return float
     */
    public function getOverpaymentAttribute(): float
    {
        return max(0, $this->total_paid_amount - $this->total_amount);
    }

    /**
     * Scope a query to only include overdue invoices.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'Overdue');
    }
}
