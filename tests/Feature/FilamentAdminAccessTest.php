<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class FilamentAdminAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Don't mock the application - use config/app.php settings
    }

    /**
     * Test that non-admin users can't access Filament when admin middleware is active
     */
    public function test_non_admin_cannot_access_filament_admin_panel(): void
    {
        // Get the middleware list from the AdminPanelProvider
        $middlewares = app()->make(\App\Http\Middleware\AdminMiddleware::class);

        // Create a regular user (non-admin)
        $regularUser = User::factory()->create([
            'role' => 'user',
        ]);

        // Try to access the Filament admin dashboard with authentication
        $this->actingAs($regularUser);
        
        // Simulate a request that would be processed by the admin middleware
        $request = request();
        $request->setUserResolver(function () use ($regularUser) {
            return $regularUser;
        });
        
        // This should throw a 403 exception
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        
        // Call the middleware handle method directly
        $middlewares->handle($request, function () {
            return response('OK');
        });
    }

    /**
     * Test that admin users can access Filament admin panel
     */
    public function test_admin_can_access_filament_admin_panel(): void
    {
        // Get the middleware
        $middleware = app()->make(\App\Http\Middleware\AdminMiddleware::class);

        // Create an admin user
        $adminUser = User::factory()->create([
            'role' => 'admin',
        ]);

        // Authenticate as the admin user
        $this->actingAs($adminUser);
        
        // Simulate a request that would be processed by the admin middleware
        $request = request();
        $request->setUserResolver(function () use ($adminUser) {
            return $adminUser;
        });
        
        // Should pass through the middleware without exception
        $response = $middleware->handle($request, function () {
            return response('OK');
        });
        
        $this->assertEquals('OK', $response->getContent());
    }
}
