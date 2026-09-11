<?php

use App\Http\Middleware\EnforceAdminIpRules;
use App\Http\Middleware\EnsureAdminSessionIsFresh;
use App\Http\Middleware\RequireAdminAccess;
use App\Http\Middleware\ShareAuthWithViews;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->throttleApi();
        $middleware->alias([
            'admin.access' => RequireAdminAccess::class,
            'admin.session' => EnsureAdminSessionIsFresh::class,
            'admin.ip' => EnforceAdminIpRules::class,
        ]);
        $middleware->web(append: [
            ShareAuthWithViews::class,
        ]);
        $middleware->validateCsrfTokens(except: [
            'api/chat/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request, Throwable $e): bool {
            return $request->is('api/*') || $request->expectsJson();
        });
    })->create();
