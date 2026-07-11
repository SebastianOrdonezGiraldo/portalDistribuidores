<?php

namespace App\Modules\Inventory\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ContaPymeSyncState
{
    private const LOCK_KEY = 'contapyme_sync_running';

    private const STATUS_KEY = 'contapyme_sync_status';

    private const LOCK_TTL_SECONDS = 720;

    private const STATUS_TTL_SECONDS = 86400;

    /**
     * Atomically reserve the manual synchronization slot and expose its queued state.
     */
    public function queue(): bool
    {
        return $this->queueWithContext('manual', (string) Str::uuid());
    }

    public function queueWithContext(string $origin, string $runId): bool
    {
        if (! Cache::add(self::LOCK_KEY, true, self::LOCK_TTL_SECONDS)) {
            return false;
        }

        $queuedAt = now();

        $this->store(
            state: 'queued',
            message: 'Sincronización ContaPyme encolada.',
            runId: $runId,
            origin: $origin,
            queuedAt: $queuedAt->toIso8601String(),
            availableAt: $queuedAt->copy()->addSeconds(self::LOCK_TTL_SECONDS)->toIso8601String(),
        );

        return true;
    }

    public function isRunning(): bool
    {
        $state = $this->status()['state'] ?? null;

        return Cache::has(self::LOCK_KEY) && in_array($state, ['queued', 'running'], true);
    }

    /**
     * @return array{can_run:bool, reason:string, available_at:string|null, retry_after:int}
     */
    public function availability(): array
    {
        if (! (bool) config('contapyme.enabled')) {
            return [
                'can_run' => false,
                'reason' => 'disabled',
                'available_at' => null,
                'retry_after' => 0,
            ];
        }

        if (! Cache::has(self::LOCK_KEY)) {
            return $this->availableResponse();
        }

        $status = $this->status();
        $availableAt = $status['available_at'] ?? null;
        $retryAfter = $this->retryAfter($availableAt);

        if ($retryAfter <= 0) {
            return $this->availableResponse();
        }

        return [
            'can_run' => false,
            'reason' => in_array($status['state'] ?? null, ['queued', 'running'], true)
                ? 'running'
                : 'cooldown',
            'available_at' => $availableAt,
            'retry_after' => $retryAfter,
        ];
    }

    /**
     * @return array{state:string, message:string, summary:string|null, run_id:string|null, origin:string|null, queued_at:string|null, started_at:string|null, completed_at:string|null, available_at:string|null, error_count:int, error_groups:list<array{message:string, count:int}>, error_details:list<array{sku:string|null, phase:string, message:string}>, unmapped_count:int, unmapped_details:list<array{sku:string|null, irecurso:string|null, phase:string, message:string}>}|null
     */
    public function status(): ?array
    {
        $status = Cache::get(self::STATUS_KEY);

        if (! is_array($status) || ! filled($status['state'] ?? null)) {
            return null;
        }

        return [
            'state' => (string) $status['state'],
            'message' => (string) ($status['message'] ?? ''),
            'summary' => filled($status['summary'] ?? null) ? (string) $status['summary'] : null,
            'run_id' => filled($status['run_id'] ?? null) ? (string) $status['run_id'] : null,
            'origin' => filled($status['origin'] ?? null) ? (string) $status['origin'] : null,
            'queued_at' => filled($status['queued_at'] ?? null) ? (string) $status['queued_at'] : null,
            'started_at' => filled($status['started_at'] ?? null) ? (string) $status['started_at'] : null,
            'completed_at' => filled($status['completed_at'] ?? null) ? (string) $status['completed_at'] : null,
            'available_at' => filled($status['available_at'] ?? null) ? (string) $status['available_at'] : null,
            'error_count' => max(0, (int) ($status['error_count'] ?? 0)),
            'error_groups' => $this->normalizeErrorGroups($status['error_groups'] ?? []),
            'error_details' => $this->normalizeErrorDetails($status['error_details'] ?? []),
            'unmapped_count' => max(0, (int) ($status['unmapped_count'] ?? 0)),
            'unmapped_details' => $this->normalizeUnmappedDetails($status['unmapped_details'] ?? []),
        ];
    }

    public function markRunning(): void
    {
        $status = $this->status();

        $this->store(
            state: 'running',
            message: 'Sincronización ContaPyme en curso.',
            runId: $status['run_id'] ?? null,
            origin: $status['origin'] ?? null,
            queuedAt: $status['queued_at'] ?? now()->toIso8601String(),
            startedAt: now()->toIso8601String(),
            availableAt: $status['available_at'] ?? null,
        );
    }

    /**
     * @param  array{error_count?:int, failed?:int, error_groups?:array, error_details?:array, unmapped?:int, unmapped_details?:array}  $diagnostics
     */
    public function complete(string $summary, array $diagnostics = []): void
    {
        $status = $this->status();

        $this->store(
            state: 'completed',
            message: 'Sincronización ContaPyme completada.',
            runId: $status['run_id'] ?? null,
            origin: $status['origin'] ?? null,
            summary: $summary,
            queuedAt: $status['queued_at'] ?? null,
            startedAt: $status['started_at'] ?? null,
            completedAt: now()->toIso8601String(),
            availableAt: $status['available_at'] ?? null,
            errorCount: (int) ($diagnostics['error_count'] ?? $diagnostics['failed'] ?? 0),
            errorGroups: (array) ($diagnostics['error_groups'] ?? []),
            errorDetails: (array) ($diagnostics['error_details'] ?? []),
            unmappedCount: (int) ($diagnostics['unmapped'] ?? 0),
            unmappedDetails: (array) ($diagnostics['unmapped_details'] ?? []),
        );
    }

