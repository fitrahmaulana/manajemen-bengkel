<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperService
 */
class Service extends Model
{
    protected $fillable = ['name', 'description', 'price'];

    public function invoices()
    {
        return $this->belongsToMany(Invoice::class, 'invoice_service');
    }
}
