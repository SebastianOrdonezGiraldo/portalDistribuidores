<?php

namespace Tests\Unit;

use App\Modules\Inventory\Jobs\SyncContaPymeStockJob;
use App\Modules\Inventory\Services\ContaPymeSyncState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use RuntimeException;
use Tests\TestCase;

class SyncContaPymeStockJobTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config(['contapyme.enabled' => true]);
    }

    public function test_job_runs_the_stock_command_and_publishes_its_summary(): void
    {
        $state = app(ContaPymeSyncState::class);
        $state->queue();

        Artisan::shouldReceive('call')
            ->once()
            ->with('contapyme:sync-stock')
            ->andReturn(0);
        Artisan::shouldReceive('output')
            ->once()
            ->andReturn("UPDATED CB-08 stock=1565\nContaPyme stock sync: processed=1 updated=1 unchanged=0 missing_contapyme=0 no_sku=0 skipped_variants=0 failed=0\n");

        (new SyncContaPymeStockJob)->handle($state);

        $status = $state->status();

        $this->assertSame('completed', $status['state']);
        $this->assertSame('ContaPyme stock sync: processed=1 updated=1 unchanged=0 missing_contapyme=0 no_sku=0 skipped_variants=0 failed=0', $status['summary']);
        $this->assertFalse($state->isRunning());
        $this->assertFalse($state->availability()['can_run']);
        $this->assertSame('cooldown', $state->availability()['reason']);
    }

    public function test_failed_job_publishes_error_and_keeps_the_cooldown(): void
    {
        $state = app(ContaPymeSyncState::class);
        $state->queue();

        Artisan::shouldReceive('call')
            ->once()
            ->with('contapyme:sync-stock')
            ->andReturn(1);
        Artisan::shouldReceive('output')
            ->once()
            ->andReturn("CONTAPYME_ERROR: la sincronizacion masiva fallo; no se modifico stock local.\n");

        $job = new SyncContaPymeStockJob;

        try {
            $job->handle($state);
            $this->fail('The job must throw when the command fails.');
        } catch (RuntimeException $exception) {
            $job->failed($exception);
        }

        $status = $state->status();

        $this->assertSame('failed', $status['state']);
        $this->assertSame('CONTAPYME_ERROR: la sincronizacion masiva fallo; no se modifico stock local.', $status['summary']);
        $this->assertFalse($state->isRunning());
        $this->assertFalse($state->availability()['can_run']);
        $this->assertSame('cooldown', $state->availability()['reason']);
    }
}
