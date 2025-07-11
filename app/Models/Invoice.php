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

    public function services()
    {
        return $this->belongsToMany(Service::class, 'invoice_service')->withPivot('price', 'description');
    }

    public function items()
    {
        return $this->belongsToMany(Item::class, 'invoice_item')
            ->withPivot('quantity', 'price', 'description')
            ->withCasts(['quantity' => 'decimal:2']);
    }

    /**
     * Get the payments for the invoice.
     */
    public function payments()
    {
        return $this->hasMany(Payment::class);
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
