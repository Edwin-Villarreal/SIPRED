<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\AuthenticateSensorToken;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias(['device.token' => AuthenticateSensorToken::class]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
        $exceptions->render(function (Throwable $exception, Request $request) {
            if (
                ! $request->is('api/*')
                || $exception instanceof \Illuminate\Validation\ValidationException
                || $exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException
                || $exception instanceof \Illuminate\Auth\AuthenticationException
                || $exception instanceof \Illuminate\Auth\Access\AuthorizationException
            ) {
                return null;
            }

            if ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface && $exception->getStatusCode() < 500) {
                return null;
            }

            return response()->json([
                'success' => false,
                'message' => 'Error interno del servidor.',
            ], 500);
        });
    })->create();
