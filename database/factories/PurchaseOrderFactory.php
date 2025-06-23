<?php

namespace Database\Factories;

use App\Models\PurchaseOrder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        $wantDelivery = $this->faker->randomElement(['yes', 'no']);
        $deliveryPrice = $wantDelivery === 'yes' ? $this->faker->randomFloat(2, 5, 20) : 0;

        $totalPrice = $this->faker->randomFloat(2, 100, 1000);
        $rabbon = $totalPrice * 0.5;
        $priceAfterRabbon = $totalPrice - $rabbon;
        $priceAfterRabbonWithDelivery = $wantDelivery === 'yes' ? $priceAfterRabbon + $deliveryPrice : null;
        $remainingWithDelivery = $priceAfterRabbonWithDelivery;

        return [
            'customer_id'                         => 1,
            'status'                              => $this->faker->randomElement(['pending', 'in_progress', 'complete', 'cancelled']),
            'delivery_status'                     => $this->faker->randomElement(['pending', 'negotiation', 'confirmed']),
            'want_delivery'                       => $wantDelivery,
            'is_paid'                             => $this->faker->randomElement(['pending', 'partial', 'paid']),
            'is_recived'                          => $this->faker->randomElement(['pending', 'done']),
            'total_price'                         => $totalPrice,
            'recive_date'                         => Carbon::now()->addDays(3)->toDateString(),
            'latitude'                            => $wantDelivery === 'yes' ? $this->faker->latitude : null,
            'longitude'                           => $wantDelivery === 'yes' ? $this->faker->longitude : null,
            'delivery_time'                       => $wantDelivery === 'yes' ? Carbon::now()->addDays(5) : null,
            'address'                             => $this->faker->address,
            'delivery_price'                      => $deliveryPrice,
            'rabbon'                              => $rabbon,
            'price_after_rabbon'                  => $priceAfterRabbon,
            'price_after_rabbon_with_delivery'    => $priceAfterRabbonWithDelivery,
            'remaining_amount_with_delivery'      => $remainingWithDelivery,
            'branch_id'                           => $wantDelivery === 'no' ? $this->faker->numberBetween(1, 5) : null,
        ];
    }
}