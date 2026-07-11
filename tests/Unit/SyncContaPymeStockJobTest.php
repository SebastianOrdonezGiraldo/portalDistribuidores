<?php

namespace Tests\Unit;

use App\Modules\Inventory\Jobs\SyncContaPymeStockJob;
use App\Modules\Inventory\Services\ContaPymeStockSyncRunner;
use App\Modules\Inventory\Services\ContaPymeSyncState;
use App\Modules\Inventory\ValueObjects\ContaPymeStockSyncReport;
use Illuminate\Support\Carbon;
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

    public function test_job_runs_the_stock_runner_and_publishes_its_summary(): void
    {
        $state = app(ContaPymeSyncState::class);
        $state->queue();
        $runId = $state->status()['run_id'];

        $this->mock(ContaPymeStockSyncRunner::class, function ($mock) use ($runId): void {
            $mock->shouldReceive('run')->once()->andReturn(new ContaPymeStockSyncReport(
                runId: $runId,
                origin: 'manual',
                mode: 'full',
                startedAt: Carbon::now()->subSecond(),
                finishedAt: Carbon::now(),
                stats: [
                    'processed' => 1,
                    'updated' => 1,
                    'unchanged' => 0,
                    'missing_contapyme' => 0,
                    'no_sku' => 0,
                    'confirmed_zero' => 0,
                    'unmapped' => 0,
                    'skipped_variants' => 0,
                    'failed' => 0,
                ],
            ));
        });

        (new SyncContaPymeStockJob(runId: $runId))->handle($state, app(ContaPymeStockSyncRunner::class));

        $status = $state->status();

        $this->assertSame('completed', $status['state']);
        $this->assertSame('Sincronización de stock ContaPyme: procesados=1, actualizados=1, sin cambios=0, ausentes en ContaPyme=0, sin SKU=0, variantes omitidas=0, fallidos=0', $status['summary']);
        $this->assertSame(0, $status['error_count']);
        $this->assertFalse($state->isRunning());
        $this->assertFalse($state->availability()['can_run']);
        $this->assertSame('cooldown', $state->availability()['reason']);
    }

    public function test_failed_job_publishes_error_and_keeps_the_cooldown(): void
    {
        $state = app(ContaPymeSyncState::class);
        $state->queue();

        $runId = $state->status()['run_id'];
        $this->mock(ContaPymeStockSyncRunner::class, function ($mock) use ($runId): void {
            $mock->shouldReceive('run')->once()->andReturn(new ContaPymeStockSyncReport(
                runId: $runId,
                origin: 'manual',
                mode: 'full',
                startedAt: Carbon::now()->subSecond(),
                finishedAt: Carbon::now(),
                stats: [
                    'processed' => 2,
                    'updated' => 0,
                    'unchanged' => 0,
                    'missing_contapyme' => 0,
                    'no_sku' => 0,
                    'confirmed_zero' => 0,
                    'unmapped' => 0,
                    'skipped_variants' => 0,
                    'failed' => 2,
                ],
                errorGroups: [
                    ['message' => 'Timeout de ContaPyme', 'count' => 2],
                ],
                errorDetails: [
                    ['sku' => 'ERR-001', 'phase' => 'consulta_masiva', 'message' => 'Timeout de ContaPyme'],
                    ['sku' => 'ERR-002', 'phase' => 'consulta_masiva', 'message' => 'Timeout de ContaPyme'],
                ],
            ));
        });

        $job = new SyncContaPymeStockJob(runId: $runId);

        try {
            $job->handle($state, app(ContaPymeStockSyncRunner::class));
            $this->fail('The job must throw when the command fails.');
        } catch (RuntimeException $exception) {
            $job->failed($exception);
        }

        $status = $state->status();

        $this->assertSame('failed', $status['state']);
        $this->assertSame('Sincronización de stock ContaPyme: procesados=2, actualizados=0, sin cambios=0, ausentes en ContaPyme=0, sin SKU=0, variantes omitidas=0, fallidos=2', $status['summary']);
        $this->assertSame(2, $status['error_count']);
        $this->assertSame([
            ['message' => 'Timeout de ContaPyme', 'count' => 2],
        ], $status['error_groups']);
        $this->assertSame('ERR-001', $status['error_details'][0]['sku']);
        $this->assertFalse($state->isRunning());
        $this->assertFalse($state->availability()['can_run']);
        $this->assertSame('cooldown', $state->availability()['reason']);
    }
}
