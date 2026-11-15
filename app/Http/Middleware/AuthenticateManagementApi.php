<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateManagementApi
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            app()->environment('local')
            && config('sipred.local_crud_without_login')
            && in_array($request->ip(), ['127.0.0.1', '::1'], true)
        ) {
            return $next($request);
        }

        return app(Authenticate::class)->handle(
            $request,
            fn (Request $request): Response => app(AuthenticateSession::class)->handle($request, $next),
            'web',
        );
    }
}
