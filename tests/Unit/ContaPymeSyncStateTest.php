<?php

namespace Tests\Unit;

use App\Modules\Inventory\Services\ContaPymeSyncState;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ContaPymeSyncStateTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config(['contapyme.enabled' => true]);
    }

    public function test_queue_is_atomic_and_reports_an_active_run(): void
    {
        $state = app(ContaPymeSyncState::class);

        $this->assertTrue($state->queue());
        $this->assertFalse($state->queue());
        $this->assertSame('running', $state->availability()['reason']);
        $this->assertFalse($state->availability()['can_run']);
    }

    public function test_completed_sync_remains_blocked_until_the_twelve_minute_window_expires(): void
    {
        Carbon::setTestNow('2026-07-10 10:00:00');

        try {
            $state = app(ContaPymeSyncState::class);
            $state->queue();
            $state->complete('Sincronización completada.');

            $this->assertSame('cooldown', $state->availability()['reason']);
            $this->assertSame(720, $state->availability()['retry_after']);

            Carbon::setTestNow('2026-07-10 10:11:59');
            $this->assertSame(1, $state->availability()['retry_after']);
            $this->assertFalse($state->availability()['can_run']);

            Carbon::setTestNow('2026-07-10 10:12:01');
            $this->assertTrue($state->availability()['can_run']);
            $this->assertSame('available', $state->availability()['reason']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_failed_sync_also_keeps_the_cooldown(): void
    {
        $state = app(ContaPymeSyncState::class);
        $state->queue();
        $state->fail('App\\Modules\\Inventory\\Jobs\\SyncContaPymeStockJob has been attempted too many times.');

        $availability = $state->availability();

        $this->assertFalse($availability['can_run']);
        $this->assertSame('cooldown', $availability['reason']);
        $this->assertGreaterThan(0, $availability['retry_after']);
    }

    public function test_disabled_contapyme_is_not_available(): void
    {
        config(['contapyme.enabled' => false]);

        $availability = app(ContaPymeSyncState::class)->availability();

        $this->assertFalse($availability['can_run']);
        $this->assertSame('disabled', $availability['reason']);
        $this->assertNull($availability['available_at']);
        $this->assertSame(0, $availability['retry_after']);
    }
}
