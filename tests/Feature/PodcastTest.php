<?php

namespace Tests\Feature;

use App\Models\Podcast;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class PodcastTest extends TestCase
{
    use RefreshDatabase, WithFaker;
    
    protected User $user;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a user for authentication
        $this->user = User::factory()->create();
    }

    public function test_can_list_podcasts(): void
    {
        Podcast::factory()->count(3)->create(['is_active' => true]);
        Podcast::factory()->create(['is_active' => false]); // This one shouldn't appear

        $response = $this->actingAs($this->user)->getJson('/api/podcasts');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_podcasts_by_category(): void
    {
        Podcast::factory()->create(['category' => 'technology', 'is_active' => true]);
        Podcast::factory()->create(['category' => 'education', 'is_active' => true]);

        $response = $this->actingAs($this->user)->getJson('/api/podcasts?category=technology');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.category', 'technology');
    }

    public function test_can_show_podcast(): void
    {
        $podcast = Podcast::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->user)->getJson("/api/podcasts/{$podcast->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $podcast->id,
                'title' => $podcast->title,
                'category' => $podcast->category,
            ]);
    }

    public function test_can_increment_plays(): void
    {
        $podcast = Podcast::factory()->create([
            'is_active' => true,
            'plays_count' => 0
        ]);

        $response = $this->actingAs($this->user)->postJson("/api/podcasts/{$podcast->id}/play");

        $response->assertStatus(200)
            ->assertJson(['plays_count' => 1]);

        $this->assertEquals(1, $podcast->fresh()->plays_count);
    }
}