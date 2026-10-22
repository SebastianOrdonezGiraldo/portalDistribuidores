<?php

namespace App\Modules\Inventory\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * HTTP transport for the ContaPyme DataSnap REST API.
 *
 * Handles authentication (cached keyagente), the official POST call with its
 * DataSnap GET fallback, response envelopes and credential redaction. It knows
 * nothing about products or stock.
 *
 * Failure contract of call():
 *  - transport problems (HTTP errors, invalid JSON, empty token) return null
 *    and leave the reason in lastError();
 *  - an unsuccessful ContaPyme envelope throws RuntimeException with the
 *    (redacted) ContaPyme message, also available in lastError().
 */
class ContaPymeClient
{
    private const CACHE_TOKEN_TTL = 3600;

    private string $baseUrl = '';

    private string $email = '';

    private string $password = '';

    private string $passwordHash = '';

    private string $idmaquina = '';

    private string $iapp = '1003';

    private int $timeout = 10;

    private ?string $lastError = null;

    /** @var array<string, string> */
    private array $authMetadata = [];

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('contapyme.base_url', ''), '/');
        $this->email = (string) config('contapyme.email', '');
        $this->password = (string) config('contapyme.password', '');
        $this->passwordHash = strtolower((string) config('contapyme.password_hash', ''));
        $this->idmaquina = (string) config('contapyme.idmaquina', '');
        $this->iapp = (string) config('contapyme.iapp', '1003');
        $this->timeout = (int) config('contapyme.timeout', 10);
    }

    public function hasBaseUrl(): bool
    {
        return $this->baseUrl !== '';
    }

    public function lastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * Version metadata returned by the last fresh GetAuth call.
     *
     * @return array<string, string>
     */
    public function authMetadata(): array
    {
        return $this->authMetadata;
    }

    /**
     * Authenticate and return keyagente ('' when ContaPyme returned none).
     */
    public function authenticate(bool $forceRefresh = false): string
    {
        $this->lastError = null;

        return $this->token($forceRefresh);
    }

    /**
     * Call a DataSnap function and return its `respuesta.datos`, refreshing the
     * token once when ContaPyme reports the session expired.
     *
     * @phpstan-impure
     */
    public function call(string $serverClass, string $function, array $dataJson): mixed
    {
        $this->lastError = null;

        $token = $this->token();

        if ($token === '') {
            return null;
        }

        try {
            return $this->sendDataSnapRequest($serverClass, $function, $dataJson, $token);
        } catch (RuntimeException $e) {
            if (! str_contains($e->getMessage(), 'Usuario no logueado')) {
                throw $e;
            }

            $token = $this->token(forceRefresh: true);

            if ($token === '') {
                return null;
            }

            $this->lastError = null;

            return $this->sendDataSnapRequest($serverClass, $function, $dataJson, $token);
        }
    }

    public function diagnosticError(?string $message, string $fallback = 'ContaPyme no devolvio una causa especifica.'): string
    {
        $message = trim((string) $message);

        return $this->redact($message !== '' ? $message : $fallback);
    }

    /**
     * Strip configured credentials from a message before it is logged or shown.
     */
    public function redact(string $message): string
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

    /**
     * Authenticate against ContaPyme and cache keyagente for subsequent calls.
     */
    private function token(bool $forceRefresh = false): string
    {
        if ($forceRefresh) {
            Cache::forget($this->cacheTokenKey());
            $this->authMetadata = [];
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
            $this->authMetadata = is_array($data)
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
     * Execute the official POST request and fall back to DataSnap GET URLs.
     */
    private function sendDataSnapRequest(
        string $serverClass,
        string $function,
        array $dataJson,
        string $controlKey,
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
            return $this->extractDataFromResponse($postResponse, $serverClass, $function);
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

        return $this->extractDataFromResponse($getResponse, $serverClass, $function);
    }

    private function extractDataFromResponse(Response $response, string $serverClass, string $function): mixed
    {
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

    private function cacheTokenKey(): string
    {
        return 'contapyme_keyagente_'.hash('sha256', implode('|', [
            $this->baseUrl,
            $this->email,
            $this->iapp,
            $this->idmaquina,
        ]));
    }

    private function assertConfigured(): void
    {
        if ($this->baseUrl === '' || $this->email === '' || ($this->password === '' && $this->passwordHash === '')) {
            throw new RuntimeException('ContaPyme no esta configurado. Define CONTAPYME_URL, CONTAPYME_EMAIL y CONTAPYME_PASSWORD_MD5 o CONTAPYME_PASSWORD.');
        }
    }
}
