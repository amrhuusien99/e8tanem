<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Podcast>
 */
class PodcastFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'audio_url' => fake()->url(),
            'thumbnail_url' => fake()->imageUrl(),
            'category' => fake()->randomElement(['education', 'technology', 'business', 'entertainment', 'news', 'other']),
            'description' => fake()->paragraph(),
            'is_active' => true,
            'plays_count' => fake()->numberBetween(0, 1000),
            'user_id' => User::factory(),
        ];
    }
}