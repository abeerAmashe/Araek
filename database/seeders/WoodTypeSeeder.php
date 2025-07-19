<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WoodType;

class WoodTypeSeeder extends Seeder
{
    public function run()
    {
        WoodType::factory()->count(8)->create();
    }
}