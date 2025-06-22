<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes; // Import SoftDeletes

class Invoice extends Model
{
    use SoftDeletes; // Use SoftDeletes trait

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
        return $this->belongsToMany(Item::class, 'invoice_item')->withPivot('quantity', 'price', 'description');
    }
}
