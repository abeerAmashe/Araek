<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\PurchaseOrder;
use App\Models\Customer;
use App\Models\Branch;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition()
    {
        $statusOptions = ['pending', 'in_progress', 'complete', 'cancelled'];
        $deliveryStatusOptions = ['pending', 'negotiation', 'confirmed'];
        $wantDeliveryOptions = ['yes', 'no'];
        $isPaidOptions = ['pending', 'partial', 'paid'];
        $isRecivedOptions = ['pending', 'done'];

        return [
            'customer_id' => Customer::inRandomOrder()->first()?->id ?? Customer::factory(),
            'status' => $this->faker->randomElement($statusOptions),
            'delivery_status' => $this->faker->randomElement($deliveryStatusOptions),
            'want_delivery' => $this->faker->randomElement($wantDeliveryOptions),
            'is_paid' => $this->faker->randomElement($isPaidOptions),
            'is_recived' => $this->faker->randomElement($isRecivedOptions),
            'total_price' => $this->faker->randomFloat(2, 50, 1000),
            'recive_date' => $this->faker->date(),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'delivery_time' => $this->faker->dateTimeBetween('now', '+7 days'),
            'address' => $this->faker->address(),
            'delivery_price' => $this->faker->randomFloat(2, 0, 50),
            'rabbon' => $this->faker->randomFloat(2, 0, 100),
            'price_after_rabbon' => function (array $attributes) {
                return $attributes['total_price'] - $attributes['rabbon'];
            },
            'price_after_rabbon_with_delivery' => function (array $attributes) {
                return ($attributes['total_price'] - $attributes['rabbon']) + $attributes['delivery_price'];
            },
            'branch_id' => Branch::inRandomOrder()->first()?->id ?? Branch::factory(),
        ];
    }
}
