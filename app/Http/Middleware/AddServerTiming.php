<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddServerTiming
{
    public function handle(Request $request, Closure $next): Response
    {
        $requestStartedAt = microtime(true);
        $response = $next($request);

        $totalMs = (microtime(true) - $requestStartedAt) * 1000;
        self::addMetric($request, 'total', $totalMs, 'Total request time');

        $metrics = $request->attributes->get('server_timing_metrics', []);
        if (! is_array($metrics) || $metrics === []) {
            return $response;
        }

        $parts = [];
        foreach ($metrics as $metric) {
            if (! is_array($metric) || ! isset($metric['name'], $metric['dur'])) {
                continue;
            }

            $name = preg_replace('/[^a-zA-Z0-9_\\-]/', '', (string) $metric['name']) ?: 'metric';
            $dur = number_format(max(0, (float) $metric['dur']), 2, '.', '');
            $part = "{$name};dur={$dur}";

            if (! empty($metric['desc'])) {
                $desc = str_replace('"', '', (string) $metric['desc']);
                $part .= ";desc=\"{$desc}\"";
            }

            $parts[] = $part;
        }

        if ($parts === []) {
            return $response;
        }

        $existing = (string) $response->headers->get('Server-Timing', '');
        $serverTiming = $existing !== ''
            ? $existing.', '.implode(', ', $parts)
            : implode(', ', $parts);

        $response->headers->set('Server-Timing', $serverTiming);

        return $response;
    }

    public static function addMetric(Request $request, string $name, float $durationMs, ?string $description = null): void
    {
        $metrics = $request->attributes->get('server_timing_metrics', []);
        if (! is_array($metrics)) {
            $metrics = [];
        }

        $metrics[] = [
            'name' => $name,
            'dur' => $durationMs,
            'desc' => $description,
        ];

        $request->attributes->set('server_timing_metrics', $metrics);
    }
}
