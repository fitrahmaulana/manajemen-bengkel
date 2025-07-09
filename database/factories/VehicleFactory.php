<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vehicle>
 */
class VehicleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => \App\Models\Customer::factory(), // Ensure Customer factory exists and is used
            'license_plate' => $this->faker->regexify('[A-Z]{1,2} [0-9]{1,4} [A-Z]{1,3}'),
            'brand' => $this->faker->randomElement(['Toyota', 'Honda', 'Suzuki', 'Mitsubishi', 'Daihatsu']),
            'model' => $this->faker->word(),
            'color' => $this->faker->safeColorName(),
            'year' => $this->faker->numberBetween(2000, now()->year),
        ];
    }
}
