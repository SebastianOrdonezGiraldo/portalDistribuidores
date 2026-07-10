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
            'contapyme.warehouse' => '1',
            'contapyme.timeout' => 5,
        ]);
    }

    public function test_get_product_info_uses_auth_token_and_physical_stock_endpoint(): void
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
        $this->assertSame(39.0, $item->stock);

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
                && $dataJson['iinventario'] === '1'
                && $request->data()['_parameters'][1] === 'TOKEN-123';
        });
    }

    public function test_list_products_normalizes_bulk_stock_for_the_configured_warehouse(): void
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
                        'irecurso' => 'NO-STOCK-IN-ONE',
                        'listabodegas' => [
                            ['iinventario' => '2', 'qinvfisico' => '12'],
                        ],
                    ],
                ],
            ]));

        $items = (new ContaPymeInventoryService)
            ->listProducts()
            ->keyBy('sku');

        $this->assertSame(39.0, $items->get('TENS7000')?->stock);
        $this->assertSame(0.0, $items->get('NO-STOCK-IN-ONE')?->stock);

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

    public function test_sync_product_stock_keeps_last_stock_when_contapyme_fails(): void
    {
        Http::fakeSequence()
            ->push($this->successResponse(['keyagente' => 'TOKEN-123']))
            ->push($this->failedResponse('Error controlado de inventario'));

        $product = Product::factory()->create([
            'sku' => 'TENS7000',
            'stock' => 10,
        ]);

        $service = new ContaPymeInventoryService;

        $this->assertFalse($service->syncProductStock('TENS7000'));

        $product->refresh();

        $this->assertSame(10.0, (float) $product->stock);
        $this->assertSame('failed', $product->stock_sync_status);
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
