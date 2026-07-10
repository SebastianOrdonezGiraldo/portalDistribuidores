<?php

namespace App\Modules\Inventory\Services;

use Illuminate\Support\Facades\Cache;

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
        if (! Cache::add(self::LOCK_KEY, true, self::LOCK_TTL_SECONDS)) {
            return false;
        }

        $this->store(
            state: 'queued',
            message: 'Sincronización ContaPyme encolada.',
            queuedAt: now()->toIso8601String(),
        );

        return true;
    }

    public function isRunning(): bool
    {
        return Cache::has(self::LOCK_KEY);
    }

    /**
     * @return array{state:string, message:string, summary:string|null, queued_at:string|null, started_at:string|null, completed_at:string|null}|null
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
            'queued_at' => filled($status['queued_at'] ?? null) ? (string) $status['queued_at'] : null,
            'started_at' => filled($status['started_at'] ?? null) ? (string) $status['started_at'] : null,
            'completed_at' => filled($status['completed_at'] ?? null) ? (string) $status['completed_at'] : null,
        ];
    }

    public function markRunning(): void
    {
        $status = $this->status();

        $this->store(
            state: 'running',
            message: 'Sincronización ContaPyme en curso.',
            queuedAt: $status['queued_at'] ?? now()->toIso8601String(),
            startedAt: now()->toIso8601String(),
        );
    }

    public function complete(string $summary): void
    {
        $status = $this->status();

        $this->store(
            state: 'completed',
            message: 'Sincronización ContaPyme completada.',
            summary: $summary,
            queuedAt: $status['queued_at'] ?? null,
            startedAt: $status['started_at'] ?? null,
            completedAt: now()->toIso8601String(),
        );

        $this->release();
    }

    public function fail(string $message): void
    {
        $status = $this->status();

        $this->store(
            state: 'failed',
            message: 'La sincronización ContaPyme falló.',
            summary: $message,
            queuedAt: $status['queued_at'] ?? null,
            startedAt: $status['started_at'] ?? null,
            completedAt: now()->toIso8601String(),
        );

        $this->release();
    }

    public function block(string $message): void
    {
        $this->store(
            state: 'blocked',
            message: $message,
            completedAt: now()->toIso8601String(),
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
        ?string $summary = null,
        ?string $queuedAt = null,
        ?string $startedAt = null,
        ?string $completedAt = null,
    ): void {
        Cache::put(self::STATUS_KEY, [
            'state' => $state,
            'message' => $message,
            'summary' => $summary,
            'queued_at' => $queuedAt,
            'started_at' => $startedAt,
            'completed_at' => $completedAt,
        ], self::STATUS_TTL_SECONDS);
    }
}
