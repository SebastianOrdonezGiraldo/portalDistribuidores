<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use App\Modules\Inventory\Models\StockMovement;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Shared\Contracts\InventorySyncInterface;
use App\Modules\Shared\Enums\OrderStatus;
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
    private const CACHE_TOKEN_TTL = 3600;

    private string $baseUrl = '';

    private string $email = '';

    private string $password = '';

    private string $passwordHash = '';

    private string $idmaquina = '';

    private string $iapp = '1003';

    private string $warehouse = '1';

    private int $timeout = 10;

    private ?string $lastError = null;

    /** @var array<string, string> */
    private array $lastAuthMetadata = [];

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
        $this->lastError = null;

        try {
            $authenticated = $this->authenticate(forceRefresh: true) !== '';

            if (! $authenticated) {
                $this->lastError ??= $this->diagnosticError(null, 'ContaPyme no devolvio keyagente.');
            }

            return $authenticated;
        } catch (\Throwable $e) {
            $this->lastError = $this->diagnosticError($e->getMessage());

            Log::error('contapyme.test_connection_failed', [
                'error' => $this->lastError,
                'base_url_configured' => $this->baseUrl !== '',
            ]);

            return false;
        }
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Run a non-mutating authentication and Agent health check.
     *
     * @return array{authenticated:bool, agent_status:string|null, auth_metadata:array<string,string>, error:string|null}
     */
    public function diagnose(): array
    {
        $this->lastError = null;
        $this->lastAuthMetadata = [];

        try {
            $authenticated = $this->authenticate(forceRefresh: true) !== '';
        } catch (\Throwable $e) {
            $this->lastError = $this->diagnosticError($e->getMessage());

            Log::error('contapyme.diagnose_failed', [
                'error' => $this->lastError,
                'auth_metadata' => $this->lastAuthMetadata,
            ]);

            return [
                'authenticated' => false,
                'agent_status' => null,
                'auth_metadata' => $this->lastAuthMetadata,
                'error' => $this->lastError,
            ];
        }

        if (! $authenticated) {
            return [
                'authenticated' => false,
                'agent_status' => null,
                'auth_metadata' => $this->lastAuthMetadata,
                'error' => $this->lastError ?? $this->diagnosticError(null, 'ContaPyme no devolvio keyagente.'),
            ];
        }

        try {
            $testResponse = $this->callWithRetry(
                serverClass: 'TBasicoGeneral',
                function: 'Test',
                dataJson: [],
            );
            $agentStatus = $this->agentStatusFromResponse($testResponse);

            if ($agentStatus === null) {
                $this->lastError = $this->diagnosticError(null, 'ContaPyme no devolvio un estado valido del Agente.');
            }

            Log::info('contapyme.diagnose_completed', [
                'agent_status' => $agentStatus,
                'auth_metadata' => $this->lastAuthMetadata,
            ]);

            return [
                'authenticated' => true,
                'agent_status' => $agentStatus,
                'auth_metadata' => $this->lastAuthMetadata,
                'error' => $this->lastError,
            ];
        } catch (\Throwable $e) {
            $this->lastError = $this->diagnosticError($e->getMessage());

            Log::error('contapyme.diagnose_agent_failed', [
                'error' => $this->lastError,
                'auth_metadata' => $this->lastAuthMetadata,
            ]);

            return [
                'authenticated' => true,
                'agent_status' => null,
                'auth_metadata' => $this->lastAuthMetadata,
                'error' => $this->lastError,
            ];
        }
    }

    public function diagnosticError(?string $message, string $fallback = 'ContaPyme no devolvio una causa especifica.'): string
    {
        $message = trim((string) $message);

        return $this->sanitizeErrorMessage($message !== '' ? $message : $fallback);
    }

    /**
     * Verify that an inventory item exists independently from its physical balance.
     */
    public function productExists(string $sku): ?bool
    {
        $this->lastError = null;
        $sku = trim($sku);

        if ($sku === '') {
            $this->lastError = 'No se puede validar un SKU vacio en ContaPyme.';

            return null;
        }

        try {
            $data = $this->callWithRetry(
                serverClass: 'TCatElemInv',
                function: 'GetExisteElemInv',
                dataJson: ['irecurso' => $sku],
            );
        } catch (\Throwable $e) {
            $this->lastError = $this->diagnosticError($e->getMessage());

            Log::error('contapyme.product_exists_failed', [
                'sku' => $sku,
                'error' => $this->lastError,
            ]);

            return null;
        }

        if ($data === null && $this->lastError !== null) {
            return null;
        }

        $exists = $this->normalizeBoolean(data_get($data, 'existe'));

        if ($exists !== null) {
            return $exists;
        }

        $this->lastError = $this->diagnosticError(
            null,
            'ContaPyme no devolvio una confirmacion de existencia valida.',
        );

        Log::error('contapyme.product_exists_invalid_response', [
            'sku' => $sku,
            'data_type' => get_debug_type($data),
        ]);

        return null;
    }

    /**
     * Fetch the physical stock for one portal SKU in the configured warehouse.
     *
     * The endpoint can return no rows when physical balance is zero. Use
     * productExists() when the caller also needs to establish SKU identity.
     */
    public function getProductInfo(string $sku): ?InventoryItemData
    {
        $this->lastError = null;
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
                $this->lastError ??= $this->diagnosticError(
                    null,
                    'ContaPyme no devolvio datos de stock para el SKU consultado.',
                );

                return null;
            }

            return new InventoryItemData(
                sku: $sku,
                stock: $this->stockFromWarehouseRows($data),
                externalId: $sku,
                rawData: is_array($data) ? $data : ['datos' => $data],
            );
        } catch (\Throwable $e) {
            $this->lastError = $this->diagnosticError($e->getMessage());

            Log::error('contapyme.get_product_stock_failed', [
                'sku' => $sku,
                'error' => $this->lastError,
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
        $this->lastError = null;

        try {
            $data = $this->callWithRetry(
                serverClass: 'TCatElemInv',
                function: 'GetSaldosProductosEnBodegas',
                dataJson: [
                    'iinventario' => 'T',
                    'bunidadrecurso' => 'T',
                    'binventariofisico' => 'T',
                    'bnombreinventario' => 'T',
                ],
            );
        } catch (\Throwable $e) {
            $this->lastError = $this->diagnosticError($e->getMessage());

            Log::error('contapyme.list_products_failed', [
                'error' => $this->lastError,
            ]);

            return collect();
        }

        if (! is_array($data)) {
            $this->lastError ??= $this->diagnosticError(
                null,
                'ContaPyme no devolvio datos de inventario validos.',
            );

            Log::error('contapyme.list_products_invalid_response', [
                'error' => $this->lastError,
            ]);

            return collect();
        }

        $products = data_get($data, 'listaproductos', []);

        if (! is_array($products)) {
            $this->lastError = $this->diagnosticError(
                null,
                'ContaPyme no devolvio listaproductos.',
            );

            Log::error('contapyme.list_products_missing_list', [
                'error' => $this->lastError,
            ]);

            return collect();
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
                    stock: $this->stockFromBulkWarehouseRows(
                        rows: (array) ($row['listabodegas'] ?? []),
                        sku: $sku,
                    ),
                    externalId: $sku,
                    rawData: $row,
                );
            })
            ->filter()
            ->values();
    }

    /**
     * Read the visible ContaPyme inventory catalog page by page.
     *
     * The stock endpoint omits products without balance. This catalog is used
     * only to establish identity before a zero can be written locally.
     *
     * @return Collection<int, array{irecurso:string, name:string|null}>|null
     */
    public function listInventoryCatalog(): ?Collection
    {
        $this->lastError = null;
        $page = 1;
        $pageSize = min(1000, max(1, (int) config('contapyme.catalog_page_size', 200)));
        $items = collect();
        $totalPages = null;

        try {
            do {
                $response = $this->callWithRetry(
                    serverClass: 'TCatElemInv',
                    function: 'GetListaElemInv',
                    dataJson: [
                        'datospagina' => [
                            'cantidadregistros' => (string) $pageSize,
                            'pagina' => (string) $page,
                        ],
                        'camposderetorno' => ['irecurso', 'nrecurso'],
                        'datosfiltro' => new \stdClass,
                    ],
                    withResponse: true,
                );

                if (! is_array($response)) {
                    $this->lastError ??= $this->diagnosticError(
                        null,
                        'ContaPyme no devolvio el catalogo de inventario.',
                    );

                    Log::error('contapyme.catalog_invalid_response', [
                        'error' => $this->lastError,
                        'page' => $page,
                    ]);

                    return null;
                }

                $rows = data_get($response, 'datos', []);

                if (! is_array($rows)) {
                    $this->lastError = $this->diagnosticError(
                        null,
                        'ContaPyme devolvio un catalogo de inventario invalido.',
                    );

                    Log::error('contapyme.catalog_invalid_rows', [
                        'error' => $this->lastError,
                        'page' => $page,
                    ]);

                    return null;
                }

                $items = $items->merge(collect($rows)
                    ->filter(fn (mixed $row): bool => is_array($row))
                    ->map(function (array $row): ?array {
                        $irecurso = trim((string) ($row['irecurso'] ?? ''));

                        return $irecurso === '' ? null : [
                            'irecurso' => $irecurso,
                            'name' => filled($row['nrecurso'] ?? null) ? (string) $row['nrecurso'] : null,
                        ];
                    })
                    ->filter()
                    ->values());

                $totalPages ??= max(1, (int) data_get($response, 'paginacion.totalpaginas', 1));
                $page++;
            } while ($page <= $totalPages);

            $context = [
                'pages' => $page - 1,
                'items' => $items->count(),
                'catalog_status' => $items->isEmpty() ? 'empty' : 'loaded',
            ];

            if ($items->isEmpty()) {
                Log::warning('contapyme.catalog_empty', $context);
            } else {
                Log::info('contapyme.catalog_loaded', $context);
            }

            return $items->unique('irecurso')->values();
        } catch (\Throwable $e) {
            $this->lastError = $this->diagnosticError($e->getMessage());

            Log::error('contapyme.catalog_failed', [
                'error' => $this->lastError,
                'page' => $page,
                'catalog_status' => 'unavailable',
            ]);

            return null;
        }
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
        $exists = $this->productExists((string) $product->sku);

        if ($exists === null) {
            return $this->failedSyncResult($product);
        }

        if (! $exists) {
            return $this->markProductMissingInContaPyme($product);
        }

        $info = $this->getProductInfo((string) $product->sku);

        if ($info === null || $info->stock === null) {
            return $this->failedSyncResult($product);
        }

        return $this->syncProductFromPhysicalStock(
            product: $product,
            physicalStock: $info->stock,
            reservedStock: $this->reservedQuantityForProduct((int) $product->id),
        );
    }

    /**
     * Preserve local availability for a SKU that ContaPyme explicitly does not know.
     *
     * @return array{status:string, changed:bool, stock:float|null}
     */
    public function markProductMissingInContaPyme(Product $product): array
    {
        $stock = is_numeric($product->stock) ? (float) $product->stock : null;

        DB::transaction(function () use ($product): void {
            $lockedProduct = Product::query()
                ->whereKey($product->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedProduct->stock_sync_status === 'missing_contapyme' && $lockedProduct->stock_synced_at === null) {
                return;
            }

            $lockedProduct->forceFill([
                'stock_sync_status' => 'missing_contapyme',
                'stock_synced_at' => null,
            ])->save();
        });

        return [
            'status' => 'missing_contapyme',
            'changed' => false,
            'stock' => $stock,
        ];
    }

    /**
     * Persist portal availability from ContaPyme physical stock minus local reservations.
     *
     * @return array{status:string, changed:bool, stock:float}
     */
    public function syncProductFromPhysicalStock(
        Product $product,
        float $physicalStock,
        float $reservedStock = 0.0,
        string $syncStatus = 'synced',
    ): array {
        $baseAvailableStock = round(max(0, $physicalStock - max(0, $reservedStock)), 2);
        $expectedPreviousStock = is_numeric($product->stock) ? (float) $product->stock : null;
        $previousStock = null;
        $newStock = $baseAvailableStock;
        $changed = false;

        DB::transaction(function () use (
            $product,
            $expectedPreviousStock,
            $baseAvailableStock,
            $syncStatus,
            &$previousStock,
            &$newStock,
            &$changed,
        ): void {
            $lockedProduct = Product::query()
                ->whereKey($product->id)
                ->lockForUpdate()
                ->firstOrFail();

            $previousStock = is_numeric($lockedProduct->stock) ? (float) $lockedProduct->stock : null;

            // An order can reserve or release stock after the bulk snapshot but before
            // this row lock. Preserve that local delta instead of overwriting it.
            $localStockDelta = $expectedPreviousStock !== null && $previousStock !== null
                ? $previousStock - $expectedPreviousStock
                : 0.0;
            $newStock = round(max(0, $baseAvailableStock + $localStockDelta), 2);
            $changed = $previousStock === null || abs($previousStock - $newStock) > 0.00001;

            $lockedProduct->forceFill([
                'stock' => $newStock,
                'stock_synced_at' => now(),
                'stock_sync_status' => $syncStatus,
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
     * Persist a mapped variant using the same reservation-safe arithmetic as a
     * simple product. The parent aggregate is recalculated after the update.
     *
     * @return array{status:string, changed:bool, stock:float}
     */
    public function syncVariantFromPhysicalStock(
        ProductVariant $variant,
        float $physicalStock,
        float $reservedStock = 0.0,
        string $syncStatus = 'synced',
    ): array {
        $baseAvailableStock = round(max(0, $physicalStock - max(0, $reservedStock)), 2);
        $expectedPreviousStock = is_numeric($variant->stock) ? (float) $variant->stock : null;
        $previousStock = null;
        $newStock = $baseAvailableStock;
        $changed = false;

        DB::transaction(function () use (
            $variant,
            $expectedPreviousStock,
            $baseAvailableStock,
            $syncStatus,
            &$previousStock,
            &$newStock,
            &$changed,
        ): void {
            $lockedVariant = ProductVariant::query()
                ->whereKey($variant->id)
                ->lockForUpdate()
                ->firstOrFail();

            $previousStock = is_numeric($lockedVariant->stock) ? (float) $lockedVariant->stock : null;
            $localStockDelta = $expectedPreviousStock !== null && $previousStock !== null
                ? $previousStock - $expectedPreviousStock
                : 0.0;
            $newStock = round(max(0, $baseAvailableStock + $localStockDelta), 2);
            $changed = $previousStock === null || abs($previousStock - $newStock) > 0.00001;

            $lockedVariant->forceFill([
                'stock' => $newStock,
                'stock_sync_status' => $syncStatus,
                'stock_synced_at' => now(),
            ])->save();

            if ($changed) {
                StockMovement::record(
                    product: $lockedVariant->product,
                    variant: $lockedVariant,
                    previousStock: $previousStock,
                    newStock: $newStock,
                    source: 'contapyme_sync',
                );
            }
        });

        $this->recalculateVariantParentStock($variant->product_id);

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
            Cache::forget($this->cacheTokenKey());
        }

        return Cache::remember($this->cacheTokenKey(), self::CACHE_TOKEN_TTL, function (): string {
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
            $this->lastAuthMetadata = is_array($data)
                ? collect($data)
                    ->only(['version', 'release', 'update', 'actualizacion', 'actualización'])
                    ->filter(fn (mixed $value): bool => is_scalar($value) && (string) $value !== '')
                    ->map(fn (mixed $value): string => (string) $value)
                    ->all()
                : [];

            if ($token === '') {
                $this->lastError ??= $this->diagnosticError(null, 'ContaPyme no devolvio keyagente.');

                Log::error('contapyme.authenticate_token_empty');
            }

            return $token;
        });
    }

    /**
     * @phpstan-impure
     */
    private function callWithRetry(string $serverClass, string $function, array $dataJson, bool $withResponse = false): mixed
    {
        $token = $this->authenticate();

        if ($token === '') {
            return null;
        }

        try {
            return $this->sendDataSnapRequest($serverClass, $function, $dataJson, $token, $withResponse);
        } catch (RuntimeException $e) {
            if (! str_contains($e->getMessage(), 'Usuario no logueado')) {
                throw $e;
            }

            $token = $this->authenticate(forceRefresh: true);

            if ($token === '') {
                return null;
            }

            $this->lastError = null;

            return $this->sendDataSnapRequest($serverClass, $function, $dataJson, $token, $withResponse);
        }
    }

    /**
     * Execute the official POST request and fall back to DataSnap GET URLs.
     */
    private function sendDataSnapRequest(
        string $serverClass,
        string $function,
        array $dataJson,
        string $controlKey,
        bool $withResponse = false,
    ): mixed {
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
            return $this->extractDataFromResponse($postResponse, $serverClass, $function, $withResponse);
        }

        $getResponse = Http::timeout($this->timeout)
            ->get($this->functionUrl($serverClass, $function).'/'.$this->pathParameters($dataJson, $controlKey));

        if (! $getResponse->successful()) {
            $this->lastError = $this->diagnosticError(
                "ContaPyme no respondio correctamente en {$serverClass}::{$function} "
                ."(HTTP POST {$postResponse->status()}, GET {$getResponse->status()}).",
            );

            Log::error('contapyme.datasnap_http_error', [
                'server_class' => $serverClass,
                'function' => $function,
                'post_status' => $postResponse->status(),
                'get_status' => $getResponse->status(),
                'error' => $this->lastError,
            ]);

            return null;
        }

        return $this->extractDataFromResponse($getResponse, $serverClass, $function, $withResponse);
    }

    private function extractDataFromResponse(
        Response $response,
        string $serverClass,
        string $function,
        bool $withResponse = false,
    ): mixed {
        $payload = $response->json();

        if (! is_array($payload)) {
            $this->lastError = $this->diagnosticError(
                "ContaPyme devolvio una respuesta JSON invalida en {$serverClass}::{$function}.",
            );

            Log::error('contapyme.datasnap_invalid_json', [
                'server_class' => $serverClass,
                'function' => $function,
                'error' => $this->lastError,
            ]);

            return null;
        }

        $envelope = $this->firstEnvelope($payload);
        $header = is_array($envelope) ? (array) ($envelope['encabezado'] ?? []) : [];
        $success = strtolower((string) ($header['resultado'] ?? 'false')) === 'true';

        if (! $success) {
            $message = $this->diagnosticError(
                (string) ($header['mensaje'] ?? ''),
                "ContaPyme devolvio una respuesta no exitosa en {$serverClass}::{$function}.",
            );

            $this->lastError = $message;

            Log::warning('contapyme.datasnap_unsuccessful', [
                'server_class' => $serverClass,
                'function' => $function,
                'message_code' => $header['imensaje'] ?? null,
                'message' => $message,
            ]);

            throw new RuntimeException($this->lastError);
        }

        $responseData = data_get($envelope, 'respuesta', []);

        return $withResponse
            ? (is_array($responseData) ? $responseData : [])
            : data_get($responseData, 'datos');
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

    private function stockFromWarehouseRows(mixed $data): ?float
    {
        if (! is_array($data)) {
            return null;
        }

        $rows = array_is_list($data) ? $data : [$data];

        $warehouseRows = collect($rows)
            ->filter(fn (mixed $row): bool => is_array($row))
            ->filter(fn (array $row): bool => (string) ($row['iinventario'] ?? '') === $this->warehouse)
            ->filter(fn (array $row): bool => $this->normalizeNumeric($row['qproducto'] ?? null) !== null);

        if ($warehouseRows->isEmpty()) {
            return null;
        }

        return (float) $warehouseRows->sum(fn (array $row): float => (float) $this->normalizeNumeric($row['qproducto']));
    }

    /**
     * @param  array<int, mixed>  $rows
     */
    private function stockFromBulkWarehouseRows(array $rows, string $sku): ?float
    {
        $warehouseRows = collect($rows)
            ->filter(fn (mixed $row): bool => is_array($row))
            ->filter(fn (array $row): bool => (string) ($row['iinventario'] ?? '') === $this->warehouse);

        if ($warehouseRows->isEmpty()) {
            Log::warning('contapyme.bulk_warehouse_not_found_for_product', [
                'sku' => $sku,
                'warehouse' => $this->warehouse,
            ]);

            return null;
        }

        $warehouseRows = $warehouseRows
            ->filter(fn (array $row): bool => $this->normalizeNumeric($row['qinvfisico'] ?? null) !== null);

        if ($warehouseRows->isEmpty()) {
            Log::warning('contapyme.bulk_warehouse_balance_invalid_for_product', [
                'sku' => $sku,
                'warehouse' => $this->warehouse,
            ]);

            return null;
        }

        return (float) $warehouseRows->sum(fn (array $row): float => (float) $this->normalizeNumeric($row['qinvfisico']));
    }

    private function reservedQuantityForProduct(int $productId): float
    {
        if ($productId <= 0) {
            return 0.0;
        }

        return (float) OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.product_id', $productId)
            ->whereNull('order_items.product_variant_id')
            ->whereIn('orders.status', array_map(
                fn (OrderStatus $status): string => $status->value,
                OrderStatus::inventoryConsuming(),
            ))
            ->sum('order_items.qty');
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

    private function normalizeBoolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (! is_string($value) && ! is_int($value)) {
            return null;
        }

        return match (strtolower(trim((string) $value))) {
            'true', '1' => true,
            'false', '0' => false,
            default => null,
        };
    }

    private function agentStatusFromResponse(mixed $response): ?string
    {
        if (is_string($response) && trim($response) !== '') {
            return $this->sanitizeErrorMessage(trim($response));
        }

        if (! is_array($response)) {
            return null;
        }

        foreach (['estado', 'status', 'mensaje', 'resultado'] as $key) {
            $value = data_get($response, $key);

            if (is_scalar($value) && trim((string) $value) !== '') {
                return $this->sanitizeErrorMessage((string) $value);
            }
        }

        return null;
    }

    /**
     * @return array{status:string, changed:bool, stock:float|null}
     */
    private function failedSyncResult(Product $product): array
    {
        return [
            'status' => 'failed',
            'changed' => false,
            'stock' => is_numeric($product->stock) ? (float) $product->stock : null,
        ];
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

    private function sanitizeErrorMessage(string $message): string
    {
        $sensitiveValues = array_filter([
            $this->email,
            $this->password,
            $this->passwordHash,
            $this->password !== '' ? md5(strtoupper($this->password)) : '',
        ]);

        foreach ($sensitiveValues as $value) {
            $message = str_replace($value, '[redacted]', $message);
        }

        return trim($message);
    }

    private function cacheTokenKey(): string
    {
        return 'contapyme_keyagente_'.hash('sha256', implode('|', [
            $this->baseUrl,
            $this->email,
            $this->iapp,
            $this->idmaquina,
        ]));
    }

    private function recalculateVariantParentStock(int $productId): void
    {
        if ($productId <= 0) {
            return;
        }

        DB::transaction(function () use ($productId): void {
            $product = Product::query()->whereKey($productId)->lockForUpdate()->first();

            if (! $product) {
                return;
            }

            $stocks = ProductVariant::query()
                ->where('product_id', $productId)
                ->where('is_active', true)
                ->pluck('stock');
            $hasAnyStock = $stocks->contains(fn ($stock): bool => $stock !== null);
            $aggregate = $hasAnyStock
                ? (float) round($stocks->filter(fn ($stock): bool => $stock !== null)->sum(), 2)
                : null;

            $product->forceFill([
                'stock' => $aggregate,
                'stock_synced_at' => now(),
                'stock_sync_status' => 'synced',
            ])->save();
        });
    }

    private function assertConfigured(): void
    {
        if ($this->baseUrl === '' || $this->email === '' || ($this->password === '' && $this->passwordHash === '')) {
            throw new RuntimeException('ContaPyme no esta configurado. Define CONTAPYME_URL, CONTAPYME_EMAIL y CONTAPYME_PASSWORD_MD5 o CONTAPYME_PASSWORD.');
        }
    }
}
