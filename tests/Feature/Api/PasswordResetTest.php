<?php

namespace Tests\Feature\Api;

use App\Models\PasswordResetCode;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_request_verification_code(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $response = $this->postJson('/api/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Verification code sent to your email']);

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_user_cannot_request_code_with_invalid_email(): void
    {
        $response = $this->postJson('/api/forgot-password', [
            'email' => 'nonexistent@example.com',
        ]);

        $response->assertStatus(422);
    }

    public function test_user_can_verify_reset_code(): void
    {
        $user = User::factory()->create();
        $resetCode = PasswordResetCode::createCodeForEmail($user->email);

        $response = $this->postJson('/api/verify-reset-code', [
            'email' => $user->email,
            'code' => $resetCode->code
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Code verified successfully'])
            ->assertJsonStructure(['reset_token']);
    }

    public function test_user_cannot_verify_expired_code(): void
    {
        $user = User::factory()->create();
        $resetCode = PasswordResetCode::create([
            'email' => $user->email,
            'code' => '123456',
            'expires_at' => now()->subMinutes(16), // Expired 16 minutes ago
            'used' => false
        ]);

        $response = $this->postJson('/api/verify-reset-code', [
            'email' => $user->email,
            'code' => $resetCode->code
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Invalid or expired code']);
    }

    public function test_user_cannot_verify_used_code(): void
    {
        $user = User::factory()->create();
        $resetCode = PasswordResetCode::create([
            'email' => $user->email,
            'code' => '123456',
            'expires_at' => now()->addMinutes(15),
            'used' => true
        ]);

        $response = $this->postJson('/api/verify-reset-code', [
            'email' => $user->email,
            'code' => $resetCode->code
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Invalid or expired code']);
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword123')
        ]);
        
        $resetCode = PasswordResetCode::create([
            'email' => $user->email,
            'code' => '123456',
            'reset_token' => 'valid-token',
            'expires_at' => now()->addMinutes(15),
            'used' => true
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'reset_token' => 'valid-token',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertOk()
            ->assertJson(['message' => 'Password reset successfully']);

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
    }

    public function test_reset_password_requires_valid_token(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('oldpassword123')
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'reset_token' => 'invalid-token',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123'
        ]);

        $response->assertStatus(422)
            ->assertJson(['message' => 'Invalid or expired reset token']);

        $this->assertTrue(Hash::check('oldpassword123', $user->fresh()->password));
    }

    public function test_reset_password_requires_password_confirmation(): void
    {
        $user = User::factory()->create();
        
        $resetCode = PasswordResetCode::create([
            'email' => $user->email,
            'code' => '123456',
            'reset_token' => 'valid-token',
            'expires_at' => now()->addMinutes(15),
            'used' => true
        ]);

        $response = $this->postJson('/api/reset-password', [
            'email' => $user->email,
            'reset_token' => 'valid-token',
            'password' => 'newpassword123'
        ]);

        $response->assertStatus(422);
    }
}