<?php

namespace Database\Factories;

use App\Models\GallaryManager;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GallaryManagerFactory extends Factory
{
    protected $model = GallaryManager::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(), // ينشئ مستخدم ويربطه بالمدير
        ];
    }
}