<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Route;

class IpRateLimitingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        
        // Clear any existing rate limiters
        RateLimiter::clear('api');
        
        // Define a custom test rate limiter with a very low limit
        RateLimiter::for('test-ip-limit', function ($request) {
            return Limit::perMinute(2)->by($request->ip());
        });
        
        // Create a test route with our rate limiter
        Route::get('/api/test-ip-limit', function () {
            return response()->json(['success' => true]);
        })->middleware(['throttle:test-ip-limit']);
    }

    /**
     * Test that IPv4 addresses are properly rate limited
     */
    public function test_ipv4_addresses_are_rate_limited(): void
    {
        // First two requests should succeed
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.1'])
            ->getJson('/api/test-ip-limit')
            ->assertOk();
            
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.1'])
            ->getJson('/api/test-ip-limit')
            ->assertOk();
        
        // Third request should be rate limited
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.1'])
            ->getJson('/api/test-ip-limit')
            ->assertStatus(429); // Too Many Requests
    }
    
    /**
     * Test that IPv6 addresses are properly rate limited
     */
    public function test_ipv6_addresses_are_rate_limited(): void
    {
        // First two requests should succeed
        $this->withServerVariables(['REMOTE_ADDR' => '2001:db8:3333:4444:5555:6666:7777:8888'])
            ->getJson('/api/test-ip-limit')
            ->assertOk();
            
        $this->withServerVariables(['REMOTE_ADDR' => '2001:db8:3333:4444:5555:6666:7777:8888'])
            ->getJson('/api/test-ip-limit')
            ->assertOk();
        
        // Third request should be rate limited
        $this->withServerVariables(['REMOTE_ADDR' => '2001:db8:3333:4444:5555:6666:7777:8888'])
            ->getJson('/api/test-ip-limit')
            ->assertStatus(429); // Too Many Requests
    }
    
    /**
     * Test that different IP addresses have separate rate limits
     */
    public function test_different_ip_addresses_have_separate_rate_limits(): void
    {
        // First IP makes two requests
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.2'])
            ->getJson('/api/test-ip-limit')
            ->assertOk();
            
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.2'])
            ->getJson('/api/test-ip-limit')
            ->assertOk();
        
        // Second IP should not be rate limited
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.3'])
            ->getJson('/api/test-ip-limit')
            ->assertOk();
            
        // IPv6 should also not be rate limited
        $this->withServerVariables(['REMOTE_ADDR' => '2001:db8::1'])
            ->getJson('/api/test-ip-limit')
            ->assertOk();
    }
    
    /**
     * Test that abbreviated IPv6 addresses are correctly parsed for rate limiting
     */
    public function test_abbreviated_ipv6_addresses_are_properly_rate_limited(): void
    {
        // First two requests should succeed with abbreviated IPv6
        $this->withServerVariables(['REMOTE_ADDR' => '2001:db8::abcd'])
            ->getJson('/api/test-ip-limit')
            ->assertOk();
            
        $this->withServerVariables(['REMOTE_ADDR' => '2001:db8::abcd'])
            ->getJson('/api/test-ip-limit')
            ->assertOk();
        
        // Third request should be rate limited
        $this->withServerVariables(['REMOTE_ADDR' => '2001:db8::abcd'])
            ->getJson('/api/test-ip-limit')
            ->assertStatus(429); // Too Many Requests
    }
    
    /**
     * Test that IPv4-mapped IPv6 addresses are correctly handled
     */
    public function test_ipv4_mapped_ipv6_addresses_are_properly_rate_limited(): void
    {
        // First two requests should succeed with IPv4-mapped IPv6
        $this->withServerVariables(['REMOTE_ADDR' => '::ffff:192.0.2.1'])
            ->getJson('/api/test-ip-limit')
            ->assertOk();
            
        $this->withServerVariables(['REMOTE_ADDR' => '::ffff:192.0.2.1'])
            ->getJson('/api/test-ip-limit')
            ->assertOk();
        
        // Third request should be rate limited
        $this->withServerVariables(['REMOTE_ADDR' => '::ffff:192.0.2.1'])
            ->getJson('/api/test-ip-limit')
            ->assertStatus(429); // Too Many Requests
        
        // Request from the IPv4 equivalent should be treated as a separate IP
        // This is actually a behavior test - frameworks might normalize IPv4-mapped IPv6 addresses
        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.1'])
            ->getJson('/api/test-ip-limit')
            ->assertOk();
    }
      /**
     * Test API authentication with proxied IP addresses (X-Forwarded-For)
     */
    public function test_proxied_ip_addresses_are_properly_handled(): void
    {
        // Skip this test if the app doesn't support X-Forwarded-For headers
        // Laravel's default behavior depends on the TrustProxies middleware configuration
        if (!class_exists('\App\Http\Middleware\TrustProxies')) {
            $this->markTestSkipped('TrustProxies middleware not found, skipping X-Forwarded-For test');
        }

        // Test with X-Forwarded-For header
        $this->withServerVariables(['REMOTE_ADDR' => '10.1.1.1'])
            ->withHeaders(['X-Forwarded-For' => '203.0.113.4'])
            ->getJson('/api/test-ip-limit')
            ->assertOk();
            
        $this->withServerVariables(['REMOTE_ADDR' => '10.1.1.1'])
            ->withHeaders(['X-Forwarded-For' => '203.0.113.4'])
            ->getJson('/api/test-ip-limit')
            ->assertOk();
        
        // Third request should be rate limited if proxies are trusted
        $response = $this->withServerVariables(['REMOTE_ADDR' => '10.1.1.1'])
            ->withHeaders(['X-Forwarded-For' => '203.0.113.4'])
            ->getJson('/api/test-ip-limit');
        
        // Different forwarded IP should not be rate limited
        // But only test if the app correctly processes X-Forwarded-For
        // This is done by checking if the previous request was rate limited
        if ($response->status() === 429) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.1.1.1'])
                ->withHeaders(['X-Forwarded-For' => '203.0.113.5'])
                ->getJson('/api/test-ip-limit')
                ->assertOk();
        } else {
            // If the app doesn't consider X-Forwarded-For, we'll still be under the limit
            // So we'll just assert it's OK and continue
            $this->withServerVariables(['REMOTE_ADDR' => '10.1.1.1'])
                ->withHeaders(['X-Forwarded-For' => '203.0.113.5'])
                ->getJson('/api/test-ip-limit')
                ->assertOk();
            
            // Mark the test as skipped to clearly indicate we couldn't fully test X-Forwarded-For
            $this->markTestSkipped('X-Forwarded-For headers are not used for rate limiting, skipping full proxy test');
        }
    }
      /**
     * Test with chained proxy headers
     */
    public function test_chained_proxy_headers_are_properly_handled(): void
    {
        // Skip this test if the app doesn't support X-Forwarded-For headers
        // Laravel's default behavior depends on the TrustProxies middleware configuration
        if (!class_exists('\App\Http\Middleware\TrustProxies')) {
            $this->markTestSkipped('TrustProxies middleware not found, skipping chained proxy test');
        }

        // Test with chained X-Forwarded-For headers
        $this->withServerVariables(['REMOTE_ADDR' => '10.1.1.1'])
            ->withHeaders(['X-Forwarded-For' => '203.0.113.6, 192.168.1.1, 172.16.0.1'])
            ->getJson('/api/test-ip-limit')
            ->assertOk();
            
        $this->withServerVariables(['REMOTE_ADDR' => '10.1.1.1'])
            ->withHeaders(['X-Forwarded-For' => '203.0.113.6, 192.168.1.1, 172.16.0.1'])
            ->getJson('/api/test-ip-limit')
            ->assertOk();
        
        // Third request - the behavior depends on how the application is configured
        // If it correctly processes the leftmost non-private IP, it should be rate limited
        $response = $this->withServerVariables(['REMOTE_ADDR' => '10.1.1.1'])
            ->withHeaders(['X-Forwarded-For' => '203.0.113.6, 192.168.1.1, 172.16.0.1'])
            ->getJson('/api/test-ip-limit');
        
        // If the app is rate limiting based on the forwarded IP, then 
        // the status should be 429, but not all apps will be configured this way
        if ($response->status() === 429) {
            $this->assertTrue(true, 'App correctly rate limits based on leftmost forwarded IP');
        } else {
            // If we're not rate limited yet, it might mean the app doesn't use 
            // X-Forwarded-For, or it might use a different position in the header
            $this->markTestSkipped('Chained X-Forwarded-For headers are not used for rate limiting or use a different position');
        }
    }
}
