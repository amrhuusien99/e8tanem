<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    public const HOME = '/home';

    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth-sensitive', function (Request $request) {
            $identifier = strtolower($request->input('email') ?? $request->ip());

            return Limit::perMinute(10)
                ->by($identifier . '|' . $request->ip())
                ->response(function () {
                    return response()->json([
                        'message' => 'عدد المحاولات كبير جداً، يرجى المحاولة لاحقاً.'
                    ], 429);
                });
        });

        RateLimiter::for('otp-sensitive', function (Request $request) {
            $identifier = strtolower($request->input('email') ?? $request->ip());

            return Limit::perMinutes(5, 5)
                ->by('otp|' . $identifier)
                ->response(function () {
                    return response()->json([
                        'message' => 'تم تجاوز الحد المسموح لمحاولات التحقق. حاول بعد قليل.'
                    ], 429);
                });
        });

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }
}
