<?php

namespace App\Modules\Inventory\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class InvenTreeApiClient
{
    private string $baseUrl;

    private string $apiToken;

    private int $timeout;

    private int $retryTimes;

    private int $retrySleep;

    private bool $verify;

    private ?string $validationError = null;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.inventree.base_url', ''), '/');
        $this->apiToken = (string) config('services.inventree.api_token', '');
        $this->timeout = (int) config('services.inventree.timeout', 30);
        $this->retryTimes = (int) config('services.inventree.retry_times', 3);
        $this->retrySleep = (int) config('services.inventree.retry_sleep', 100);
        $this->verify = (bool) config('services.inventree.verify', true);

        if ($this->baseUrl === '' || $this->apiToken === '') {
            $this->validationError = 'INVENTREE_BASE_URL y INVENTREE_API_TOKEN deben estar configurados en .env';
        }
    }

    public function isConfigured(): bool
    {
        return $this->validationError === null;
    }

    public function validationError(): ?string
    {
        return $this->validationError;
    }

    public function http(): PendingRequest
    {
        $this->ensureConfigured();

        return Http::baseUrl($this->baseUrl)
            ->withToken($this->apiToken, 'Token')
            ->acceptJson()
            ->asJson()
            ->timeout($this->timeout)
            ->retry($this->retryTimes, $this->retrySleep)
            ->withOptions(['verify' => $this->verify])
            ->throw();
    }

    public function getParts(array $params = []): array
    {
        return $this->fetchAll('api/part/', $params);
    }

    public function getPartsPage(array $params = []): array
    {
        return $this->fetchPage('api/part/', $params);
    }

    public function getPart(int $id): array
    {
        $this->ensureConfigured();

        return $this->http()->get("api/part/{$id}/")->json();
    }

    public function getStockItems(array $params = []): array
    {
        return $this->fetchAll('api/stock/', $params);
    }

    public function getPartCategories(array $params = []): array
    {
        return $this->fetchAll('api/part/category/', $params);
    }

    private function ensureConfigured(): void
    {
        if ($this->validationError !== null) {
            throw new RuntimeException('InvenTree API: '.$this->validationError);
        }
    }

    private function fetchAll(string $endpoint, array $params = []): array
    {
        $this->ensureConfigured();

        $all = [];
        $offset = max(0, (int) ($params['offset'] ?? 0));
        $limit = max(1, (int) ($params['limit'] ?? 100));
        $baseParams = $params;
        unset($baseParams['limit'], $baseParams['offset']);

        do {
            $data = $this->fetchPage($endpoint, array_merge($baseParams, [
                'limit' => $limit,
                'offset' => $offset,
            ]));

            $results = $data['results'];

            foreach ($results as $item) {
                $all[] = $item;
            }

            $next = $data['next'] ?? null;

            if ($next !== null) {
                $offset += $limit;
            }
        } while ($next !== null);

        return $all;
    }

    private function fetchPage(string $endpoint, array $params = []): array
    {
        $this->ensureConfigured();

        try {
            $response = $this->http()->get($endpoint, $params);
        } catch (RequestException $e) {
            $status = $e->response->status();
            $detail = "HTTP {$status}";

            throw new RuntimeException("InvenTree API: {$detail} al consultar {$endpoint}. Revisa token, permisos y endpoint.", 0, $e);
        } catch (Throwable $e) {
            throw new RuntimeException("InvenTree API: no fue posible consultar {$endpoint}: ".$e->getMessage(), 0, $e);
        }

        $data = $response->json();

        if (! is_array($data) || ! array_key_exists('results', $data) || ! is_array($data['results'])) {
            throw new RuntimeException("InvenTree API: respuesta inválida desde {$endpoint}. Se esperaba paginación con results.");
        }

        return $data;
    }
}
