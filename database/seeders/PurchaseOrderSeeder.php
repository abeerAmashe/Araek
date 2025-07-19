<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PurchaseOrder;
use Illuminate\Support\Carbon;

class PurchaseOrderSeeder extends Seeder
{
    public function run(): void
    {
        PurchaseOrder::factory()->count(10)->create();
    }
}
