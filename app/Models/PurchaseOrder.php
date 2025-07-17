<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'order_date',
        'total_price',
        'status',
        'discount',
        'discount_type',
        'notes',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function items()
    {
        return $this->belongsToMany(Item::class, 'purchase_order_items')
            ->withPivot('quantity', 'price')
            ->withTimestamps();
    }
}
