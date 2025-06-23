<?php

namespace Database\Factories;

use App\Models\PlaceCost;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlaceCostFactory extends Factory
{
    protected $model = PlaceCost::class;

    public function definition(): array
    {
        return [
            'place' => $this->faker->city, 
            'price' => $this->faker->randomFloat(2, 5, 50), 
        ];
    }
}