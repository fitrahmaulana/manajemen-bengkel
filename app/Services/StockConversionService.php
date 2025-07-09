<?php

namespace App\Services;

use App\Models\Item;
use App\Models\ItemStockConversion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class StockConversionService
{
    /**
     * Perform a stock conversion between two items.
     *
     * @param int $fromItemId
     * @param int $toItemId
     * @param float $fromQuantity - Ubah ke float untuk mendukung desimal
     * @param float $toQuantity - Ubah ke float untuk mendukung desimal
     * @param string|null $notes
     * @param int|null $userId
     * @return ItemStockConversion
     * @throws Exception
     */
    public function convertStock(
        int $fromItemId,
        int $toItemId,
        float $fromQuantity, // Ubah dari int ke float
        float $toQuantity,   // Ubah dari int ke float
        ?string $notes = null,
        ?int $userId = null
    ): ItemStockConversion {
        if ($fromItemId === $toItemId) {
            throw new Exception('Tidak bisa mengkonversi item ke dirinya sendiri.');
        }

        if ($fromQuantity <= 0 || $toQuantity <= 0) {
            throw new Exception('Kuantitas konversi harus lebih besar dari nol.');
        }

        return DB::transaction(function () use ($fromItemId, $toItemId, $fromQuantity, $toQuantity, $notes, $userId) {
            // Lock items for update
            $fromItem = Item::lockForUpdate()->findOrFail($fromItemId);
            $toItem = Item::lockForUpdate()->findOrFail($toItemId);

            // Check if source item has enough stock
            if ($fromItem->stock < $fromQuantity) {
                throw new Exception("Stok item '{$fromItem->display_name}' tidak mencukupi untuk konversi. Stok saat ini: {$fromItem->stock}.");
            }

            // Decrease stock from source item
            $fromItem->stock -= $fromQuantity;
            $fromItem->save();

            // Increase stock for destination item
            $toItem->stock += $toQuantity;
            $toItem->save();

            // Record the conversion
            $conversion = ItemStockConversion::create([
                'from_item_id' => $fromItem->id,
                'to_item_id' => $toItem->id,
                'from_quantity' => $fromQuantity,
                'to_quantity' => $toQuantity,
                'user_id' => $userId ?? Auth::id(),
                'conversion_date' => now(),
                'notes' => $notes,
            ]);

            return $conversion;
        });
    }
}
