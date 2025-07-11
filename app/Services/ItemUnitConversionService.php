<?php

namespace App\Services;

use App\Models\Item;
use Exception;

class ItemUnitConversionService
{
    /**
     * Calculates the target quantity when converting stock from a source item to a target item.
     *
     * This method assumes that both items belong to the same product and have compatible
     * volume units for conversion.
     *
     * @param Item $sourceItem The item from which stock is being converted.
     * @param Item $targetItem The item to which stock is being converted.
     * @param float $sourceQuantity The quantity of the source item being converted.
     * @return float|null The calculated quantity of the target item, or null if conversion is not possible or results in zero/negative.
     */
    public static function calculateTargetQuantity(Item $sourceItem, Item $targetItem, float $sourceQuantity): ?float
    {
        if ($sourceQuantity <= 0) {
            return null; // Cannot convert zero or negative quantity
        }

        // Ensure items have necessary volume information for conversion
        if (
            !$sourceItem->volume_value || !$sourceItem->base_volume_unit ||
            !$targetItem->volume_value || !$targetItem->base_volume_unit
        ) {
            // Missing volume information for one or both items
            return null;
        }

        // Ensure base volume units are the same for a meaningful conversion
        if ($sourceItem->base_volume_unit !== $targetItem->base_volume_unit) {
            // Different base units, direct conversion not supported by this logic
            // Consider logging this case if it's unexpected
            return null;
        }

        // Avoid division by zero if target item's volume value is zero
        if ($targetItem->volume_value == 0) {
            return null;
        }

        // Calculate the total base volume of the source quantity
        $totalSourceBaseVolume = $sourceItem->volume_value * $sourceQuantity;

        // Calculate how many target items can be made from the total base volume
        $calculatedQuantity = $totalSourceBaseVolume / $targetItem->volume_value;

        // Determine precision: if the result is a whole number, use 0 decimal places, otherwise use 2.
        // This matches the rounding logic observed in the original methods.
        $precision = (fmod($calculatedQuantity, 1) != 0) ? 2 : 0;
        $finalQuantity = round($calculatedQuantity, $precision);

        // Ensure the final quantity is positive
        if ($finalQuantity <= 0) {
            return null;
        }

        return $finalQuantity;
    }
}
