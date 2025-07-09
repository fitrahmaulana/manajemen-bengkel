<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'product_id' => \App\Models\Product::factory(),
            'name' => $this->faker->words(2, true), // e.g., "Red Widget"
            'sku' => $this->faker->unique()->ean13,
            'purchase_price' => $this->faker->numberBetween(10000, 50000),
            'selling_price' => $this->faker->numberBetween(50000, 100000),
            'stock' => $this->faker->numberBetween(0, 100),
            'unit' => $this->faker->randomElement(['Pcs', 'Botol', 'Liter', 'Set', 'ml', 'Dus', 'Galon']),
            'volume_value' => function (array $attributes) {
                // Simple logic for factory, can be more sophisticated
                switch (strtolower($attributes['unit'])) {
                    case 'liter':
                        return 1000;
                    case 'botol': // assume 1 botol = 1000ml or 1 pcs for non-liquids
                        return $this->faker->randomElement([1000, 1]);
                    case 'ml':
                        return $this->faker->randomElement([100, 250, 500, 750]);
                    case 'dus': // assume 1 dus = 12 pcs/botol
                        return 12;
                    case 'galon': // assume 1 galon = 4000ml
                        return 4000;
                    case 'pcs':
                    case 'set':
                        return 1;
                    default:
                        return $this->faker->numberBetween(1, 100);
                }
            },
            'base_volume_unit' => function (array $attributes) {
                switch (strtolower($attributes['unit'])) {
                    case 'liter':
                    case 'botol': // Assuming botol is often liquid
                    case 'ml':
                    case 'galon':
                        return 'ml';
                    case 'dus': // Could represent pcs or a liquid unit if standardized
                    case 'pcs':
                    case 'set':
                        return 'pcs';
                    default:
                        return $this->faker->randomElement(['ml', 'gr', 'pcs']);
                }
            },
            'is_convertible' => false,
            'target_child_item_id' => null,
            'conversion_value' => null,
        ];
    }
}
