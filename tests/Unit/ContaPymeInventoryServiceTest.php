<?php

namespace Tests\Unit;

use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Services\ContaPymeInventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ContaPymeInventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        config([
            'contapyme.base_url' => 'http://contapyme.test:9000',
            'contapyme.email' => 'stock@example.com',
            'contapyme.password' => '',
            'contapyme.password_hash' => '00000000000000000000000000000000',
            'contapyme.idmaquina' => '1',
            'contapyme.iapp' => '1003',
            'contapyme.timeout' => 5,
        ]);
    }

    public function test_connection_exposes_http_statuses_when_contapyme_is_unreachable(): void
    {
        Http::fakeSequence()
            ->push([], 503)
            ->push([], 504);

        $service = new ContaPymeInventoryService;

        $this->assertFalse($service->testConnection());
        $this->assertStringContainsString('HTTP POST 503, GET 504', (string) $service->lastError());
    }

    public function test_connection_reports_invalid_json(): void
    {
        Http::fakeSequence()->push('not-json', 200);

        $service = new ContaPymeInventoryService;

        $this->assertFalse($service->testConnection());
        $this->assertStringContainsString('respuesta JSON invalida', (string) $service->lastError());
    }

    public function test_diagnose_checks_the_agent_without_exposing_the_token(): void
    {
        Http::fakeSequence()
            ->push($this->successResponse([
                'keyagente' => 'TOKEN-123',
                'version' => 'V4',
                'release' => '8',
                'update' => '14',
            ]))
            ->push($this->successResponse(['estado' => 'Conectado']));

        $diagnostics = (new ContaPymeInventoryService)->diagnose();

        $this->assertTrue($diagnostics['authenticated']);
        $this->assertSame('Conectado', $diagnostics['agent_status']);
        $this->assertSame(['version' => 'V4', 'release' => '8', 'update' => '14'], $diagnostics['auth_metadata']);
        $this->assertArrayNotHasKey('keyagente', $diagnostics['auth_metadata']);
        $this->assertNull($diagnostics['error']);
    }

    public function test_diagnose_returns_a_clear_agent_error(): void
    {
        Http::fakeSequence()
            ->push($this->successResponse(['keyagente' => 'TOKEN-123']))
            ->push($this->failedResponse('Agente detenido'));

        $diagnostics = (new ContaPymeInventoryService)->diagnose();

        $this->assertTrue($diagnostics['authenticated']);
        $this->assertNull($diagnostics['agent_status']);
        $this->assertSame('Agente detenido', $diagnostics['error']);
    }

    public function test_diagnostic_error_redacts_credentials(): void
    {
        $service = new ContaPymeInventoryService;

        $message = $service->diagnosticError(
            'email=stock@example.com password_hash=00000000000000000000000000000000',
        );

        $this->assertStringNotContainsString('stock@example.com', $message);
        $this->assertStringNotContainsString('00000000000000000000000000000000', $message);
        $this->assertStringContainsString('[redacted]', $message);
    }

    public function test_get_product_info_sums_stock_across_all_warehouses(): void
    {
        Http::fakeSequence()
            ->push($this->successResponse(['keyagente' => 'TOKEN-123']))
            ->push($this->successResponse([
                ['iinventario' => '1', 'ninventario' => 'Bodega 1', 'qproducto' => '39'],
                ['iinventario' => '2', 'ninventario' => 'Bodega 2', 'qproducto' => '99'],
            ]));

        $service = new ContaPymeInventoryService;

        $item = $service->getProductInfo('TENS7000');

        $this->assertNotNull($item);
        $this->assertSame('TENS7000', $item->sku);
        $this->assertSame(138.0, $item->stock);

        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), '/TBasicoGeneral/GetAuth')) {
                return false;
            }

            $dataJson = json_decode((string) $request->data()['_parameters'][0], true);

            return $dataJson['email'] === 'stock@example.com'
                && $dataJson['password'] === '00000000000000000000000000000000'
                && $dataJson['idmaquina'] === '1'
                && $request->data()['_parameters'][2] === '1003';
        });

        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), '/TInventarios/GetSaldoFisicoProductoEnBodegas')) {
                return false;
            }

            $dataJson = json_decode((string) $request->data()['_parameters'][0], true);

            return $dataJson['irecurso'] === 'TENS7000'
                && ! array_key_exists('iinventario', $dataJson)
                && $request->data()['_parameters'][1] === 'TOKEN-123';
        });
    }

    public function test_get_product_info_treats_an_empty_balance_list_as_zero(): void
    {
        Http::fakeSequence()
            ->push($this->successResponse(['keyagente' => 'TOKEN-123']))
            ->push($this->successResponse([]));

        $item = (new ContaPymeInventoryService)->getProductInfo('ZERO-SKU');

        $this->assertNotNull($item);
        $this->assertSame(0.0, $item->stock);
    }

    public function test_list_products_sums_bulk_stock_across_all_warehouses(): void
    {
        Http::fakeSequence()
            ->push($this->successResponse(['keyagente' => 'TOKEN-123']))
            ->push($this->successResponse([
                'listaproductos' => [
                    [
                        'irecurso' => 'TENS7000',
                        'listabodegas' => [
                            ['iinventario' => '1', 'qinvfisico' => '39'],
                            ['iinventario' => '2', 'qinvfisico' => '99'],
                        ],
                    ],
                    [
                        'irecurso' => 'ONLY-IN-TWO',
                        'listabodegas' => [
                            ['iinventario' => '2', 'qinvfisico' => '12'],
                        ],
                    ],
                ],
            ]));

        $items = (new ContaPymeInventoryService)
            ->listProducts()
            ->keyBy('sku');

        $this->assertSame(138.0, $items->get('TENS7000')?->stock);
        $this->assertSame(12.0, $items->get('ONLY-IN-TWO')?->stock);

        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), '/TCatElemInv/GetSaldosProductosEnBodegas')) {
                return false;
            }

            $dataJson = json_decode((string) $request->data()['_parameters'][0], true);

            return $dataJson['iinventario'] === 'T'
                && $dataJson['bunidadrecurso'] === 'T'
                && $dataJson['binventariofisico'] === 'T'
                && $dataJson['bnombreinventario'] === 'T'
                && $request->data()['_parameters'][1] === 'TOKEN-123';
        });
    }

    public function test_product_exists_uses_get_existe_elem_inv(): void
    {
        Http::fakeSequence()
            ->push($this->successResponse(['keyagente' => 'TOKEN-123']))
            ->push($this->successResponse(['existe' => 'true']));

        $service = new ContaPymeInventoryService;

        $this->assertTrue($service->productExists('TENS7000'));

        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), '/TCatElemInv/GetExisteElemInv')) {
                return false;
            }

            $dataJson = json_decode((string) $request->data()['_parameters'][0], true);

            return $dataJson['irecurso'] === 'TENS7000'
                && $request->data()['_parameters'][1] === 'TOKEN-123';
        });
    }

    public function test_product_exists_returns_false_for_a_missing_inventory_item(): void
    {
        Http::fakeSequence()
            ->push($this->successResponse(['keyagente' => 'TOKEN-123']))
            ->push($this->successResponse(['existe' => 'false']));

        $this->assertFalse((new ContaPymeInventoryService)->productExists('MISSING-SKU'));
    }

    public function test_product_exists_returns_null_for_an_invalid_response(): void
    {
        Http::fakeSequence()
            ->push($this->successResponse(['keyagente' => 'TOKEN-123']))
            ->push($this->successResponse(['existe' => 'unknown']));

        $service = new ContaPymeInventoryService;

        $this->assertNull($service->productExists('TENS7000'));
        $this->assertSame('ContaPyme no devolvio una confirmacion de existencia valida.', $service->lastError());
    }

    public function test_product_exists_refreshes_the_token_after_a_not_logged_in_response(): void
    {
        Http::fakeSequence()
            ->push($this->successResponse(['keyagente' => 'TOKEN-OLD']))
            ->push($this->failedResponse('Usuario no logueado'))
            ->push($this->successResponse(['keyagente' => 'TOKEN-NEW']))
            ->push($this->successResponse(['existe' => 'true']));

        $service = new ContaPymeInventoryService;

        $this->assertTrue($service->productExists('TENS7000'));
        $this->assertNull($service->lastError());
        Http::assertSentCount(4);
        Http::assertSent(function ($request): bool {
            return str_contains($request->url(), '/TCatElemInv/GetExisteElemInv')
                && $request->data()['_parameters'][1] === 'TOKEN-NEW';
        });
    }

    public function test_list_products_refreshes_the_token_after_a_not_logged_in_response(): void
    {
        Http::fakeSequence()
            ->push($this->successResponse(['keyagente' => 'TOKEN-OLD']))
            ->push($this->failedResponse('Usuario no logueado'))
            ->push($this->successResponse(['keyagente' => 'TOKEN-NEW']))
            ->push($this->successResponse([
                'listaproductos' => [[
                    'irecurso' => 'TENS7000',
                    'listabodegas' => [['iinventario' => '1', 'qinvfisico' => '39']],
                ]],
            ]));

        $service = new ContaPymeInventoryService;
        $items = $service->listProducts();

        $this->assertCount(1, $items);
        $this->assertSame(39.0, $items->first()?->stock);
        $this->assertNull($service->lastError());
        Http::assertSentCount(4);
        Http::assertSent(function ($request): bool {
            if (! str_contains($request->url(), '/TCatElemInv/GetSaldosProductosEnBodegas')) {
                return false;
            }

            return $request->data()['_parameters'][1] === 'TOKEN-NEW';
        });
    }

    public function test_sync_product_stock_updates_local_stock_and_records_movement(): void
    {
        Http::fakeSequence()
            ->push($this->successResponse(['keyagente' => 'TOKEN-123']))
            ->push($this->successResponse(['existe' => 'true']))
            ->push($this->successResponse([
                ['iinventario' => '1', 'ninventario' => 'Bodega 1', 'qproducto' => '39'],
            ]));

        $product = Product::factory()->create([
            'sku' => 'TENS7000',
            'stock' => 10,
        ]);

        $service = new ContaPymeInventoryService;

        $this->assertTrue($service->syncProductStock('TENS7000'));

        $product->refresh();

        $this->assertSame(39.0, (float) $product->stock);
        $this->assertSame('synced', $product->stock_sync_status);
        $this->assertNotNull($product->stock_synced_at);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'source' => 'contapyme_sync',
            'previous_stock' => 10,
            'new_stock' => 39,
            'delta' => 29,
        ]);
    }

    public function test_sync_product_stock_writes_zero_when_balance_list_is_empty(): void
    {
        Http::fakeSequence()
            ->push($this->successResponse(['keyagente' => 'TOKEN-123']))
            ->push($this->successResponse(['existe' => 'true']))
            ->push($this->successResponse([]));

        $product = Product::factory()->create([
            'sku' => 'ZERO-SKU',
            'stock' => 10,
        ]);

        $this->assertTrue((new ContaPymeInventoryService)->syncProductStock('ZERO-SKU'));

        $product->refresh();
        $this->assertSame(0.0, (float) $product->stock);
        $this->assertSame('synced', $product->stock_sync_status);
    }

    public function test_sync_product_stock_preserves_local_stock_when_the_sku_does_not_exist(): void
    {
        Http::fakeSequence()
            ->push($this->successResponse(['keyagente' => 'TOKEN-123']))
            ->push($this->successResponse(['existe' => 'false']));

        $product = Product::factory()->create([
            'sku' => 'MISSING-SKU',
            'stock' => 10,
            'stock_sync_status' => 'synced',
            'stock_synced_at' => now(),
        ]);

        $this->assertFalse((new ContaPymeInventoryService)->syncProductStock('MISSING-SKU'));

        $product->refresh();

        $this->assertSame(10.0, (float) $product->stock);
        $this->assertSame('missing_contapyme', $product->stock_sync_status);
        $this->assertNull($product->stock_synced_at);
        $this->assertDatabaseMissing('stock_movements', [
            'product_id' => $product->id,
            'source' => 'contapyme_sync',
        ]);
    }

    public function test_sync_product_stock_keeps_existing_state_when_contapyme_fails(): void
    {
        Http::fakeSequence()
            ->push($this->successResponse(['keyagente' => 'TOKEN-123']))
            ->push($this->successResponse(['existe' => 'true']))
            ->push($this->failedResponse('Error controlado de inventario'));

        $product = Product::factory()->create([
            'sku' => 'TENS7000',
            'stock' => 10,
        ]);

        $service = new ContaPymeInventoryService;

        $this->assertFalse($service->syncProductStock('TENS7000'));
        $this->assertSame('Error controlado de inventario', $service->lastError());

        $product->refresh();

        $this->assertSame(10.0, (float) $product->stock);
        $this->assertNull($product->stock_sync_status);
        $this->assertNull($product->stock_synced_at);
        $this->assertDatabaseMissing('stock_movements', [
            'product_id' => $product->id,
            'source' => 'contapyme_sync',
        ]);
    }

    private function successResponse(mixed $datos): array
    {
        return [
            'result' => [[
                'encabezado' => [
                    'resultado' => 'true',
                    'imensaje' => '',
                    'mensaje' => '',
                    'tiempo' => '49',
                ],
                'respuesta' => [
                    'datos' => $datos,
                ],
            ]],
        ];
    }

    private function failedResponse(string $message): array
    {
        return [
            'result' => [[
                'encabezado' => [
                    'resultado' => 'false',
                    'imensaje' => '1',
                    'mensaje' => $message,
                    'tiempo' => '49',
                ],
                'respuesta' => [
                    'datos' => '',
                ],
            ]],
        ];
    }
}
