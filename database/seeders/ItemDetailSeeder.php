<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ItemDetail;

class ItemDetailSeeder extends Seeder
{
    public function run()
    {
        ItemDetail::factory()->count(30)->create();
    }
}