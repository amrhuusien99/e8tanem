<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\User;
use App\Models\Video;
use Illuminate\Database\Seeder;

class DevSampleSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::where('email', 'test@example.com')->first()
            ?? User::first();

        if (! $user) {
            return;
        }

        $video = Video::factory()->create([
            'user_id' => $user->id,
            'comments_count' => 0,
        ]);

        Comment::factory()->count(3)->create([
            'video_id' => $video->id,
            'user_id' => $user->id,
        ]);

        $video->update(['comments_count' => 3]);

        $this->command?->info("Sample video id: {$video->id}");
    }
}
