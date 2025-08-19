<?php

namespace Tests\Feature\Api;

use App\Models\Like;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LikeControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Video $video;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create();
        $this->video = Video::factory()->create(['is_active' => true]);
    }

    public function test_user_can_like_video(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/videos/{$this->video->id}/toggle-like");

        $response->assertOk()
            ->assertJson(['liked' => true]);

        $this->assertDatabaseHas('likes', [
            'user_id' => $this->user->id,
            'video_id' => $this->video->id,
        ]);
    }

    public function test_user_can_unlike_video(): void
    {
        Like::factory()->create([
            'user_id' => $this->user->id,
            'video_id' => $this->video->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/videos/{$this->video->id}/toggle-like");

        $response->assertOk()
            ->assertJson(['liked' => false]);

        $this->assertDatabaseMissing('likes', [
            'user_id' => $this->user->id,
            'video_id' => $this->video->id,
        ]);
    }

    public function test_user_can_get_like_status(): void
    {
        Like::factory()->create([
            'user_id' => $this->user->id,
            'video_id' => $this->video->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/videos/{$this->video->id}/like-status");

        $response->assertOk()
            ->assertJson(['liked' => true]);
    }

    public function test_unauthenticated_user_cannot_like_video(): void
    {
        $response = $this->postJson("/api/videos/{$this->video->id}/toggle-like");

        $response->assertUnauthorized();
    }
}
