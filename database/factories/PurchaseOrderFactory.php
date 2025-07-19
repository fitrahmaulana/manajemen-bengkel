<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = PurchaseOrder::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'supplier_id' => Supplier::factory(),
            'po_number' => $this->faker->unique()->numerify('PO-########'),
            'order_date' => $this->faker->date(),
            'status' => 'draft',
            'payment_status' => 'unpaid',
            'total_amount' => $this->faker->numberBetween(100, 10000),
        ];
    }
}
