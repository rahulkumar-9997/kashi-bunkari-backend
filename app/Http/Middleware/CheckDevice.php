<?php

namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class CheckDevice
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if ($user && $user->currentAccessToken()) {
            $token = $user->currentAccessToken();
            $currentAgent = substr((string) $request->userAgent(), 0, 255);
            $currentIp = $request->ip();

            if (
                $token->user_agent !== $currentAgent ||
                $token->ip_address !== $currentIp
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized device detected'
                ], 401);
            }
        }
        return $next($request);
    }
}
