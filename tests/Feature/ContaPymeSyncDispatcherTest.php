<?php

namespace Tests\Feature;

use App\Modules\Inventory\Jobs\SyncContaPymeStockJob;
use App\Modules\Inventory\Models\ContaPymeSyncRun;
use App\Modules\Inventory\Services\ContaPymeSyncDispatcher;
use App\Modules\Inventory\Services\ContaPymeSyncState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tests\TestCase;

class ContaPymeSyncDispatcherTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config(['contapyme.enabled' => true]);
    }

    public function test_dispatch_passes_the_lock_owner_to_the_job_and_blocks_overlap(): void
    {
        Bus::fake();
        $dispatcher = app(ContaPymeSyncDispatcher::class);

        $this->assertTrue($dispatcher->dispatchIfAvailable('scheduled'));
        $this->assertFalse($dispatcher->dispatchIfAvailable('scheduled'));

        Bus::assertDispatchedTimes(SyncContaPymeStockJob::class, 1);
        Bus::assertDispatched(SyncContaPymeStockJob::class, function (SyncContaPymeStockJob $job): bool {
            return $job->origin === 'scheduled'
                && filled($job->runId)
                && filled($job->lockOwner);
        });
        $this->assertDatabaseCount('contapyme_sync_runs', 1);
    }

    public function test_dispatch_failure_releases_the_owned_lock(): void
    {
        Bus::shouldReceive('dispatch')
            ->once()
            ->andThrow(new RuntimeException('Queue unavailable'));

        $this->assertFalse(app(ContaPymeSyncDispatcher::class)->dispatchIfAvailable('scheduled'));
        $this->assertTrue(app(ContaPymeSyncState::class)->availability()['can_run']);
        $this->assertSame('failed', ContaPymeSyncRun::query()->sole()->status);
    }
}
