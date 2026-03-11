<?php

namespace App\Modules\AuthAccess\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\Response;

class UseRequestHostForUrls
{
    public function handle(Request $request, Closure $next): Response
    {
        // En producción Railway termina SSL en el proxy,
        // la petición interna llega como http, pero el usuario
        // navega por https. Forzamos https si APP_URL lo usa.
        $appUrl = config('app.url');
        $scheme = str_starts_with($appUrl, 'https') ? 'https' : $request->getScheme();

        URL::forceRootUrl($scheme . '://' . $request->getHost());

        return $next($request);
    }
}
