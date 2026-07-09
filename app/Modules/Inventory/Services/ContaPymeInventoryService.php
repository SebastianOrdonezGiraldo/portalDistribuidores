<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Shared\Contracts\InventorySyncInterface;
use App\Modules\Shared\ValueObjects\InventoryItemData;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * ContaPyme DataSnap adapter for stock-only synchronization.
 *
 * The portal owns prices and catalog data. ContaPyme only feeds the local stock
 * field so cart and quotation validation can stay fast and isolated from the
 * external API.
 */
class ContaPymeInventoryService implements InventorySyncInterface
{
    private const CACHE_TOKEN = 'contapyme_keyagente';

    private const CACHE_TOKEN_TTL = 3600;

    private string $baseUrl;

    private string $email;

    private string $password;

    private string $passwordHash;

    private string $idmaquina;

    private string $iapp;

    private string $warehouse;

    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('contapyme.base_url', ''), '/');
        $this->email = (string) config('contapyme.email', '');
        $this->password = (string) config('contapyme.password', '');
        $this->passwordHash = strtolower((string) config('contapyme.password_hash', ''));
        $this->idmaquina = (string) config('contapyme.idmaquina', '');
        $this->iapp = (string) config('contapyme.iapp', '1003');
        $this->warehouse = (string) config('contapyme.warehouse', '1');
        $this->timeout = (int) config('contapyme.timeout', 10);
    }

    /**
     * Verify credentials and endpoint reachability without mutating inventory.
     */
    public function testConnection(): bool
    {
        try {
            return $this->authenticate(forceRefresh: true) !== '';
        } catch (\Throwable $e) {
            Log::error('contapyme.test_connection_failed', [
                'error' => $e->getMessage(),
                'base_url_configured' => $this->baseUrl !== '',
            ]);

            return false;
        }
    }

    /**
     * Fetch the physical stock for one portal SKU in the configured warehouse.
     */
    public function getProductInfo(string $sku): ?InventoryItemData
    {
        $sku = trim($sku);

        if ($sku === '') {
            return null;
        }

        try {
            $data = $this->callWithRetry(
                serverClass: 'TInventarios',
                function: 'GetSaldoFisicoProductoEnBodegas',
                dataJson: [
                    'irecurso' => $sku,
                    'iinventario' => $this->warehouse,
                ],
            );

            if ($data === null) {
                return null;
            }

            return new InventoryItemData(
                sku: $sku,
                stock: $this->stockFromWarehouseRows($data),
                externalId: $sku,
                rawData: is_array($data) ? $data : ['datos' => $data],
            );
        } catch (\Throwable $e) {
            Log::error('contapyme.get_product_stock_failed', [
                'sku' => $sku,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Fetch stock for all products exposed by ContaPyme.
     *
     * @return Collection<int, InventoryItemData>
     */
    public function listProducts(): Collection
    {
        try {
            $data = $this->callWithRetry(
                serverClass: 'TCatElemInv',
                function: 'GetSaldosProductosEnBodegas',
                dataJson: [
                    'binventariofisico' => 'T',
                ],
            );
        } catch (\Throwable $e) {
            Log::error('contapyme.list_products_failed', [
                'error' => $e->getMessage(),
            ]);

            return new Collection;
        }

        if (! is_array($data)) {
            return new Collection;
        }

        $products = data_get($data, 'listaproductos', []);

        if (! is_array($products)) {
            return new Collection;
        }

        return collect($products)
            ->map(function (mixed $row): ?InventoryItemData {
                if (! is_array($row)) {
                    return null;
                }

                $sku = trim((string) ($row['irecurso'] ?? ''));

                if ($sku === '') {
                    return null;
                }

                return new InventoryItemData(
                    sku: $sku,
                    stock: $this->stockFromBulkWarehouseRows((array) ($row['listabodegas'] ?? [])),
                    externalId: $sku,
                    rawData: $row,
                );
            })
            ->filter()
            ->values();
    }

    /**
     * Pull stock for one SKU and persist it to the matching portal product.
     *
     * @return bool true only when stock changed
     */
    public function syncProductStock(string $sku): bool
    {
        $product = Product::query()->where('sku', trim($sku))->first();

        if ($product === null) {
            Log::warning('contapyme.sync_product_stock_missing_local_product', [
                'sku' => $sku,
            ]);

            return false;
        }

        return $this->syncProduct($product)['changed'];
    }

    /**
     * @return array{status:string, changed:bool, stock:float|null}
     */
    public function syncProduct(Product $product): array
    {
        $previousStock = is_numeric($product->stock) ? (float) $product->stock : null;
        $info = $this->getProductInfo((string) $product->sku);

        if ($info === null || $info->stock === null) {
            $product->forceFill([
                'stock_sync_status' => 'failed',
            ])->save();

            return [
                'status' => 'failed',
                'changed' => false,
                'stock' => $previousStock,
            ];
        }

        $newStock = round(max(0, $info->stock), 2);
        $changed = $previousStock === null || abs($previousStock - $newStock) > 0.00001;

        DB::transaction(function () use ($product, $previousStock, $newStock, $changed): void {
            $lockedProduct = Product::query()
                ->whereKey($product->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedProduct->forceFill([
                'stock' => $newStock,
                'stock_synced_at' => now(),
                'stock_sync_status' => 'synced',
            ])->save();

            if ($changed) {
                StockMovement::record(
                    product: $lockedProduct,
                    previousStock: $previousStock,
                    newStock: $newStock,
                    source: 'contapyme_sync',
                );
            }
        });

        return [
            'status' => $changed ? 'updated' : 'unchanged',
            'changed' => $changed,
            'stock' => $newStock,
        ];
    }

    /**
     * Authenticate against ContaPyme and cache keyagente for subsequent calls.
     */
    private function authenticate(bool $forceRefresh = false): string
    {
        if ($forceRefresh) {
            Cache::forget(self::CACHE_TOKEN);
        }

        return Cache::remember(self::CACHE_TOKEN, self::CACHE_TOKEN_TTL, function (): string {
            $this->assertConfigured();

            $data = $this->sendDataSnapRequest(
                serverClass: 'TBasicoGeneral',
                function: 'GetAuth',
                dataJson: [
                    'email' => $this->email,
                    'password' => $this->resolvePasswordHash(),
                    'idmaquina' => $this->idmaquina,
                ],
                controlKey: '',
            );

            $token = is_array($data) ? trim((string) ($data['keyagente'] ?? '')) : '';

            if ($token === '') {
                Log::error('contapyme.authenticate_token_empty');
            }

            return $token;
        });
    }

    private function callWithRetry(string $serverClass, string $function, array $dataJson): mixed
    {
        $token = $this->authenticate();

        if ($token === '') {
            return null;
        }

        try {
            return $this->sendDataSnapRequest($serverClass, $function, $dataJson, $token);
        } catch (RuntimeException $e) {
            if (! str_contains($e->getMessage(), 'Usuario no logueado')) {
                throw $e;
            }

            $token = $this->authenticate(forceRefresh: true);

            if ($token === '') {
                return null;
            }

            return $this->sendDataSnapRequest($serverClass, $function, $dataJson, $token);
        }
    }

    /**
     * Execute the official POST request and fall back to DataSnap GET URLs.
     */
    private function sendDataSnapRequest(string $serverClass, string $function, array $dataJson, string $controlKey): mixed
    {
        $payload = [
            '_parameters' => [
                json_encode($dataJson, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                $controlKey,
                $this->iapp,
                (string) random_int(0, 999999),
            ],
        ];

        $postResponse = Http::timeout($this->timeout)
            ->post($this->functionUrl($serverClass, $function), $payload);

        if ($postResponse->successful()) {
            return $this->extractDataFromResponse($postResponse, $serverClass, $function);
        }

        $getResponse = Http::timeout($this->timeout)
            ->get($this->functionUrl($serverClass, $function).'/'.$this->pathParameters($dataJson, $controlKey));

        if (! $getResponse->successful()) {
            Log::error('contapyme.datasnap_http_error', [
                'server_class' => $serverClass,
                'function' => $function,
                'post_status' => $postResponse->status(),
                'get_status' => $getResponse->status(),
            ]);

            return null;
        }

        return $this->extractDataFromResponse($getResponse, $serverClass, $function);
    }

    private function extractDataFromResponse(Response $response, string $serverClass, string $function): mixed
    {
        $payload = $response->json();

        if (! is_array($payload)) {
            Log::error('contapyme.datasnap_invalid_json', [
                'server_class' => $serverClass,
                'function' => $function,
            ]);

            return null;
        }

        $envelope = $this->firstEnvelope($payload);
        $header = is_array($envelope) ? (array) ($envelope['encabezado'] ?? []) : [];
        $success = strtolower((string) ($header['resultado'] ?? 'false')) === 'true';

        if (! $success) {
            $message = (string) ($header['mensaje'] ?? 'Respuesta no exitosa de ContaPyme.');

            Log::warning('contapyme.datasnap_unsuccessful', [
                'server_class' => $serverClass,
                'function' => $function,
                'message_code' => $header['imensaje'] ?? null,
                'message' => $message,
            ]);

            throw new RuntimeException($message);
        }

        return data_get($envelope, 'respuesta.datos');
    }

    private function firstEnvelope(array $payload): ?array
    {
        $result = $payload['result'] ?? null;

        if (is_array($result) && isset($result[0]) && is_array($result[0])) {
            return $result[0];
        }

        if (is_array($result)) {
            return $result;
        }

        return null;
    }

    private function stockFromWarehouseRows(mixed $data): float
    {
        if (! is_array($data)) {
            return 0.0;
        }

        $rows = array_is_list($data) ? $data : [$data];

        return collect($rows)
            ->filter(fn (mixed $row): bool => is_array($row))
            ->filter(fn (array $row): bool => (string) ($row['iinventario'] ?? '') === $this->warehouse)
            ->sum(fn (array $row): float => $this->normalizeNumeric($row['qproducto'] ?? null) ?? 0.0);
    }

    /**
     * @param  array<int, mixed>  $rows
     */
    private function stockFromBulkWarehouseRows(array $rows): float
    {
        return collect($rows)
            ->filter(fn (mixed $row): bool => is_array($row))
            ->filter(fn (array $row): bool => (string) ($row['iinventario'] ?? '') === $this->warehouse)
            ->sum(fn (array $row): float => $this->normalizeNumeric($row['qinvfisico'] ?? null) ?? 0.0);
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

    private function functionUrl(string $serverClass, string $function): string
    {
        return "{$this->baseUrl}/datasnap/rest/{$serverClass}/{$function}";
    }

    private function pathParameters(array $dataJson, string $controlKey): string
    {
        return rawurlencode(json_encode($dataJson, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE))
            .'/'.rawurlencode($controlKey)
            .'/'.rawurlencode($this->iapp);
    }

    private function resolvePasswordHash(): string
    {
        if ($this->passwordHash !== '') {
            return $this->passwordHash;
        }

        return md5(strtoupper($this->password));
    }

    private function assertConfigured(): void
    {
        if ($this->baseUrl === '' || $this->email === '' || ($this->password === '' && $this->passwordHash === '')) {
            throw new RuntimeException('ContaPyme no esta configurado. Define CONTAPYME_BASE_URL, CONTAPYME_EMAIL y CONTAPYME_PASSWORD_HASH o CONTAPYME_PASSWORD.');
        }
    }
}
