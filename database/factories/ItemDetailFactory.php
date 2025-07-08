<?php

namespace Database\Factories;

use App\Models\ItemDetail;
use App\Models\Item;
use App\Models\Wood;
use App\Models\Fabric;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemDetailFactory extends Factory
{
    protected $model = ItemDetail::class;

    public function definition()
    {
        return [
            'item_id' => Item::factory(),
            'wood_id' => Wood::factory(),
            'fabric_id' => Fabric::factory(),
            'wood_length' => $this->faker->randomFloat(2, 10, 200),
            'wood_width' => $this->faker->randomFloat(2, 10, 200),
            'wood_height' => $this->faker->randomFloat(2, 10, 200),
            'fabric_dimension' => $this->faker->randomElement(['100x100', '200x150', '150x150']),
            'fabric_width'=>$this->faker->randomFloat(2, 10, 200),
            'fabric_length'=>$this->faker->randomFloat(2, 10, 200),
            'wood_area_m2' => $this->faker->numberBetween(1, 10),

        ];
    }
}