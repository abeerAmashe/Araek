<?php

namespace Database\Factories;

use App\Models\WoodColor;
use Illuminate\Database\Eloquent\Factories\Factory;

class WoodColorFactory extends Factory
{
    protected $model = WoodColor::class;

    public function definition()
    {
        return [
            'name' => $this->faker->safeColorName(),
        ];
    }
}