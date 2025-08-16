<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\GallaryManager;
use App\Models\SubManager;
use Illuminate\Database\Eloquent\Factories\Factory;

class BranchFactory extends Factory
{
    protected $model = Branch::class;

    public function definition(): array
    {
        return [
            'sub_manager_id' => SubManager::factory(),
            'address' => $this->faker->address,
            'latitude' => $this->faker->latitude(24, 32),
            'longitude' => $this->faker->longitude(34, 40),
        ];
    }
}