<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Wood;
use App\Models\Fabric;
use App\Models\Type;
use App\Models\Color;
use App\Models\FabricColor;
use App\Models\FabricType;
use App\Models\PlaceCost;
use App\Models\RoomOrder;
use App\Models\WoodColor;
use App\Models\WoodType;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $woods = Wood::factory()->count(10)->create();
        $fabrics = Fabric::factory()->count(10)->create();

        foreach ($woods as $wood) {
            WoodColor::factory()->create([
                'name' => fake()->safeColorName(),
            ]);

            WoodType::factory()->create([
                'name' => fake()->word(),
            ]);
        }

        foreach ($fabrics as $fabric) {
            FabricColor::factory()->create([
                'name' => fake()->safeColorName(),
            ]);

            FabricType::factory()->create([
                'name' => fake()->word(),
            ]);
        }


        $this->call([
        // UserSeeder::class,
        // CustomerSeeder::class,
        // WoodSeeder::class,
        // FabricSeeder::class,
        // CategorySeeder::class,
        // RoomSeeder::class,
        // LikeSeeder::class,
        // RatingSeeder::class,
        // ItemSeeder::class,
        // CartSeeder::class,
        // DiscountSeeder::class,
        // GallaryManagerSeeder::class,
        // CartItemReservationSeeder::class,
        // PlaceCostSeeder::class,
        // BranchSeeder::class,
        // FabricColorSeeder::class,
        // FabricTypeSeeder::class,
        // WoodColorSeeder::class,
        // WoodTypeSeeder::class,
        // RoomSeeder::class,
        // ItemTypeSeeder::class,
        // ItemDetailSeeder::class,
        // PurchaseOrderSeeder::class,
        FavoriteSeeder::class,
        AvailableTimeSeeder::class,
        DeliveryCompanyAvailabilitySeeder::class,
    ]);
    }
}