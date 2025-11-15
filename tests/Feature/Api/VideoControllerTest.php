<?php

namespace Tests\Feature\Api;

use App\Models\Comment;
use App\Models\Like;
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

    public function test_feed_ranking_prioritizes_engagement_but_respects_chronology_mode(): void
    {
        $highEngagement = Video::factory()->create([
            'is_active' => true,
            'views_count' => 400,
            'user_id' => $this->user->id,
            'created_at' => now()->subHours(8),
        ]);

        $freshVideo = Video::factory()->create([
            'is_active' => true,
            'views_count' => 10,
            'user_id' => $this->user->id,
            'created_at' => now()->subMinutes(10),
        ]);

        Like::factory()->count(60)->create(['video_id' => $highEngagement->id]);
        Comment::factory()->count(25)->create(['video_id' => $highEngagement->id]);

        $feedResponse = $this->actingAs($this->user)->getJson('/api/videos?seed=test-seed');
        $feedResponse->assertOk()
            ->assertJsonPath('data.0.id', $highEngagement->id);

        $chronologicalResponse = $this->actingAs($this->user)
            ->getJson('/api/videos?mode=chronological');
        $chronologicalResponse->assertOk()
            ->assertJsonPath('data.0.id', $freshVideo->id);
    }

    public function test_feed_response_includes_last_comment_and_viewer_flags(): void
    {
        $video = Video::factory()->create([
            'is_active' => true,
            'views_count' => 25,
            'user_id' => $this->user->id,
        ]);

        Comment::factory()->create([
            'video_id' => $video->id,
            'created_at' => now()->subDay(),
        ]);

        $latestComment = Comment::factory()->create([
            'video_id' => $video->id,
            'content' => 'This is the freshest remark',
            'user_id' => User::factory()->create()->id,
            'created_at' => now(),
        ]);

        Like::factory()->create([
            'video_id' => $video->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/api/videos?mode=chronological');

        $response->assertOk()
            ->assertJsonPath('data.0.last_comment.content', $latestComment->content)
            ->assertJsonPath('data.0.last_comment.user.id', $latestComment->user_id)
            ->assertJsonPath('data.0.is_liked_by_viewer', true)
            ->assertJsonPath('data.0.engagement_overview.likes', 1)
            ->assertJsonPath('data.0.engagement_overview.comments', 2)
            ->assertJsonPath('data.0.engagement_overview.views', 25);
    }
}
