<?php

namespace Database\Factories;

use App\Models\ConnectedSocialAccount;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

class PostVariantFactory extends Factory
{
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'platform' => fake()->randomElement(['facebook', 'linkedin']),
            'connected_social_account_id' => ConnectedSocialAccount::factory(),
            'text_override' => null,
            'media_override' => null,
            'scheduled_at' => fake()->dateTimeBetween('now', '+1 week'),
            'status' => 'scheduled',
        ];
    }
}
