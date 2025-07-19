<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ItemDetail;

class ItemDetailSeeder extends Seeder
{
    public function run()
    {
        // توليد 30 سجل عشوائي في جدول item_details
        ItemDetail::factory()->count(30)->create();
    }
}