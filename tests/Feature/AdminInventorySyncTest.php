<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Inventory\Services\InvenTreeSyncService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AdminInventorySyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_inventory_panel_shows_prices_copy_and_real_stats(): void
    {
        $admin = User::factory()->admin()->create();

        $syncService = Mockery::mock(InvenTreeSyncService::class);
        $syncService->shouldReceive('testConnection')
            ->once()
            ->andReturn([
                'success' => true,
                'message' => 'Conexión exitosa a InvenTree API',
                'parts_count' => 1,
                'total_parts' => 12,
                'server' => 'https://inventree.example.com',
            ]);

        $this->app->instance(InvenTreeSyncService::class, $syncService);

        $response = $this->actingAs($admin)
            ->withSession([
                'syncResults' => [
                    'prices' => [
                        'total' => 12,
                        'matched' => 8,
                        'updated_price' => 3,
                        'skipped' => 5,
                        'skipped_variants' => 2,
                        'unmatched' => 2,
                        'not_found' => 2,
                        'errors' => 1,
                    ],
                    'stock' => [
                        'total' => 12,
                        'matched' => 7,
                        'updated' => 4,
                        'skipped_variants' => 2,
                        'unmatched' => 3,
                        'errors' => 0,
                    ],
                    'duration_ms' => 120,
                    'started_at' => '2026-07-03T20:00:00+00:00',
                ],
            ])
            ->get('/admin/inventory');

        $response->assertOk();
        $response->assertSee('Sincronizar precios');
        $response->assertSee('Precios actualizados');
        $response->assertSee('Variantes omitidas');
        $response->assertSee('Las variantes no se sincronizan en esta versión.');
        $response->assertDontSee('Creados');
        $response->assertDontSee('Sincronizar productos');
    }

    public function test_admin_can_run_prices_sync_type(): void
    {
        $this->withoutMiddleware(ValidateCsrfToken::class);

        $admin = User::factory()->admin()->create();

        $syncService = Mockery::mock(InvenTreeSyncService::class);
        $syncService->shouldReceive('syncAll')
            ->once()
            ->with('prices')
            ->andReturn([
                'prices' => [
                    'total' => 1,
                    'matched' => 1,
                    'updated_price' => 1,
                    'skipped' => 0,
                    'skipped_variants' => 0,
                    'unmatched' => 0,
                    'not_found' => 0,
                    'errors' => 0,
                ],
                'stock' => [
                    'total' => 0,
                    'matched' => 0,
                    'updated' => 0,
                    'skipped_variants' => 0,
                    'unmatched' => 0,
                    'errors' => 0,
                ],
                'duration_ms' => 25,
            ]);

        $this->app->instance(InvenTreeSyncService::class, $syncService);

        $response = $this->actingAs($admin)
            ->post('/admin/inventory/sync', ['type' => 'prices']);

        $response->assertRedirect('/admin/inventory');
        $response->assertSessionHas('syncResults');
    }
}
