<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\LocaleMiddleware;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\SecurityHeadersMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(LocaleMiddleware::class);
        $middleware->web(\Modules\Context\Http\Middleware\InitializeTenant::class);
        $middleware->web(SecurityHeadersMiddleware::class);
        $middleware->api(SecurityHeadersMiddleware::class);
        $middleware->validateCsrfTokens(except: [
            'otp/*',
        ]);
        $middleware->alias([
            'check.permission' => CheckPermission::class,
            'tenant.context'   => \Modules\Context\Http\Middleware\InitializeTenant::class,
            'vendor.auth'      => \Modules\Marketplace\Http\Middleware\EnsureUserIsVendor::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
