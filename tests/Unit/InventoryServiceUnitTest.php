<?php

namespace Tests\Unit;

use App\Models\Item;
use App\Services\InventoryService;
use PHPUnit\Framework\TestCase;

class InventoryServiceUnitTest extends TestCase
{
    // Made it an instance method, can now use $this->createMock
    private function createItemMock(
        ?float $volumeValue,
        ?string $baseVolumeUnit,
        string $unitName = 'pcs'
    ): Item {
        $item = $this->createMock(Item::class);
        // Configure the mock to return values for properties accessed by the service
        $item->method('__get')->willReturnMap([
            ['volume_value', $volumeValue],
            ['base_volume_unit', $baseVolumeUnit],
            ['unit', $unitName], // Though 'unit' isn't used by service, good for consistency
        ]);

        return $item;
    }

    /**
     * @dataProvider conversionScenariosProvider
     */
    public function test_calculate_target_quantity(
        array $sourceItemParams, // Now accepts array of params
        array $targetItemParams, // Now accepts array of params
        float $sourceQuantity,
        ?float $expectedQuantity
    ): void {
        $sourceItem = $this->createItemMock(
            $sourceItemParams[0], // volumeValue
            $sourceItemParams[1], // baseVolumeUnit
            $sourceItemParams[2]  // unitName
        );
        $targetItem = $this->createItemMock(
            $targetItemParams[0], // volumeValue
            $targetItemParams[1], // baseVolumeUnit
            $targetItemParams[2]  // unitName
        );

        $calculated = InventoryService::calculateTargetQuantity(
            $sourceItem,
            $targetItem,
            $sourceQuantity
        );
        $this->assertEquals($expectedQuantity, $calculated);
    }

    public static function conversionScenariosProvider(): array
    {
        // Now returns arrays of parameters for mock creation within the test method
        return [
            'valid conversion: drum to bottle (200L drum, 1L bottle)' => [
                // sourceItem params: volumeValue, baseVolumeUnit, unitName
                [200, 'liter', 'Drum'],
                // targetItem params
                [1, 'liter', 'Bottle'],
                // sourceQuantity
                1.0,
                // expectedQuantity
                200.0,
            ],
            'valid conversion: box of 12 to pcs (12pcs box, 1pcs item)' => [
                [12, 'pcs', 'Box'],
                [1, 'pcs', 'Pcs'],
                2.0,
                24.0,
            ],
            'valid conversion: with decimal result (e.g., 10L source, 4L target -> 2.5)' => [
                [10, 'liter', 'Source'],
                [4, 'liter', 'Target'],
                1.0,
                2.5,
            ],
            'valid conversion: small to large (e.g., 1L source, 5L target -> 0.2)' => [
                [1, 'liter', 'Source'],
                [5, 'liter', 'Target'],
                1.0,
                0.2,
            ],
            'valid conversion: multiple source quantity (2 drums of 200L, 1L bottle -> 400 bottles)' => [
                [200, 'liter', 'Drum'],
                [1, 'liter', 'Bottle'],
                2.0,
                400.0,
            ],
            'valid conversion: result requires rounding (10 / 3 = 3.33)' => [
                [10, 'kg', 'Bag A'],
                [3, 'kg', 'Bag B'],
                1.0,
                3.33, // Rounded to 2 decimal places
            ],
            'valid conversion: result is whole number, no rounding needed (10 / 2 = 5)' => [
                [10, 'kg', 'Sack'],
                [2, 'kg', 'Pack'],
                1.0,
                5.0,
            ],

            // Edge cases and invalid scenarios
            'invalid: source quantity zero' => [
                [200, 'liter', 'Drum'],
                [1, 'liter', 'Bottle'],
                0.0,
                null,
            ],
            'invalid: source quantity negative' => [
                [200, 'liter', 'Drum'],
                [1, 'liter', 'Bottle'],
                -1.0,
                null,
            ],
            'invalid: source item missing volume_value' => [
                [null, 'liter', 'Drum'],
                [1, 'liter', 'Bottle'],
                1.0,
                null,
            ],
            'invalid: source item missing base_volume_unit' => [
                [200, null, 'Drum'],
                [1, 'liter', 'Bottle'],
                1.0,
                null,
            ],
            'invalid: target item missing volume_value' => [
                [200, 'liter', 'Drum'],
                [null, 'liter', 'Bottle'],
                1.0,
                null,
            ],
            'invalid: target item missing base_volume_unit' => [
                [200, 'liter', 'Drum'],
                [1, null, 'Bottle'],
                1.0,
                null,
            ],
            'invalid: different base_volume_units (liter vs kg)' => [
                [200, 'liter', 'Drum'],
                [1, 'kg', 'Sack'],
                1.0,
                null,
            ],
            'invalid: target item volume_value is zero (division by zero)' => [
                [200, 'liter', 'Drum'],
                [0, 'liter', 'Bottle'],
                1.0,
                null,
            ],
            'invalid: source item volume_value is zero (results in zero output)' => [
                [0, 'liter', 'Drum'],
                [1, 'liter', 'Bottle'],
                'sourceQuantity' => 1.0,
                'expectedQuantity' => null, // because 0 * 1 / 1 = 0, which service treats as invalid
            ],
        ];
    }
}
