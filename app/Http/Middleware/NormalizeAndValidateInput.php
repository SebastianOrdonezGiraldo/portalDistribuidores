<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class NormalizeAndValidateInput
{
    private const MAX_DEPTH = 8;

    /**
     * @var list<string>
     */
    private array $skipInspectionKeys = [
        'password',
        'password_confirmation',
        'current_password',
    ];

    /**
     * @var list<string>
     */
    private array $sqlInjectionPatterns = [
        '/\bunion\b\s+(?:all\s+)?\bselect\b/i',
        '/\b(?:drop|truncate|alter)\b\s+\b(?:table|database)\b/i',
        '/(?:\'|")\s*(?:or|and)\s+(?:\'[^\']*\'|\d+)\s*=\s*(?:\'[^\']*\'|\d+)/i',
        '/\b(?:benchmark|sleep)\s*\(\s*\d+/i',
        '/\binformation_schema\b/i',
    ];

    /**
     * @var list<string>
     */
    private array $commandInjectionPatterns = [
        '/\$\([^)]*\)|`[^`]*`/u',
        '/(?:^|[;&|]{1,2}\s*)(?:cat|ls|rm|cp|mv|chmod|chown|curl|wget|bash|sh|powershell|cmd|php|python|node)\b/i',
        '/\.\.(?:\/|\\\\)/u',
    ];

    /**
     * @var list<string>
     */
    private array $scriptInjectionPatterns = [
        '/<\s*script\b/i',
        '/<\s*\/\s*script\s*>/i',
        '/\bjavascript\s*:/i',
        '/\bdata\s*:\s*text\/html/i',
        '/<\s*(iframe|object|embed)\b/i',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $request->query->replace(
            $this->normalizeArray(
                $request->query->all(),
                'query',
            )
        );

        $request->request->replace(
            $this->normalizeArray(
                $request->request->all(),
                'body',
            )
        );

        return $next($request);
    }

    /**
     * @param  array<string|int, mixed>  $input
     * @return array<string|int, mixed>
     */
    private function normalizeArray(array $input, string $source, int $depth = 0): array
    {
        if ($depth > self::MAX_DEPTH) {
            $this->throwValidationError('input', 'La estructura del payload es demasiado profunda.');
        }

        $normalized = [];

        foreach ($input as $rawKey => $value) {
            $key = is_int($rawKey) ? $rawKey : $this->normalizeKey((string) $rawKey, $source);
            $normalized[$key] = $this->normalizeValue($value, (string) $rawKey, $source, $depth + 1);
        }

        return $normalized;
    }

    private function normalizeValue(mixed $value, string $key, string $source, int $depth): mixed
    {
        if (is_array($value)) {
            return $this->normalizeArray($value, $source, $depth);
        }

        if (is_string($value)) {
            $sanitized = $this->sanitizeString($value);

            if (! $this->shouldSkipInspection($key)) {
                $this->assertNoInjectionPayload($sanitized, $key);
            }

            return $sanitized;
        }

        if (is_scalar($value) || $value === null) {
            return $value;
        }

        $this->throwValidationError($key, 'Tipo de dato no soportado en la solicitud.');
    }

    private function normalizeKey(string $key, string $source): string
    {
        if ($key === '' || preg_match('/^[A-Za-z0-9_.-]{1,80}$/', $key) !== 1) {
            $this->throwValidationError('input', "Nombre de campo invalido en {$source}.");
        }

        return $key;
    }

    private function sanitizeString(string $value): string
    {
        if (! mb_check_encoding($value, 'UTF-8')) {
            $this->throwValidationError('input', 'El texto contiene una codificacion invalida.');
        }

        if (str_contains($value, "\0")) {
            $this->throwValidationError('input', 'Se detectaron bytes nulos no permitidos.');
        }

        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? $value;

        return trim($value);
    }

    private function shouldSkipInspection(string $key): bool
    {
        $normalized = Str::lower($key);

        return in_array($normalized, $this->skipInspectionKeys, true);
    }

    private function assertNoInjectionPayload(string $value, string $key): void
    {
        if ($value === '') {
            return;
        }

        foreach ($this->sqlInjectionPatterns as $pattern) {
            if (preg_match($pattern, $value) === 1) {
                $this->throwValidationError($key, 'El campo contiene una secuencia SQL no permitida.');
            }
        }

        foreach ($this->commandInjectionPatterns as $pattern) {
            if (preg_match($pattern, $value) === 1) {
                $this->throwValidationError($key, 'El campo contiene una secuencia de comandos no permitida.');
            }
        }

        foreach ($this->scriptInjectionPatterns as $pattern) {
            if (preg_match($pattern, $value) === 1) {
                $this->throwValidationError($key, 'El campo contiene contenido HTML o script no permitido.');
            }
        }
    }

    private function throwValidationError(string $field, string $message): never
    {
        throw ValidationException::withMessages([
            $field => $message,
        ]);
    }
}
