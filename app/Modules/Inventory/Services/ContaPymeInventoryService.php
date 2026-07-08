<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Shared\Contracts\InventorySyncInterface;
use App\Modules\Shared\ValueObjects\InventoryItemData;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ContaPymeInventoryService implements InventorySyncInterface
{
    private const CACHE_TOKEN = 'contapyme_keyagente';

    private const CACHE_TOKEN_TTL = 3600;

    private string $baseUrl;

    private string $email;

    private string $password;

    private string $idmaquina;

    private string $iapp;

    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('contapyme.base_url', ''), '/');
        $this->email = (string) config('contapyme.email', '');
        $this->password = (string) config('contapyme.password', '');
        $this->idmaquina = (string) config('contapyme.idmaquina', '');
        $this->iapp = (string) config('contapyme.iapp', '1001');
        $this->timeout = (int) config('contapyme.timeout', 10);
    }

    public function testConnection(): bool
    {
        try {
            $token = $this->authenticate();

            return $token !== '';
        } catch (\Throwable $e) {
            Log::error('ContaPyme.testConnection.failed', [
                'error' => $e->getMessage(),
                'base_url' => $this->baseUrl,
            ]);

            return false;
        }
    }

    public function getProductInfo(string $sku): ?InventoryItemData
    {
        try {
            $response = $this->call('GetInfoElemInv', [$sku]);

            if ($response === null) {
                return null;
            }

            $data = $this->extractData($response);

            if ($data === null) {
                return null;
            }

            return $this->mapToItemData($data);
        } catch (\Throwable $e) {
            Log::error('ContaPyme.getProductInfo.failed', [
                'sku' => $sku,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function listProducts(): Collection
    {
        try {
            $response = $this->call('GetListaElemInv');

            if ($response === null) {
                return new Collection;
            }

            $data = $this->extractData($response);

            if ($data === null) {
                return new Collection;
            }

            $items = is_array($data) && isset($data[0]) ? $data : [$data];

            return collect($items)
                ->map(fn (mixed $item): ?InventoryItemData => $this->mapToItemData($item))
                ->filter();
        } catch (\Throwable $e) {
            Log::error('ContaPyme.listProducts.failed', [
                'error' => $e->getMessage(),
            ]);

            return new Collection;
        }
    }

    public function syncProductStock(string $sku): bool
    {
        $product = Product::query()->where('sku', $sku)->first();

        if ($product === null) {
            Log::warning('ContaPyme.syncProductStock.productNotFound', [
                'sku' => $sku,
            ]);

            return false;
        }

        $info = $this->getProductInfo($sku);

        if ($info === null || $info->stock === null) {
            return false;
        }

        $previousStock = $product->stock;

        if ((float) ($previousStock ?? -1) === $info->stock) {
            return false;
        }

        DB::transaction(function () use ($product, $previousStock, $info): void {
            $product->update(['stock' => $info->stock]);

            StockMovement::record(
                product: $product,
                previousStock: $previousStock !== null ? (float) $previousStock : null,
                newStock: $info->stock,
                source: 'contapyme_sync',
            );
        });

        return true;
    }

    private function authenticate(): string
    {
        return Cache::remember(self::CACHE_TOKEN, self::CACHE_TOKEN_TTL, function (): string {
            $payload = [
                'email' => $this->email,
                'password' => md5($this->password),
            ];

            if ($this->idmaquina !== '') {
                $payload['idmaquina'] = $this->idmaquina;
            }

            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/datasnap/rest/TBasicoGeneral/GetAuth/", $payload);

            if (! $response->successful()) {
                Log::error('ContaPyme.authenticate.httpError', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return '';
            }

            $data = $response->json();

            $token = $data['keyagente'] ?? $data['result']['keyagente'] ?? '';

            if ($token === '') {
                Log::error('ContaPyme.authenticate.tokenEmpty', [
                    'response' => $data,
                ]);
            }

            return (string) $token;
        });
    }

    private function call(string $function, array $params = []): ?array
    {
        $token = $this->authenticate();

        if ($token === '') {
            return null;
        }

        $parameters = [
            ...$params,
            $token,
            $this->iapp,
            (string) random_int(0, 999999),
        ];

        $response = Http::timeout($this->timeout)
            ->post("{$this->baseUrl}/datasnap/rest/TBasicoGeneral/{$function}/", [
                '_parameters' => $parameters,
            ]);

        if (! $response->successful()) {
            Log::error('ContaPyme.call.httpError', [
                'function' => $function,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json();
    }

    private function extractData(?array $response): mixed
    {
        if ($response === null) {
            return null;
        }

        $result = $response['result'] ?? $response['resultado'] ?? null;

        if (is_array($result) && count($result) > 0) {
            $last = end($result);

            if (is_array($last) && (isset($last['resultado']) || isset($last['irecurso']))) {
                return $last['resultado'] ?? $last;
            }

            if (is_array($last)) {
                return $last;
            }

            return $result[0] ?? $result;
        }

        if (is_array($result)) {
            return $result;
        }

        return $response;
    }

    private function mapToItemData(mixed $data): ?InventoryItemData
    {
        if (! is_array($data)) {
            return null;
        }

        $sku = $data['sku']
            ?? $data['codigo']
            ?? $data['Codigo']
            ?? $data['irecurso']
            ?? null;

        if ($sku === null) {
            return null;
        }

        return new InventoryItemData(
            sku: (string) $sku,
            name: $data['nombre'] ?? $data['Nombre'] ?? $data['descripcion'] ?? null,
            stock: $this->normalizeNumeric($data['stock'] ?? $data['Stock'] ?? null),
            price: $this->normalizeNumeric($data['precio'] ?? $data['Precio'] ?? null),
            unit: $data['unidad'] ?? $data['Unidad'] ?? null,
            isActive: $data['activo'] ?? $data['Activo'] ?? null,
            externalId: (string) ($data['irecurso'] ?? $data['IRecurso'] ?? $data['id'] ?? $data['Id'] ?? ''),
            rawData: $data,
        );
    }

    private function normalizeNumeric(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }
}
