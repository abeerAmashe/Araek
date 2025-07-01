<?php

namespace Database\Factories;

use App\Models\FabricType;
use Illuminate\Database\Eloquent\Factories\Factory;

class FabricTypeFactory extends Factory
{
    protected $model = FabricType::class;

    public function definition()
    {
        return [
            'name' => $this->faker->randomElement([
                'Cotton',
                'Silk',
                'Linen',
                'Velvet',
                'Wool',
                'Polyester',
                'Denim',
                'Chiffon',
            ]),
        ];
    }
}