<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RoomDetail;

class RoomDetailSeeder extends Seeder
{
    public function run()
    {
        RoomDetail::factory()->count(10)->create();
    }
}