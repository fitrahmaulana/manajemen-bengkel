<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'customer_id' => \App\Models\Customer::factory(),
            'vehicle_id' => \App\Models\Vehicle::factory(),
            'invoice_number' => 'INV-'.$this->faker->unique()->numerify('##########'),
            'invoice_date' => $this->faker->date(),
            'due_date' => $this->faker->dateTimeBetween('+1 day', '+1 month')->format('Y-m-d'),
            'status' => $this->faker->randomElement(['unpaid', 'paid', 'partially_paid', 'overdue']),
            'subtotal' => $this->faker->randomFloat(2, 100, 1000),
            'discount_type' => 'fixed',
            'discount_value' => 0,
            'total_amount' => function (array $attributes) {
                return $attributes['subtotal']; // Simple default, actual calculation is more complex
            },
            'terms' => $this->faker->sentence(),
        ];
    }
}
