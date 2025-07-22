<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->company.' '.$this->faker->sentence(3), // e.g., "Stark Industries Nano Gauntlet"
            'brand' => $this->faker->company,
            'description' => $this->faker->realText(),
            'type_item_id' => null, // Or \App\Models\TypeItem::factory() if you have it and it's required
            'has_variants' => $this->faker->boolean(80), // 80% chance of having variants
        ];
    }
}
