<?php

namespace Database\Factories;

use App\Models\FabricColor;
use Illuminate\Database\Eloquent\Factories\Factory;

class FabricColorFactory extends Factory
{
    protected $model = FabricColor::class;

    public function definition()
    {
        return [
            'name' => $this->faker->colorName(), // يعطي أسماء ألوان مثل: "Red", "Blue", "Beige"
        ];
    }
}