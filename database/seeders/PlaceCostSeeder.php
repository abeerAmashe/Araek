<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PlaceCost;

class PlaceCostSeeder extends Seeder
{
    public function run(): void
    {
        PlaceCost::factory()->count(10)->create();
    }
}