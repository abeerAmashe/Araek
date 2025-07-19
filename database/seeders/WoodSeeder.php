<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Wood;

class WoodSeeder extends Seeder
{
    public function run()
    {
        Wood::factory()->count(20)->create();
    }
}