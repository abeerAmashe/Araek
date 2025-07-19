<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Room;
use App\Models\Category;
use App\Models\WoodType;
use App\Models\WoodColor;
use App\Models\FabricType;
use App\Models\FabricColor;

class RoomFactory extends Factory
{
    protected $model = Room::class;

    public function definition(): array
    {
        $images = [
            'download.jpeg',
            'OIP.jpeg',
            'OIP1.jpeg',
            'OIP2.jpeg',
            'OIP3.jpeg',
            'OIP4.jpeg',
            'th.jpeg',
        ];

        return [
            'name'            => $this->faker->word,
            'category_id'     => Category::inRandomOrder()->first()?->id ?? Category::factory(),
            'description'     => $this->faker->paragraph,
            'image_url'       => 'images/' . $this->faker->randomElement($images),
            'count_reserved'  => $this->faker->numberBetween(0, 10),
            'time'            => $this->faker->randomFloat(1, 1, 10),
            'price'           => $this->faker->randomFloat(2, 100, 1000),
            'count'           => $this->faker->numberBetween(1, 50),

            'wood_type_id'    => WoodType::inRandomOrder()->first()?->id ?? WoodType::factory(),
            'wood_color_id'   => WoodColor::inRandomOrder()->first()?->id ?? WoodColor::factory(),
            'fabric_type_id'  => FabricType::inRandomOrder()->first()?->id ?? FabricType::factory(),
            'fabric_color_id' => FabricColor::inRandomOrder()->first()?->id ?? FabricColor::factory(),
        ];
    }
}