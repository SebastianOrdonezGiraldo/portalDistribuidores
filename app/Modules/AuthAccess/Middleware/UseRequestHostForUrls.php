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
        // In production Railway terminates SSL at the proxy level.
        // The internal request arrives as http://, but the user browses via https://.
        // We derive the correct scheme from APP_URL so generated URLs use https://.
        $appUrl = config('app.url');
        $scheme = str_starts_with((string) $appUrl, 'https') ? 'https' : $request->getScheme();

        URL::forceRootUrl($scheme . '://' . $request->getHost());

        return $next($request);
    }
}
