<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Room;
use Database\Factories\RoomDetailFactory;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        Room::factory()->count(10)->create();
    }
}