<?php

namespace Tests\Feature\Api;

use App\Models\Lesson;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LessonControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_view_active_lesson(): void
    {
        $subject = Subject::factory()->create();
        $lesson = Lesson::factory()->create([
            'subject_id' => $subject->id,
        ]);

        $initialViews = $lesson->views_count;

        $response = $this->actingAs($this->user)
            ->getJson("/api/lessons/{$lesson->id}");

        $response->assertOk()
            ->assertJsonFragment([
                'title' => $lesson->title,
                'video_url' => $lesson->video_url,
            ]);

        $this->assertEquals($initialViews + 1, $lesson->fresh()->views_count);
    }

    public function test_user_cannot_view_inactive_lesson(): void
    {
        $lesson = Lesson::factory()->inactive()->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/lessons/{$lesson->id}");

        $response->assertNotFound();
    }

    public function test_user_cannot_view_lesson_from_inactive_subject(): void
    {
        $subject = Subject::factory()->inactive()->create();
        $lesson = Lesson::factory()->create([
            'subject_id' => $subject->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/lessons/{$lesson->id}");

        $response->assertNotFound();
    }

    public function test_unauthenticated_user_cannot_view_lesson(): void
    {
        $lesson = Lesson::factory()->create();

        $response = $this->getJson("/api/lessons/{$lesson->id}");
        $response->assertUnauthorized();
    }
}
