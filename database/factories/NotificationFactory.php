<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'title' => $this->faker->sentence(),
            'message' => $this->faker->paragraph(),
            'type' => $this->faker->randomElement(['general', 'video', 'update']),
            'is_read' => false,
            'expires_at' => null,
        ];
    }
    
    public function global(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'user_id' => null,
            ];
        });
    }
    
    public function expired(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'expires_at' => now()->subDay(),
            ];
        });
    }
    
    public function expiresInFuture(): self
    {
        return $this->state(function (array $attributes) {
            return [
                'expires_at' => now()->addDays(7),
            ];
        });
    }
}
