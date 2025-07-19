<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WoodColor;

class WoodColorSeeder extends Seeder
{
    public function run()
    {
        WoodColor::factory()->count(15)->create();
    }
}