<?php

use App\Http\Middleware\AddServerTiming;
use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\NormalizeAndValidateInput;
use App\Http\Middleware\ThrottleAiGenerationRequests;
use App\Http\Middleware\ThrottleSuspiciousAutomation;
use App\Modules\AuthAccess\Middleware\RoleMiddleware;
use App\Modules\Catalog\Support\ProductUploadLimits;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Log;
use Scoutapm\ScoutApmAgent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'suspicious_automation' => ThrottleSuspiciousAutomation::class,
        ]);

        $middleware->web(prepend: [
            NormalizeAndValidateInput::class,
        ]);

        $middleware->web(append: [
            AddServerTiming::class,
            EnsureActiveUser::class,
            ThrottleAiGenerationRequests::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (Throwable $e): void {
            app()->make(ScoutApmAgent::class)->recordThrowable($e);
        });

        // Handle 404 Not Found errors with custom view
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            Log::info('http.404', [
                'path' => $request->path(),
                'method' => $request->method(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'referrer' => $request->headers->get('referer'),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Página no encontrada',
                ], 404);
            }

            return response()->view('errors.404', [], 404);
        });

        $exceptions->render(function (TooManyRequestsHttpException $e, $request) {
            $retryAfter = $e->getHeaders()['Retry-After'] ?? null;
            $message = 'Has realizado demasiados intentos. Espera un momento e inténtalo de nuevo.';

            if ($request->expectsJson()) {
                $payload = ['message' => $message];

                if (is_numeric($retryAfter)) {
                    $payload['retry_after'] = (int) $retryAfter;
                }

                $headers = [];

                if ($retryAfter !== null) {
                    $headers['Retry-After'] = (string) $retryAfter;
                }

                return response()->json($payload, 429, $headers);
            }

            $isAuthFormRequest = $request->is('login')
                || $request->is('register')
                || $request->is('forgot-password')
                || $request->is('reset-password')
                || $request->is('reset-password/*');

            if ($isAuthFormRequest) {
                $response = redirect()
                    ->to($request->fullUrl())
                    ->withInput($request->except(['password', 'password_confirmation']))
                    ->withErrors(['email' => $message]);

                if ($retryAfter !== null) {
                    $response->headers->set('Retry-After', (string) $retryAfter);
                }

                return $response;
            }

            return response($message, 429);
        });

        $exceptions->render(function (PostTooLargeException $e, $request) {
            $referer = (string) $request->headers->get('referer', '');
            $currentHost = $request->getHost();
            $refererHost = parse_url($referer, PHP_URL_HOST);
            $fallbackUrl = app('router')->has('admin.products.create')
                ? route('admin.products.create')
                : url('/');

            if ($referer !== '' && ($refererHost === null || $refererHost === '' || $refererHost === $currentHost)) {
                $fallbackUrl = $referer;
            }

            Log::warning('request.post_too_large', [
                'path' => $request->path(),
                'referer' => $referer !== '' ? $referer : null,
                'content_length' => $request->server('CONTENT_LENGTH'),
                'ip' => $request->ip(),
                'user_id' => $request->user()?->getAuthIdentifier(),
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => ProductUploadLimits::totalSizeExceededMessage(),
                ], 413);
            }

            return redirect()
                ->to($fallbackUrl)
                ->withInput($request->except(['photos', 'tech_sheet']))
                ->withErrors([
                    'media_upload' => ProductUploadLimits::totalSizeExceededMessage(),
                ]);
        });

        // Cuando el token CSRF expira (sesion vencida), redirigir al login en lugar de mostrar 419.
        $exceptions->render(function (TokenMismatchException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Tu sesión ha expirado. Por favor inicia sesión nuevamente.'], 419);
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Tu sesión ha expirado. Por favor inicia sesión nuevamente.',
            ]);
        });
    })->create();
