<?php

namespace Database\Factories;

use App\Models\Subject;
use Illuminate\Database\Eloquent\Factories\Factory;

class LessonFactory extends Factory
{
    public function definition(): array
    {
        return [
            'subject_id' => Subject::factory(),
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'video_path' => 'lessons/videos/sample.mp4',
            'thumbnail' => null,
            'duration' => $this->faker->numberBetween(300, 3600), // 5 minutes to 1 hour
            'order' => $this->faker->numberBetween(1, 100),
            'is_active' => true,
            'views_count' => $this->faker->numberBetween(0, 1000),
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
