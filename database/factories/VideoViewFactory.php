<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\Video;
use App\Models\VideoView;
use Illuminate\Database\Eloquent\Factories\Factory;

class VideoViewFactory extends Factory
{
    protected $model = VideoView::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'video_id' => Video::factory(),
        ];
    }
}
