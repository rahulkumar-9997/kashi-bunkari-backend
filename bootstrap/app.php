<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\TryAuthenticateSanctum; 
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Http\Middleware\HandleCors;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'check.device' => \App\Http\Middleware\CheckDevice::class,
            'cart.optional-auth' => TryAuthenticateSanctum::class,
        ]);
        $middleware->prepend(HandleCors::class); 
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Please login first.',
                    'error_code' => 401
                ], 401);
            }
        });
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            // Check if it's an API route OR expects JSON
            if ($request->is('api/*') || $request->expectsJson()) {
                $allowed = $e->getHeaders()['Allow'] ?? '';
                return response()->json([
                    'success' => false,
                    'message' => 'Method not allowed. Please use POST method.',
                    'allowed_methods' => $allowed ? explode(', ', $allowed) : ['POST'],
                    'error_code' => 405
                ], 405);
            }
        });
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'API route not found.',
                    'error_code' => 404
                ], 404);
            }
        });
        $exceptions->render(function (\Throwable $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'An error occurred while processing your request.',
                    'error_code' => 500,
                    'debug' => config('app.debug') ? $e->getMessage() : null
                ], 500);
            }
        });
    })->create();
