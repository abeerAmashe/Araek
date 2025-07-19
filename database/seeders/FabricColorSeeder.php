<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FabricColor;

class FabricColorSeeder extends Seeder
{
    public function run()
    {
        FabricColor::factory()->count(15)->create();
    }
}