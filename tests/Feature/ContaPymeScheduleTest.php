<?php

namespace Tests\Feature;

use App\Modules\Inventory\Jobs\SyncContaPymeStockJob;
use App\Modules\Inventory\Models\ContaPymeSyncRun;
use App\Modules\Inventory\Services\ContaPymeSyncState;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ContaPymeScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduler_runs_on_five_minute_boundaries_without_overlapping(): void
    {
        Cache::flush();
        Queue::fake();
        config(['contapyme.enabled' => true]);
        Carbon::setTestNow('2026-07-14 10:00:00');

        try {
            $this->artisan('schedule:run')->assertSuccessful();
            Queue::assertPushedTimes(SyncContaPymeStockJob::class, 1);

            $firstJob = Queue::pushed(SyncContaPymeStockJob::class)->first();
            $this->assertInstanceOf(SyncContaPymeStockJob::class, $firstJob);

            Carbon::setTestNow('2026-07-14 10:05:00');
            $this->artisan('schedule:run')->assertSuccessful();
            Queue::assertPushedTimes(SyncContaPymeStockJob::class, 1);

            $state = app(ContaPymeSyncState::class);
            $state->complete('Sincronización completada.');
            $state->release($firstJob->lockOwner);

            Carbon::setTestNow('2026-07-14 10:10:00');
            $this->artisan('schedule:run')->assertSuccessful();
            Queue::assertPushedTimes(SyncContaPymeStockJob::class, 2);

            $this->assertDatabaseCount('contapyme_sync_runs', 2);
            $this->assertSame(
                ['scheduled', 'scheduled'],
                ContaPymeSyncRun::query()
                    ->orderBy('created_at')
                    ->pluck('origin')
                    ->all(),
            );
        } finally {
            Carbon::setTestNow();
        }
    }
}
