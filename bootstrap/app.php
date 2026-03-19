<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Session\TokenMismatchException;
use App\Modules\AuthAccess\Middleware\RoleMiddleware;
use App\Modules\AuthAccess\Middleware\UseRequestHostForUrls;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);

        $middleware->web(append: [
            UseRequestHostForUrls::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Cuando el token CSRF expira (sesión vencida), redirigir al login en lugar de mostrar 419
        $exceptions->render(function (TokenMismatchException $e, $request) {
            return redirect()->route('login')->withErrors([
                'email' => 'Tu sesión ha expirado. Por favor inicia sesión nuevamente.',
            ]);
        });
    })->create();
