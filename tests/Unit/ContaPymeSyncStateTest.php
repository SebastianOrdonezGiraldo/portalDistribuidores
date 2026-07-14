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

        $this->assertIsString($state->queue());
        $this->assertNull($state->queue());
        $this->assertSame('running', $state->availability()['reason']);
        $this->assertFalse($state->availability()['can_run']);
    }

    public function test_completed_sync_releases_its_lock_immediately(): void
    {
        Carbon::setTestNow('2026-07-10 10:00:00');

        try {
            $state = app(ContaPymeSyncState::class);
            $owner = $state->queue();
            $state->complete('Sincronización completada.');
            $state->release($owner);

            $this->assertTrue($state->availability()['can_run']);
            $this->assertSame('available', $state->availability()['reason']);
            $this->assertNull($state->status()['available_at']);
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_failed_sync_releases_its_lock_immediately(): void
    {
        $state = app(ContaPymeSyncState::class);
        $owner = $state->queue();
        $state->fail('App\\Modules\\Inventory\\Jobs\\SyncContaPymeStockJob has been attempted too many times.');
        $state->release($owner);

        $availability = $state->availability();

        $this->assertTrue($availability['can_run']);
        $this->assertSame('available', $availability['reason']);
        $this->assertNull($state->status()['available_at']);
    }

    public function test_an_expired_owner_cannot_release_a_newer_lock(): void
    {
        Carbon::setTestNow('2026-07-10 10:00:00');

        try {
            $state = app(ContaPymeSyncState::class);
            $expiredOwner = $state->queueWithContext('scheduled', 'run-1');

            Carbon::setTestNow('2026-07-10 10:12:01');
            $currentOwner = $state->queueWithContext('scheduled', 'run-2');

            $this->assertIsString($expiredOwner);
            $this->assertIsString($currentOwner);

            $state->release($expiredOwner);

            $this->assertNull($state->queueWithContext('scheduled', 'run-3'));

            $state->release($currentOwner);
            $this->assertIsString($state->queueWithContext('scheduled', 'run-3'));
        } finally {
            Carbon::setTestNow();
        }
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

    public function test_unmapped_diagnostics_are_preserved_for_the_admin_view(): void
    {
        $state = app(ContaPymeSyncState::class);
        $owner = $state->queue();
        $state->fail('La sincronización terminó con productos pendientes.', [
            'unmapped' => 2,
            'unmapped_details' => [
                ['sku' => 'VAR-001', 'irecurso' => null, 'phase' => 'mapeo_variante', 'message' => 'Falta irecurso.'],
            ],
        ]);
        $state->release($owner);

        $status = $state->status();

        $this->assertSame(2, $status['unmapped_count']);
        $this->assertSame('VAR-001', $status['unmapped_details'][0]['sku']);
    }
}
