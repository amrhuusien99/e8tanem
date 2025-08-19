<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_can_list_active_videos(): void
    {
        Video::factory()->count(3)->create([
            'is_active' => true,
            'user_id' => $this->user->id,
        ]);

        Video::factory()->count(2)->create([
            'is_active' => false,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/videos');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_can_view_active_video(): void
    {
        $video = Video::factory()->create([
            'is_active' => true,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/videos/{$video->id}");

        $response->assertOk()
            ->assertJson([
                'id' => $video->id,
                'title' => $video->title,
            ]);
    }

    public function test_cannot_view_inactive_video(): void
    {
        $video = Video::factory()->create([
            'is_active' => false,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/videos/{$video->id}");

        $response->assertNotFound();
    }

    public function test_viewing_video_increments_view_count(): void
    {
        $video = Video::factory()->create([
            'is_active' => true,
            'views_count' => 0,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->getJson("/api/videos/{$video->id}");
        $response->assertOk();

        $this->assertEquals(1, $video->fresh()->views_count);
    }
}
