<?php

namespace Database\Factories;

use App\Models\Fabric;
use App\Models\FabricColor;
use App\Models\FabricType;
use Illuminate\Database\Eloquent\Factories\Factory;

class FabricFactory extends Factory
{
    protected $model = Fabric::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word(),
            'price_per_meter' => $this->faker->randomFloat(2, 5, 100),
            'fabric_color_id' => FabricColor::factory(),
            'fabric_type_id' => FabricType::factory(),
        ];
    }
}