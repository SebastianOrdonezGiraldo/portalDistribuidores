<?php

namespace Tests\Unit;

use App\Modules\Inventory\Services\ContaPymeInventoryService;
use App\Modules\Inventory\Services\ContaPymePriceService;
use App\Modules\Inventory\ValueObjects\ContaPymePriceLookup;
use Tests\TestCase;

class ContaPymePriceServiceTest extends TestCase
{
    public function test_get_precio_calculado_uses_expected_contract_and_mprecio(): void
    {
        $client = new class extends ContaPymeInventoryService
        {
            public array $call = [];

            public function callReadOnly(string $serverClass, string $function, array $dataJson, bool $withResponse = false): mixed
            {
                $this->call = compact('serverClass', 'function', 'dataJson', 'withResponse');

                return ['mprecio' => '12345.60'];
            }
        };

        $result = (new ContaPymePriceService($client))->calculatedPrice('SKU-1', '1', '3');

        $this->assertSame('ok', $result->status);
        $this->assertSame('12345.60', $result->price);
        $this->assertSame('TCatElemInv', $client->call['serverClass']);
        $this->assertSame('GetPrecioCalculado', $client->call['function']);
        $this->assertSame(['irecurso' => 'SKU-1', 'imetodo' => '1', 'ilista' => '3'], $client->call['dataJson']);
    }

    public function test_mprecio_missing_is_not_treated_as_zero(): void
    {
        $client = new class extends ContaPymeInventoryService
        {
            public function callReadOnly(string $serverClass, string $function, array $dataJson, bool $withResponse = false): mixed
            {
                return [];
            }
        };

        $result = (new ContaPymePriceService($client))->calculatedPrice('SKU-1', '1', '3');

        $this->assertContains($result->status, ['error', 'missing_contapyme']);
        $this->assertNull($result->price);
    }
}
