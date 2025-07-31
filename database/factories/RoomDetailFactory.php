<?php

namespace Database\Factories;

use App\Models\RoomDetail;
use App\Models\Room;
use App\Models\Wood;
use App\Models\Fabric;
use Illuminate\Database\Eloquent\Factories\Factory;

class RoomDetailFactory extends Factory
{
    protected $model = RoomDetail::class;

    public function definition()
    {
        return [
            'room_id' => Room::factory(),      
            'wood_id' => Wood::factory(),      
            'fabric_id' => Fabric::factory(),  
        ];
    }
}