<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $revision = $this->readReleaseValue((string) config('health.revision_path'));
        $tree = $this->readReleaseValue((string) config('health.tree_path'));
        $failedCheck = null;

        try {
            $failedCheck = 'release_metadata';
            $this->assertSha($revision, 'REVISION');
            $this->assertSha($tree, 'TREE');

            $failedCheck = 'database';
            DB::select('select 1');

            $failedCheck = 'cache';
            $cacheKey = 'health:'.Str::uuid()->toString();
            Cache::put($cacheKey, 'ok', 10);

            if (Cache::get($cacheKey) !== 'ok') {
                throw new \RuntimeException('The cache value could not be read back.');
            }

            Cache::forget($cacheKey);

            $failedCheck = 'storage';
            $storagePath = (string) config('health.storage_path');

            if (! is_dir($storagePath) || ! is_writable($storagePath)) {
                throw new \RuntimeException('The storage directory is not writable.');
            }
        } catch (Throwable $exception) {
            Log::error('health.readiness_failed', [
                'check' => $failedCheck,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            return response()->json([
                'status' => 'unavailable',
            ], 503, [
                'Cache-Control' => 'no-store, private',
            ]);
        }

        return response()->json([
            'status' => 'ok',
            'revision' => $revision,
            'tree' => $tree,
        ], 200, [
            'Cache-Control' => 'no-store, private',
        ]);
    }

    private function readReleaseValue(string $path): string
    {
        if ($path === '' || ! is_file($path) || ! is_readable($path)) {
            return '';
        }

        return trim((string) file_get_contents($path));
    }

    private function assertSha(string $value, string $label): void
    {
        if (preg_match('/\A[0-9a-f]{40}\z/', $value) !== 1) {
            throw new \RuntimeException("{$label} does not contain a valid Git SHA.");
        }
    }
}
