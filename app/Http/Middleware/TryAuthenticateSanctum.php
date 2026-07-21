<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class TryAuthenticateSanctum
{
    public function handle(Request $request, Closure $next)
    {
        $bearerToken = $request->bearerToken();
        if ($bearerToken && !$request->user()) {
            $accessToken = PersonalAccessToken::findToken($bearerToken);
            if ($accessToken && $accessToken->tokenable) {
                $request->setUserResolver(fn() => $accessToken->tokenable);
                auth()->guard()->setUser($accessToken->tokenable);
            }
        }

        return $next($request);
    }
}
