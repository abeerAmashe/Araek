<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\GallaryManager;
use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'gallary_manager_id' => GallaryManager::factory(), // ينشئ مدير معرض تلقائيًا
            'address' => $this->faker->address,
            'latitude' => $this->faker->latitude,
            'longitude' => $this->faker->longitude,
        ];
    }
}