<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CheckAccessTokenExpiry
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'غير مصرح لك'], 401);
        }

        if ($user->access_token_expires_at && Carbon::now()->greaterThan($user->access_token_expires_at)) {
            // حذف التوكن المنتهي
            $user->currentAccessToken()->delete();
            return response()->json(['message' => 'Access Token منتهي الصلاحية'], 401);
        }

        return $next($request);
    }
}