<?php

use App\Http\Middleware\AuthenticateDevice;
use App\Http\Middleware\EnsureCredentialsChanged;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'device.token' => AuthenticateDevice::class,
            'password.changed' => EnsureCredentialsChanged::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Data yang dikirim tidak valid.',
                    'code' => 'validation_error',
                    'errors' => $e->errors(),
                ], 422);
            }
        });

        $exceptions->render(function (HttpExceptionInterface $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $status = $e->getStatusCode();
                $code = match ($status) {
                    401 => 'unauthorized',
                    403 => 'forbidden',
                    404 => 'not_found',
                    422 => 'validation_error',
                    429 => 'too_many_requests',
                    default => 'server_error',
                };

                return response()->json([
                    'message' => $e->getMessage() ?: 'Terjadi kesalahan.',
                    'code' => $code,
                ], $status, $e->getHeaders());
            }
        });
    })->create();
