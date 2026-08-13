<?php

namespace App\Modules\Inventory\Services;

use App\Modules\Inventory\ValueObjects\ContaPymePriceLookup;
use Illuminate\Support\Facades\Log;
use Throwable;

class ContaPymePriceService
{
    public function __construct(private readonly ContaPymeInventoryService $client) {}

    public function calculatedPrice(string $irecurso, string $method, string $list): ContaPymePriceLookup
    {
        $irecurso = trim($irecurso);

        if ($irecurso === '') {
            return ContaPymePriceLookup::missing('El recurso no tiene SKU.');
        }

        try {
            $data = $this->client->callReadOnly(
                'TCatElemInv',
                'GetPrecioCalculado',
                [
                    'irecurso' => $irecurso,
                    'imetodo' => $method,
                    'ilista' => $list,
                ],
                withResponse: false,
            );
        } catch (Throwable $e) {
            $message = $this->client->diagnosticError($e->getMessage());

            if ($this->looksMissing($message)) {
                return ContaPymePriceLookup::missing($message);
            }

            Log::warning('contapyme.price_lookup_failed', [
                'irecurso' => $irecurso,
                'list' => $list,
                'error' => $message,
            ]);

            return ContaPymePriceLookup::error($message);
        }

        $price = data_get($data, 'mprecio');

        if ($price === null || trim((string) $price) === '' || ! is_numeric($price)) {
            $message = $this->client->lastError() ?? 'ContaPyme no devolvio mprecio.';

            return $this->looksMissing($message)
                ? ContaPymePriceLookup::missing($message)
                : ContaPymePriceLookup::error($message);
        }

        $raw = trim((string) $price);
        if (str_starts_with($raw, '-')) {
            return ContaPymePriceLookup::error('ContaPyme devolvio un precio negativo.');
        }

        if (! preg_match('/^\+?\d+(?:\.\d+)?$/', $raw)) {
            return ContaPymePriceLookup::error('ContaPyme devolvio un precio con formato inválido.');
        }

        [$whole, $fraction] = array_pad(explode('.', $raw, 2), 2, '');
        $whole = ltrim($whole, '+') ?: '0';
        $normalized = ltrim($whole, '0') ?: '0';
        $normalized .= '.'.str_pad(substr($fraction, 0, 2), 2, '0');

        return ContaPymePriceLookup::ok($normalized);
    }

    private function looksMissing(string $message): bool
    {
        $normalized = mb_strtolower($message);

        return str_contains($normalized, 'no existe')
            || str_contains($normalized, 'no encontrado')
            || str_contains($normalized, 'no encontrado')
            || str_contains($normalized, '240');
    }
}
