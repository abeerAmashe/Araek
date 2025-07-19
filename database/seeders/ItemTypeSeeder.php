<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ItemType;

class ItemTypeSeeder extends Seeder
{
    public function run()
    {
        // توليد 15 سجل عشوائي في جدول item_types
        ItemType::factory()->count(15)->create();
    }
}