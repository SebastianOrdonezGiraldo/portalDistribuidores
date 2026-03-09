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
        // Keep generated URLs on the same host where the user is browsing
        // (localhost/ngrok) to avoid session split across domains.
        URL::forceRootUrl($request->getSchemeAndHttpHost());

        return $next($request);
    }
}
