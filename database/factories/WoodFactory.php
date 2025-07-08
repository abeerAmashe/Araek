<?php

namespace Database\Factories;

use App\Models\Wood;
use App\Models\WoodColor;
use App\Models\WoodType;
use Illuminate\Database\Eloquent\Factories\Factory;

class WoodFactory extends Factory
{
    protected $model = Wood::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word(),
            'wood_color_id' => WoodColor::factory(),
            'wood_type_id' => WoodType::factory(),
        ];
    }
}