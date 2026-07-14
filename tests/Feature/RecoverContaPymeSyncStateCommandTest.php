<?php

namespace Tests\Feature;

use App\Modules\Inventory\Models\ContaPymeSyncRun;
use App\Modules\Inventory\Services\ContaPymeSyncState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class RecoverContaPymeSyncStateCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'database',
            'contapyme.enabled' => true,
        ]);
        app('cache')->setDefaultDriver('database');
        Cache::flush();
    }

    public function test_it_fails_only_old_queued_runs_and_clears_the_orphaned_lock(): void
    {
        $oldQueued = $this->createRun('queued', now()->subMinutes(30));
        $recentQueued = $this->createRun('queued', now()->subMinutes(5));
        $running = $this->createRun('running', now()->subMinutes(30), now()->subMinutes(29));

        app(ContaPymeSyncState::class)->queueWithContext('manual', $oldQueued->id);
        $this->assertFalse(app(ContaPymeSyncState::class)->availability()['can_run']);

        $this->artisan('contapyme:recover-sync-state', [
            '--older-than' => 15,
            '--force' => true,
        ])->assertSuccessful();

        $this->assertSame('failed', $oldQueued->fresh()->status);
        $this->assertNotNull($oldQueued->fresh()->finished_at);
        $this->assertSame('Ejecucion cancelada durante la recuperacion operativa de la cola.', $oldQueued->fresh()->summary);
        $this->assertSame('queued', $recentQueued->fresh()->status);
        $this->assertSame('running', $running->fresh()->status);
        $this->assertNull(app(ContaPymeSyncState::class)->status());
        $this->assertTrue(app(ContaPymeSyncState::class)->availability()['can_run']);
    }

    public function test_it_rejects_an_invalid_age(): void
    {
        $this->artisan('contapyme:recover-sync-state', [
            '--older-than' => 0,
            '--force' => true,
        ])->assertFailed();
    }

    private function createRun(string $status, mixed $createdAt, mixed $startedAt = null): ContaPymeSyncRun
    {
        $run = ContaPymeSyncRun::query()->create([
            'id' => (string) Str::uuid(),
            'origin' => 'manual',
            'mode' => 'full',
            'status' => $status,
            'warehouse' => '1',
            'started_at' => $startedAt,
        ]);
        $run->forceFill(['created_at' => $createdAt])->save();

        return $run;
    }
}
