<?php

namespace Database\Factories;

use App\Models\WoodType;
use Illuminate\Database\Eloquent\Factories\Factory;

class WoodTypeFactory extends Factory
{
    protected $model = WoodType::class;

    public function definition()           

    {
        return [
            'name' => $this->faker->randomElement([
                'Oak',
                'Pine',
                'Mahogany',
                'Walnut',
                'Teak',
                'Cherry',
                'Maple',
                'Birch',
            ]),
             'price_per_meter' => $this->faker->randomFloat(2, 30, 300),
        ];
    }
}