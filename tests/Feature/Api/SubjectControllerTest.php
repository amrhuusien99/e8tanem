<?php

namespace Tests\Feature\Api;

use App\Models\Subject;
use App\Models\User;
use App\Models\Lesson;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubjectControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_list_active_subjects(): void
    {
        $activeSubject = Subject::factory()->create();
        Subject::factory()->inactive()->create();

        $response = $this->actingAs($this->user)
            ->getJson('/api/subjects');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['name' => $activeSubject->name]);
    }

    public function test_user_can_view_active_subject_with_lessons(): void
    {
        $subject = Subject::factory()
            ->has(Lesson::factory()->count(3))
            ->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/subjects/{$subject->id}");

        $response->assertOk()
            ->assertJsonCount(3, 'lessons')
            ->assertJsonFragment(['name' => $subject->name]);
    }

    public function test_user_cannot_view_inactive_subject(): void
    {
        $subject = Subject::factory()->inactive()->create();

        $response = $this->actingAs($this->user)
            ->getJson("/api/subjects/{$subject->id}");

        $response->assertNotFound();
    }

    public function test_unauthenticated_user_cannot_access_subjects(): void
    {
        $response = $this->getJson('/api/subjects');
        $response->assertUnauthorized();
    }
}
