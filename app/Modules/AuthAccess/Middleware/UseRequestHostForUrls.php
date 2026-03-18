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
        $scheme = $this->resolveScheme($request);
        $host = $this->resolveHost($request);

        URL::forceRootUrl($scheme.'://'.$host);

        return $next($request);
    }

    private function resolveScheme(Request $request): string
    {
        $forwardedProto = $this->firstHeaderValue($request->headers->get('x-forwarded-proto'));
        if (\in_array($forwardedProto, ['http', 'https'], true)) {
            return $forwardedProto;
        }

        $requestScheme = strtolower($request->getScheme());
        if (\in_array($requestScheme, ['http', 'https'], true)) {
            return $requestScheme;
        }

        return str_starts_with((string) config('app.url'), 'https://') ? 'https' : 'http';
    }

    private function resolveHost(Request $request): string
    {
        $forwardedHost = $this->firstHeaderValue($request->headers->get('x-forwarded-host'));

        if ($forwardedHost !== '') {
            $parsedHost = parse_url('http://'.$forwardedHost, PHP_URL_HOST);
            $parsedPort = parse_url('http://'.$forwardedHost, PHP_URL_PORT);

            if (is_string($parsedHost) && $parsedHost !== '') {
                return is_int($parsedPort) ? $parsedHost.':'.$parsedPort : $parsedHost;
            }
        }

        return $request->getHttpHost();
    }

    private function firstHeaderValue(mixed $headerValue): string
    {
        if (! is_string($headerValue) || $headerValue === '') {
            return '';
        }

        return strtolower(trim(explode(',', $headerValue)[0]));
    }
}
