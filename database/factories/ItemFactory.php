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
            'unit' => $this->faker->randomElement(['Pcs', 'Botol', 'Liter', 'Set']),
            'is_convertible' => false,
            'target_child_item_id' => null,
            'conversion_value' => null,
        ];
    }
}
