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
            'room_id' => Room::factory(),      // ينشئ Room جديد أو يمكنك وضع ID موجود
            'wood_id' => Wood::factory(),      // ينشئ Wood جديد أو ID موجود
            'fabric_id' => Fabric::factory(),  // ينشئ Fabric جديد أو ID موجود
        ];
    }
}