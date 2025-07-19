<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CartItemReservation;

class CartItemReservationSeeder extends Seeder
{
    public function run()
    {
        CartItemReservation::factory()->count(10)->create();
    }
}