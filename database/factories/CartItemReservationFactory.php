<?php

namespace Database\Factories;

use App\Models\CartItemReservation;
use App\Models\Cart;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

class CartItemReservationFactory extends Factory
{
    protected $model = CartItemReservation::class;

    public function definition()
    {
        return [
            'cart_id' => Cart::factory(),
            'item_id' => Item::factory(),
            'count_reserved' => $this->faker->numberBetween(1, 5),
        ];
    }
}