    /**
     * @param  array{error_count?:int, failed?:int, error_groups?:array, error_details?:array, unmapped?:int, unmapped_details?:array}  $diagnostics
     */
    public function fail(string $message, array $diagnostics = []): void
    {
        $status = $this->status();

        $this->store(
            state: 'failed',
            message: 'La sincronización ContaPyme falló.',
            runId: $status['run_id'] ?? null,
            origin: $status['origin'] ?? null,
            summary: $message,
            queuedAt: $status['queued_at'] ?? null,
            startedAt: $status['started_at'] ?? null,
            completedAt: now()->toIso8601String(),
            availableAt: $status['available_at'] ?? null,
            errorCount: (int) ($diagnostics['error_count'] ?? $diagnostics['failed'] ?? 0),
            errorGroups: (array) ($diagnostics['error_groups'] ?? []),
            errorDetails: (array) ($diagnostics['error_details'] ?? []),
            unmappedCount: (int) ($diagnostics['unmapped'] ?? 0),
            unmappedDetails: (array) ($diagnostics['unmapped_details'] ?? []),
        );
    }

    public function block(string $message): void
    {
        $this->store(
            state: 'blocked',
            message: $message,
            completedAt: now()->toIso8601String(),
            availableAt: null,
        );

        $this->release();
    }

    public function release(): void
    {
        Cache::forget(self::LOCK_KEY);
    }

    private function store(
        string $state,
        string $message,
        ?string $runId = null,
        ?string $origin = null,
        ?string $summary = null,
        ?string $queuedAt = null,
        ?string $startedAt = null,
        ?string $completedAt = null,
        ?string $availableAt = null,
        int $errorCount = 0,
        array $errorGroups = [],
        array $errorDetails = [],
        int $unmappedCount = 0,
        array $unmappedDetails = [],
    ): void {
        Cache::put(self::STATUS_KEY, [
            'state' => $state,
            'message' => $message,
            'summary' => $summary,
            'run_id' => $runId,
            'origin' => $origin,
            'queued_at' => $queuedAt,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
            'available_at' => $availableAt,
            'error_count' => max(0, $errorCount),
            'error_groups' => $errorGroups,
            'error_details' => $errorDetails,
            'unmapped_count' => max(0, $unmappedCount),
            'unmapped_details' => $unmappedDetails,
        ], self::STATUS_TTL_SECONDS);
    }

    /**
     * @return list<array{message:string, count:int}>
     */
    private function normalizeErrorGroups(mixed $groups): array
    {
        if (! is_array($groups)) {
            return [];
        }

        return collect($groups)
            ->filter(fn (mixed $group): bool => is_array($group) && filled($group['message'] ?? null))
            ->map(fn (array $group): array => [
                'message' => (string) $group['message'],
                'count' => max(0, (int) ($group['count'] ?? 0)),
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{sku:string|null, phase:string, message:string}>
     */
    private function normalizeErrorDetails(mixed $details): array
    {
        if (! is_array($details)) {
            return [];
        }

        return collect($details)
            ->filter(fn (mixed $detail): bool => is_array($detail) && filled($detail['message'] ?? null))
            ->map(fn (array $detail): array => [
                'sku' => filled($detail['sku'] ?? null) ? (string) $detail['sku'] : null,
                'phase' => filled($detail['phase'] ?? null) ? (string) $detail['phase'] : 'desconocida',
                'message' => (string) $detail['message'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{sku:string|null, irecurso:string|null, phase:string, message:string}>
     */
    private function normalizeUnmappedDetails(mixed $details): array
    {
        if (! is_array($details)) {
            return [];
        }

        return collect($details)
            ->filter(fn (mixed $detail): bool => is_array($detail) && filled($detail['message'] ?? null))
            ->map(fn (array $detail): array => [
                'sku' => filled($detail['sku'] ?? null) ? (string) $detail['sku'] : null,
                'irecurso' => filled($detail['irecurso'] ?? null) ? (string) $detail['irecurso'] : null,
                'phase' => filled($detail['phase'] ?? null) ? (string) $detail['phase'] : 'desconocida',
                'message' => (string) $detail['message'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{can_run:bool, reason:string, available_at:null, retry_after:int}
     */
    private function availableResponse(): array
    {
        return [
            'can_run' => true,
            'reason' => 'available',
            'available_at' => null,
            'retry_after' => 0,
        ];
    }

    private function retryAfter(?string $availableAt): int
    {
        if (! filled($availableAt)) {
            return self::LOCK_TTL_SECONDS;
        }

        return max(0, now()->diffInSeconds($availableAt, false));
    }
}
