<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Item;

class ItemSeeder extends Seeder
{
    public function run()
    {
        // توليد 25 عنصر عشوائي مع علاقات room و item_type
        Item::factory()->count(25)->create();
    }
}