<?php

namespace Tests\Feature\Api;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private User $otherUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Make sure to disable foreign key checks for SQLite
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = OFF');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
        }
        
        // Completely wipe the notifications table - guaranteed to work with SQLite
        DB::table('notifications')->delete();
        
        // Re-enable foreign key checks
        if (DB::connection()->getDriverName() === 'sqlite') {
            DB::statement('PRAGMA foreign_keys = ON');
        } else {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
        
        // Now create our test users
        $this->user = User::factory()->create();
        $this->otherUser = User::factory()->create();
    }

    public function test_user_can_get_notifications(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Test Title',
            'message' => 'Test notification message',
            'type' => 'general'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment([
                'id' => $notification->id,
                'title' => 'Test Title',
                'message' => 'Test notification message'
            ]);
    }
    
    public function test_user_can_see_global_notifications(): void
    {
        // Create a global notification
        $globalNotification = Notification::factory()->global()->create([
            'title' => 'Global Notification',
            'message' => 'This is for all users',
            'type' => 'general'
        ]);
        
        // Create a user-specific notification
        $userNotification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'User Notification',
            'message' => 'This is just for you',
            'type' => 'general'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['title' => 'Global Notification'])
            ->assertJsonFragment(['title' => 'User Notification']);
    }
    
    public function test_expired_global_notifications_are_not_returned(): void
    {
        // Create an expired global notification
        Notification::factory()->global()->expired()->create([
            'title' => 'Expired Global Notification',
        ]);
        
        // Create a valid global notification
        Notification::factory()->global()->create([
            'title' => 'Valid Global Notification',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonFragment(['title' => 'Valid Global Notification'])
            ->assertJsonMissing(['title' => 'Expired Global Notification']);
    }
    
    public function test_global_notification_mark_as_read(): void
    {
        // Create a global notification
        $globalNotification = Notification::factory()->global()->create([
            'title' => 'Global Notification',
            'message' => 'This should be marked as read',
            'type' => 'general'
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/notifications/{$globalNotification->id}/mark-as-read");

        $response->assertOk();
        
        // Check that a user-specific version was created with is_read = true
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'title' => 'Global Notification',
            'message' => 'This should be marked as read',
            'is_read' => true
        ]);
        
        // Original global notification should still exist and be unread
        $this->assertDatabaseHas('notifications', [
            'id' => $globalNotification->id,
            'user_id' => null,
            'is_read' => false
        ]);
    }
    
    public function test_mark_all_as_read_includes_global_notifications(): void
    {
        // Create some user-specific notifications
        Notification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'is_read' => false
        ]);
        
        // Create some global notifications
        Notification::factory()->global()->count(3)->create([
            'is_read' => false
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/notifications/mark-all-as-read');

        $response->assertOk();
        
        // All user-specific notifications should be read
        $this->assertEquals(0, $this->user->notifications()->where('is_read', false)->count());
        
        // Check if user-specific copies of global notifications were created
        $globalNotifications = Notification::whereNull('user_id')->get();
        foreach ($globalNotifications as $notification) {
            $this->assertDatabaseHas('notifications', [
                'user_id' => $this->user->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'is_read' => true
            ]);
        }
    }
    
    public function test_unread_count_includes_global_notifications(): void
    {
        // Create 2 user-specific unread notifications
        Notification::factory()->count(2)->create([
            'user_id' => $this->user->id,
            'is_read' => false
        ]);
        
        // Create 3 global unread notifications
        Notification::factory()->global()->count(3)->create([
            'is_read' => false
        ]);
        
        // Create 1 global notification that user has already read
        $readGlobal = Notification::factory()->global()->create([
            'title' => 'Already Read Global',
            'message' => 'User has read this',
            'is_read' => false
        ]);
        
        // Create user-specific version marked as read
        Notification::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Already Read Global',
            'message' => 'User has read this',
            'is_read' => true
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications/unread-count');

        $response->assertOk()
            ->assertJson(['count' => 5]); // 2 user-specific + 3 unread global (not counting the one marked as read)
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Test Title',
            'message' => 'Test notification message',
            'type' => 'general'
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/notifications/{$notification->id}/mark-as-read");

        $response->assertOk();
        $this->assertTrue($notification->fresh()->is_read);
    }

    public function test_user_cannot_mark_other_users_notification_as_read(): void
    {
        $notification = Notification::factory()->create([
            'user_id' => $this->otherUser->id,
            'title' => 'Test Title',
            'message' => 'Test notification message',
            'type' => 'general'
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/notifications/{$notification->id}/mark-as-read");

        $response->assertStatus(403);
        $this->assertFalse($notification->fresh()->is_read);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'title' => 'Test Title',
            'message' => 'Test notification message',
            'type' => 'general'
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/notifications/mark-all-as-read');

        $response->assertOk();
        $this->assertEquals(0, $this->user->notifications()->where('is_read', false)->count());
    }

    public function test_user_can_get_unread_count(): void
    {
        // Create 3 regular notifications
        Notification::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'title' => 'Test Title',
            'message' => 'Test notification message',
            'type' => 'general'
        ]);
        
        // Create 2 expired notifications (which should not be counted)
        Notification::factory()->expired()->count(2)->create([
            'user_id' => $this->user->id,
            'title' => 'Expired Title',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications/unread-count');

        $response->assertOk()
            ->assertJson(['count' => 3]);
    }

    public function test_user_cant_see_other_users_notifications(): void
    {
        // Create notifications for other user
        Notification::factory()->count(3)->create([
            'user_id' => $this->otherUser->id,
            'title' => 'Other User Notification',
            'message' => 'This should not be visible',
            'type' => 'general'
        ]);
        
        // Create one notification for the current user
        Notification::factory()->create([
            'user_id' => $this->user->id, 
            'title' => 'My Notification',
            'message' => 'This should be visible',
            'type' => 'general'
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/notifications');

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonFragment(['title' => 'My Notification'])
            ->assertJsonMissing(['title' => 'Other User Notification']);
    }

    public function test_unauthenticated_user_cannot_access_notifications(): void
    {
        $response = $this->getJson('/api/notifications');
        $response->assertUnauthorized();
    }
}
