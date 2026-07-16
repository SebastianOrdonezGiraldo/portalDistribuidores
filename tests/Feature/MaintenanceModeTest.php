<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    private string $healthDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->healthDirectory = storage_path('framework/testing/maintenance-health');
        File::ensureDirectoryExists($this->healthDirectory);

        config([
            'health.revision_path' => $this->healthDirectory.'/REVISION',
            'health.tree_path' => $this->healthDirectory.'/TREE',
            'health.storage_path' => $this->healthDirectory,
        ]);
    }

    protected function tearDown(): void
    {
        app()->maintenanceMode()->deactivate();
        File::deleteDirectory($this->healthDirectory);

        parent::tearDown();
    }

    public function test_custom_maintenance_view_can_be_prerendered(): void
    {
        $html = view('errors.503', ['retryAfter' => 60])->render();

        $this->assertStringContainsString('Estamos mejorando', $html);
        $this->assertStringContainsString('Import Corporal Medical', $html);
        $this->assertStringContainsString('actualizará automáticamente', $html);
    }

    public function test_public_requests_are_blocked_with_the_custom_maintenance_page(): void
    {
        $this->enableMaintenanceMode();

        $this->get('/login')
            ->assertServiceUnavailable()
            ->assertSee('Estamos mejorando')
            ->assertSee('Actualización en curso');
    }

    public function test_health_endpoint_remains_available_during_maintenance(): void
    {
        File::put(config('health.revision_path'), str_repeat('a', 40));
        File::put(config('health.tree_path'), str_repeat('b', 40));
        DB::shouldReceive('select')->once()->with('select 1')->andReturn([]);
        $this->enableMaintenanceMode();

        $this->getJson('/up')
            ->assertOk()
            ->assertJsonPath('status', 'ok');
    }

    private function enableMaintenanceMode(): void
    {
        app()->maintenanceMode()->activate([
            'except' => ['up'],
            'redirect' => null,
            'retry' => 60,
            'refresh' => 60,
            'secret' => 'testing-maintenance-secret',
            'status' => 503,
            'template' => view('errors.503', ['retryAfter' => 60])->render(),
        ]);
    }
}
