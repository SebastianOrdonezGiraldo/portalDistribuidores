<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class HealthEndpointTest extends TestCase
{
    private string $healthDirectory;

    protected function setUp(): void
    {
        parent::setUp();

        $this->healthDirectory = storage_path('framework/testing/health');
        File::ensureDirectoryExists($this->healthDirectory);

        config([
            'health.revision_path' => $this->healthDirectory.'/REVISION',
            'health.tree_path' => $this->healthDirectory.'/TREE',
            'health.storage_path' => $this->healthDirectory,
        ]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->healthDirectory);

        parent::tearDown();
    }

    public function test_internal_health_endpoint_reports_release_readiness(): void
    {
        File::put(config('health.revision_path'), str_repeat('a', 40));
        File::put(config('health.tree_path'), str_repeat('b', 40));

        $this->getJson('/up')
            ->assertOk()
            ->assertHeader('Cache-Control', 'no-store, private')
            ->assertExactJson([
                'status' => 'ok',
                'revision' => str_repeat('a', 40),
                'tree' => str_repeat('b', 40),
            ]);
    }

    public function test_internal_health_endpoint_fails_closed_without_release_metadata(): void
    {
        $this->getJson('/up')
            ->assertServiceUnavailable()
            ->assertExactJson([
                'status' => 'unavailable',
            ]);
    }
}
