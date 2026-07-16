<?php

namespace Tests\Feature;

use App\Modules\Inventory\Jobs\SyncContaPymeStockJob;
use App\Modules\Inventory\Services\ContaPymeStockSyncRunner;
use App\Modules\Inventory\Services\ContaPymeSyncDispatcher;
use App\Modules\Inventory\Services\ContaPymeSyncState;
use App\Modules\Inventory\ValueObjects\ContaPymeStockSyncReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ContaPymeDatabaseQueueTest extends TestCase
{
    use RefreshDatabase;

    private ?string $cacheConnection = null;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'cache.default' => 'database',
            'queue.default' => 'database',
            'queue.connections.database.retry_after' => 660,
            'contapyme.enabled' => true,
        ]);
        $this->configureNonTransactionalPostgresCache();
        app('cache')->setDefaultDriver('database');
        app('queue')->setDefaultDriver('database');
        Cache::flush();
    }

    protected function tearDown(): void
    {
        if ($this->cacheConnection !== null) {
            Cache::flush();
            DB::disconnect($this->cacheConnection);
        }

        parent::tearDown();
    }

    public function test_database_queue_preserves_the_lock_owner_until_the_job_releases_it(): void
    {
        $this->assertTrue(app(ContaPymeSyncDispatcher::class)->dispatchIfAvailable('scheduled'));
        $this->assertDatabaseCount('jobs', 1);

        $payload = json_decode((string) DB::table('jobs')->value('payload'), true, flags: JSON_THROW_ON_ERROR);
        $job = unserialize($payload['data']['command']);

        $this->assertInstanceOf(SyncContaPymeStockJob::class, $job);
        $this->assertNotNull($job->lockOwner);
        $this->assertFalse(app(ContaPymeSyncState::class)->availability()['can_run']);

        $this->mock(ContaPymeStockSyncRunner::class, function ($mock) use ($job): void {
            $mock->shouldReceive('run')->once()->andReturn(new ContaPymeStockSyncReport(
                runId: $job->runId,
                origin: 'scheduled',
                mode: 'full',
                startedAt: Carbon::now()->subSecond(),
                finishedAt: Carbon::now(),
                stats: [
                    'processed' => 1,
                    'updated' => 0,
                    'unchanged' => 1,
                    'missing_contapyme' => 0,
                    'no_sku' => 0,
                    'confirmed_zero' => 0,
                    'unmapped' => 0,
                    'skipped_variants' => 0,
                    'failed' => 0,
                ],
            ));
        });

        $job->handle(
            app(ContaPymeSyncState::class),
            app(ContaPymeStockSyncRunner::class),
        );

        $this->assertSame('completed', app(ContaPymeSyncState::class)->status()['state']);
        $this->assertTrue(app(ContaPymeSyncState::class)->availability()['can_run']);
    }

    private function configureNonTransactionalPostgresCache(): void
    {
        $defaultConnection = (string) config('database.default');

        if ((string) config("database.connections.{$defaultConnection}.driver") !== 'pgsql') {
            return;
        }

        $this->cacheConnection = 'pgsql_test_cache';
        config([
            "database.connections.{$this->cacheConnection}" => config("database.connections.{$defaultConnection}"),
            'cache.stores.database.connection' => $this->cacheConnection,
            'cache.stores.database.lock_connection' => $this->cacheConnection,
        ]);
        DB::purge($this->cacheConnection);
        Cache::forgetDriver('database');
    }
}
