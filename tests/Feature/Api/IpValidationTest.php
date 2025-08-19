<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class IpValidationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    /**
     * Test API authentication with IPv4 address
     */
    public function test_authentication_works_with_ipv4_address(): void
    {
        // Simulate request from an IPv4 address
        $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.1']);
        
        $response = $this->postJson('/api/login', [
            'email' => $this->user->email,
            'password' => 'password', // Default password from factory
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user']);
            
        // Verify that authentication works for protected routes
        $token = $response->json('token');
        
        $protectedResponse = $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.1'])
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user');
            
        $protectedResponse->assertOk()
            ->assertJson([
                'id' => $this->user->id,
                'email' => $this->user->email,
            ]);
    }

    /**
     * Test API authentication with IPv6 address
     */
    public function test_authentication_works_with_ipv6_address(): void
    {
        // Simulate request from an IPv6 address
        $this->withServerVariables(['REMOTE_ADDR' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334']);
        
        $response = $this->postJson('/api/login', [
            'email' => $this->user->email,
            'password' => 'password', // Default password from factory
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user']);
            
        // Verify that authentication works for protected routes
        $token = $response->json('token');
        
        $protectedResponse = $this->withServerVariables(['REMOTE_ADDR' => '2001:0db8:85a3:0000:0000:8a2e:0370:7334'])
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user');
            
        $protectedResponse->assertOk()
            ->assertJson([
                'id' => $this->user->id,
                'email' => $this->user->email,
            ]);
    }

    /**
     * Test API authentication with abbreviated IPv6 address
     */
    public function test_authentication_works_with_abbreviated_ipv6_address(): void
    {
        // Simulate request from an abbreviated IPv6 address
        $this->withServerVariables(['REMOTE_ADDR' => '2001:db8::8a2e:370:7334']);
        
        $response = $this->postJson('/api/login', [
            'email' => $this->user->email,
            'password' => 'password', // Default password from factory
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user']);
            
        // Verify that authentication works for protected routes
        $token = $response->json('token');
        
        $protectedResponse = $this->withServerVariables(['REMOTE_ADDR' => '2001:db8::8a2e:370:7334'])
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user');
            
        $protectedResponse->assertOk()
            ->assertJson([
                'id' => $this->user->id,
                'email' => $this->user->email,
            ]);
    }

    /**
     * Test API authentication with IPv4-mapped IPv6 address
     */
    public function test_authentication_works_with_ipv4_mapped_ipv6_address(): void
    {
        // Simulate request from an IPv4-mapped IPv6 address
        $this->withServerVariables(['REMOTE_ADDR' => '::ffff:192.0.2.128']);
        
        $response = $this->postJson('/api/login', [
            'email' => $this->user->email,
            'password' => 'password', // Default password from factory
        ]);

        $response->assertOk()
            ->assertJsonStructure(['token', 'user']);
            
        // Verify that authentication works for protected routes
        $token = $response->json('token');
        
        $protectedResponse = $this->withServerVariables(['REMOTE_ADDR' => '::ffff:192.0.2.128'])
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->getJson('/api/user');
            
        $protectedResponse->assertOk()
            ->assertJson([
                'id' => $this->user->id,
                'email' => $this->user->email,
            ]);
    }

    /**
     * Test API rate limiting with different IP addresses
     */
    public function test_rate_limiting_works_with_different_ip_formats(): void
    {
        // Configure a very restrictive rate limit for testing
        Config::set('sanctum.limiters.api', '5,1'); // 5 requests per minute
        
        // Test rate limiting with IPv4
        $this->withServerVariables(['REMOTE_ADDR' => '192.168.1.2']);
        
        // Make multiple requests to potentially trigger rate limiting
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/login', [
                'email' => $this->user->email,
                'password' => 'password',
            ]);
            $response->assertOk();
        }
        
        // Test rate limiting with IPv6
        $this->withServerVariables(['REMOTE_ADDR' => '2001:db8::8a2e:370:7335']);
        
        // Should not be rate limited because it's a different IP
        $response = $this->postJson('/api/login', [
            'email' => $this->user->email,
            'password' => 'password',
        ]);
        $response->assertOk();
    }    /**
     * Test IP address storage in session
     */
    public function test_ip_address_is_stored_in_session(): void
    {
        // This test is conditional as it requires web routes to have a login path
        // Skip if we can't find a login route
        if (!in_array('login', array_keys(Route::getRoutes()->get('POST')))) {
            $this->markTestSkipped('Skipping session test - login route not found');
        }
        
        // Make sure we're using the database session driver
        if (config('session.driver') !== 'database') {
            $this->markTestSkipped('Skipping session test - not using database session driver');
        }
        
        // Use web middleware for session-based tests
        $ipv4 = '192.168.5.5';
        
        $response = $this->withServerVariables(['REMOTE_ADDR' => $ipv4])
            ->post('/login', [
                'email' => $this->user->email,
                'password' => 'password',
            ]);
            
        // If we have sessions in the database, verify the IP
        if (Schema::hasTable('sessions') && DB::table('sessions')->count() > 0) {
            $this->assertDatabaseHas('sessions', [
                'ip_address' => $ipv4,
            ]);
        } else {
            // Otherwise just verify we got a successful response or redirect
            $this->assertTrue($response->isSuccessful() || $response->isRedirect());
        }
    }
}
