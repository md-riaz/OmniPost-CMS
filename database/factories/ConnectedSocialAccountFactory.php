<?php

namespace Database\Factories;

use App\Models\Brand;
use App\Models\OAuthToken;
use Illuminate\Database\Eloquent\Factories\Factory;

class ConnectedSocialAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'platform' => fake()->randomElement(['facebook', 'linkedin']),
            'external_account_id' => fake()->uuid(),
            'display_name' => fake()->company(),
            'token_id' => OAuthToken::factory(),
            'status' => 'connected',
        ];
    }
}
