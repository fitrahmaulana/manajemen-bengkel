<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemStockConversion extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_item_id',
        'to_item_id',
        'from_quantity',
        'to_quantity',
        'user_id',
        'conversion_date',
        'notes',
    ];

    protected $casts = [
        'conversion_date' => 'datetime',
    ];

    public function fromItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'from_item_id');
    }

    public function toItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'to_item_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
