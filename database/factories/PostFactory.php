<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostFactory extends Factory
{
    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'created_by' => User::factory(),
            'status' => 'draft',
            'title' => fake()->sentence(),
            'base_text' => fake()->paragraph(),
            'base_media' => [],
            'target_url' => fake()->url(),
            'utm_template' => null,
        ];
    }
}
