<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FabricType;

class FabricTypeSeeder extends Seeder
{
    public function run()
    {
        FabricType::factory()->count(20)->create();
    }
}