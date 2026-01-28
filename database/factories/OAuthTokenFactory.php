<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class OAuthTokenFactory extends Factory
{
    public function definition(): array
    {
        return [
            'platform' => fake()->randomElement(['facebook', 'linkedin']),
            'access_token' => fake()->uuid(),
            'refresh_token' => fake()->uuid(),
            'expires_at' => now()->addDays(60),
            'scopes' => ['read', 'write'],
            'meta' => [],
        ];
    }
}
