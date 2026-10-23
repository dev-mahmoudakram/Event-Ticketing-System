<?php

use App\Http\Middleware\EnsureUserHasPermission;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(fn () => route('admin.login'));
        // TLS ends at the proxy in front of the app, so take the scheme and client IP from it —
        // or every link is built as http:// on an https page. Only the proxies listed in
        // config/trustedproxy.php (Cloudflare, the local reverse proxy, TRUSTED_PROXIES) are
        // believed; anyone else's forwarded headers are ignored so a client can't fake its IP.
        // The forwarded host is never trusted, so a forged one can't rewrite our links.
        $middleware->trustProxies(
            headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PORT | Request::HEADER_X_FORWARDED_PROTO,
        );
        $middleware->alias(['permission' => EnsureUserHasPermission::class]);
        // Kashier's server posts here with no session; its signature is checked instead.
        $middleware->validateCsrfTokens(except: ['payments/kashier/webhook']);
        $middleware->web(append: [
            SetLocale::class,
            SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
