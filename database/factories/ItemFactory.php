<?php

namespace Database\Factories;

use App\Models\Item;
use App\Models\Room;
use App\Models\ItemType;
use Illuminate\Database\Eloquent\Factories\Factory;

class ItemFactory extends Factory
{
    protected $model = Item::class;

    public function definition()
    {
        return [
            'room_id' => Room::factory(),
            'item_type_id' => ItemType::factory(),
            'name' => $this->faker->words(2, true),
            'price' => $this->faker->randomFloat(2, 50, 5000),
            'image_url' => $this->faker->imageUrl(640, 480, 'furniture', true),
            'description' => $this->faker->paragraph(),
            'count' => $this->faker->numberBetween(0, 20),
            'count_reserved' => $this->faker->numberBetween(0, 20),
            'time' => $this->faker->numberBetween(0, 20),
        ];
    }
}
