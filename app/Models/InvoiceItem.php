<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class InvoiceItem extends Pivot
{
    protected $table = 'invoice_item';

    public function getQuantityAttribute($value)
    {
        return $value + 0;
    }
}
