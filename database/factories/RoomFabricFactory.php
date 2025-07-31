<?php

namespace Database\Factories;

use App\Models\RoomFabric;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFabricFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'room_id' => \App\Models\Room::inRandomOrder()->first()->id, 
            'fabric_id' => \App\Models\Fabric::inRandomOrder()->first()->id, 
        ];
    }
}