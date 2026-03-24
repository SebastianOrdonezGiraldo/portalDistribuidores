<?php

use App\Modules\AuthAccess\Middleware\RoleMiddleware;
use App\Modules\AuthAccess\Middleware\UseRequestHostForUrls;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Session\TokenMismatchException;

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
        $exceptions->render(function (PostTooLargeException $e, $request) {
            return back()->withInput()->withErrors([
                'media_upload' => 'Los archivos seleccionados superan el tamano maximo permitido para la carga total. Reduce la cantidad o el peso de fotos y documentos e intentalo nuevamente.',
            ]);
        });

        // Cuando el token CSRF expira (sesion vencida), redirigir al login en lugar de mostrar 419.
        $exceptions->render(function (TokenMismatchException $e, $request) {
            return redirect()->route('login')->withErrors([
                'email' => 'Tu sesion ha expirado. Por favor inicia sesion nuevamente.',
            ]);
        });
    })->create();
