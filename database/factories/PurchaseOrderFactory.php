<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition()
    {
        $wantDelivery = $this->faker->randomElement(['yes', 'no']);

        return [
            'customer_id' => Customer::factory(),
            'status' => $this->faker->randomElement(['pending', 'in_progress', 'complete', 'cancelled']),
            'delivery_status' => $this->faker->randomElement(['pending', 'negotiation', 'confirmed']),
            'want_delivery' => $wantDelivery,
            'is_paid' => $this->faker->randomElement(['pending', 'partial', 'paid']),
            'is_recived' => $this->faker->randomElement(['pending', 'done']),
            'total_price' => $this->faker->randomFloat(2, 100, 10000),
            'recive_date' => $this->faker->date(),
            'latitude' => $this->faker->optional()->latitude(),
            'longitude' => $this->faker->optional()->longitude(),
            'delivery_time' => $wantDelivery === 'yes' ? $this->faker->dateTimeBetween('+1 day', '+7 days') : null,
            'address' => $this->faker->address(),
            'delivery_price' => $wantDelivery === 'yes' ? $this->faker->randomFloat(2, 10, 100) : 0,
            'rabbon' => $this->faker->optional()->randomFloat(2, 5, 500),
            'price_after_rabbon' => null,
            'price_after_rabbon_with_delivery' => null, 
            'branch_id' => Branch::factory(),
        ];
    }
}