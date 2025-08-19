<?php

namespace Tests\Feature\Api;

use App\Models\Comment;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentControllerTest extends TestCase
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

    public function test_user_can_create_comment(): void
    {
        $response = $this->actingAs($this->user)->postJson("/api/videos/{$this->video->id}/comments", [
            'content' => 'Test comment',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'id',
                'content',
                'user' => ['id', 'name'],
            ]);

        $this->assertDatabaseHas('comments', [
            'content' => 'Test comment',
            'user_id' => $this->user->id,
            'video_id' => $this->video->id,
        ]);
    }

    public function test_user_can_update_own_comment(): void
    {
        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'video_id' => $this->video->id,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/videos/{$this->video->id}/comments/{$comment->id}", [
                'content' => 'Updated comment',
            ]);

        $response->assertOk();
        
        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Updated comment',
        ]);
    }

    public function test_user_cannot_update_others_comment(): void
    {
        $otherUser = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $otherUser->id,
            'video_id' => $this->video->id,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/videos/{$this->video->id}/comments/{$comment->id}", [
                'content' => 'Updated comment',
            ]);

        $response->assertForbidden();
    }

    public function test_user_can_delete_own_comment(): void
    {
        $comment = Comment::factory()->create([
            'user_id' => $this->user->id,
            'video_id' => $this->video->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/videos/{$this->video->id}/comments/{$comment->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_user_cannot_delete_others_comment(): void
    {
        $otherUser = User::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $otherUser->id,
            'video_id' => $this->video->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/videos/{$this->video->id}/comments/{$comment->id}");

        $response->assertForbidden();
        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }
}
