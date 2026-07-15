<?php

namespace App\Modules\Catalog\Services;

use App\Modules\Catalog\Models\Product;
use App\Modules\Categories\Models\Category;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Throwable;

class ProductBulkImportService
{
    private const MAX_ERRORS = 60;

    /**
     * @var array<int, bool>
     */
    private array $categoryIds = [];

    /**
     * @var array<string, int|null>
     */
    private array $categoryIdBySlug = [];

    /**
     * @var array<string, int|null>
     */
    private array $categoryIdByName = [];

    /**
     * @var array<string, Product|null>
     */
    private array $productCacheBySku = [];

    /**
     * @var list<string>
     */
    private array $requiredHeaders = [
        'sku',
        'name',
        'category_id',
        'price',
    ];

    /**
     * @return array{
     *   ok:bool,
     *   total_rows:int,
     *   processed_rows:int,
     *   created:int,
     *   updated:int,
     *   skipped:int,
     *   errors:list<array{row:int,sku:string,message:string}>
     * }
     */
    public function importFromCsv(string $absolutePath, string $defaultAction = 'upsert'): array
    {
        $this->boot();

        $report = [
            'ok' => true,
            'total_rows' => 0,
            'processed_rows' => 0,
            'created' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        $resolvedDefaultAction = $this->normalizeAction($defaultAction);
        $delimiter = $this->detectDelimiter($absolutePath);
        $handle = fopen($absolutePath, 'rb');

        if (! is_resource($handle)) {
            $report['ok'] = false;
            $report['errors'][] = [
                'row' => 1,
                'sku' => '',
                'message' => 'No fue posible abrir el archivo para procesarlo.',
            ];

            return $report;
        }

        $rawHeaderRow = fgetcsv($handle, 0, $delimiter);

        if (! is_array($rawHeaderRow) || $rawHeaderRow === []) {
            fclose($handle);
            $report['ok'] = false;
            $report['errors'][] = [
                'row' => 1,
                'sku' => '',
                'message' => 'El archivo no contiene encabezados válidos.',
            ];

            return $report;
        }

        $headers = array_map(fn ($header) => $this->normalizeHeader((string) $header), $rawHeaderRow);
        $headerIndexes = array_flip($headers);
        $missingHeaders = array_values(array_diff($this->requiredHeaders, $headers));

        if ($missingHeaders !== []) {
            fclose($handle);
            $report['ok'] = false;
            $report['errors'][] = [
                'row' => 1,
                'sku' => '',
                'message' => 'Faltan columnas obligatorias: '.implode(', ', $missingHeaders).'.',
            ];

            return $report;
        }

        $lineNumber = 1;

        while (($row = fgetcsv($handle, 0, $delimiter)) !== false) {
            $lineNumber++;

            if ($this->isEmptyRow($row)) {
                $report['skipped']++;
                continue;
            }

            $report['total_rows']++;
            $rowPayload = $this->mapRowPayload($row, $headerIndexes, $resolvedDefaultAction);
            $sku = trim((string) ($rowPayload['sku'] ?? ''));

            try {
                $errorMessage = $this->validateRowPayload($rowPayload);

                if ($errorMessage !== null) {
                    $report['ok'] = false;
                    $this->appendError($report, $lineNumber, $sku, $errorMessage);
                    continue;
                }

                $action = (string) $rowPayload['action'];
                $existing = $this->findProductBySku($sku);
                $attributes = [
                    'name' => (string) $rowPayload['name'],
                    'brand' => $rowPayload['brand'] !== null ? (string) $rowPayload['brand'] : null,
                    'sku' => $sku,
                    'description' => $rowPayload['description'] !== null ? (string) $rowPayload['description'] : null,
                    'category_id' => (int) $rowPayload['category_id'],
                    'price' => (float) $rowPayload['price'],
                    'stock' => $rowPayload['stock'] !== null ? (float) $rowPayload['stock'] : null,
                    'is_active' => (bool) ($rowPayload['is_active'] ?? true),
                    'is_vat_excluded' => (bool) ($rowPayload['is_vat_excluded'] ?? false),
                ];

                if ($action === 'create') {
                    if ($existing) {
                        $report['ok'] = false;
                        $this->appendError($report, $lineNumber, $sku, 'El SKU ya existe y la acción es create.');
                        continue;
                    }

                    $this->persistCreate($sku, $attributes, $report);
                    continue;
                }

                if ($action === 'update') {
                    if (! $existing) {
                        $report['ok'] = false;
                        $this->appendError($report, $lineNumber, $sku, 'No existe un producto con ese SKU para actualizar.');
                        continue;
                    }

                    $this->persistUpdate($existing, $attributes, $report);
                    continue;
                }

                if ($existing) {
                    $this->persistUpdate($existing, $attributes, $report);
                    continue;
                }

                $this->persistCreate($sku, $attributes, $report);
            } catch (Throwable) {
                $report['ok'] = false;
                $this->appendError($report, $lineNumber, $sku, 'Error inesperado al procesar la fila.');
            }
        }

        fclose($handle);

        return $report;
    }

    /**
     * @param  array<int, string|null>  $row
     * @param  array<string, int>  $headerIndexes
     * @return array<string, mixed>
     */
    private function mapRowPayload(array $row, array $headerIndexes, string $defaultAction): array
    {
        $action = $this->normalizeAction($this->cell($row, $headerIndexes, 'action') ?? $defaultAction);
        $sku = trim((string) $this->cell($row, $headerIndexes, 'sku'));
        $name = trim((string) $this->cell($row, $headerIndexes, 'name'));
        $brand = $this->normalizeNullableString($this->cell($row, $headerIndexes, 'brand'));
        $description = $this->normalizeNullableString($this->cell($row, $headerIndexes, 'description'));
        [$categoryId, $categoryInvalid] = $this->normalizeCategoryReference($this->cell($row, $headerIndexes, 'category_id'));
        $price = $this->normalizeNullableDecimal($this->cell($row, $headerIndexes, 'price'));
        $stock = $this->normalizeNullableDecimal($this->cell($row, $headerIndexes, 'stock'));
        [$isActive, $isActiveInvalid] = $this->normalizeNullableBoolean($this->cell($row, $headerIndexes, 'is_active'));
        [$isVatExcluded, $isVatExcludedInvalid] = $this->normalizeNullableBoolean($this->cell($row, $headerIndexes, 'is_vat_excluded'));

        return [
            'action' => $action,
            'sku' => $sku,
            'name' => $name,
            'brand' => $brand,
            'description' => $description,
            'category_id' => $categoryId,
            'category_invalid' => $categoryInvalid,
            'price' => $price,
            'stock' => $stock,
            'is_active' => $isActive,
            'is_active_invalid' => $isActiveInvalid,
            'is_vat_excluded' => $isVatExcluded,
            'is_vat_excluded_invalid' => $isVatExcludedInvalid,
        ];
    }

    /**
     * @param  array<string, mixed>  $rowPayload
     */
    private function validateRowPayload(array $rowPayload): ?string
    {
        if (($rowPayload['is_active_invalid'] ?? false) === true) {
            return 'El campo is_active debe ser 1/0, true/false o si/no.';
        }

        if (($rowPayload['is_vat_excluded_invalid'] ?? false) === true) {
            return 'El campo is_vat_excluded debe ser 1/0, true/false o si/no.';
        }

        if (($rowPayload['category_invalid'] ?? false) === true) {
            return 'El campo category_id debe contener un ID, slug o nombre de categoría existente.';
        }

        $validator = Validator::make($rowPayload, [
            'action' => ['required', Rule::in(['upsert', 'create', 'update'])],
            'sku' => ['required', 'string', 'max:80'],
            'name' => ['required', 'string', 'max:160'],
            'brand' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:4000'],
            'category_id' => ['required', 'integer', Rule::in(array_map('intval', array_keys($this->categoryIds)))],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'stock' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
            'is_active' => ['nullable', 'boolean'],
            'is_vat_excluded' => ['nullable', 'boolean'],
        ], [
            'action.required' => 'El campo action es obligatorio.',
            'action.in' => 'El campo action debe ser upsert, create o update.',
            'sku.required' => 'El campo sku es obligatorio.',
            'name.required' => 'El campo name es obligatorio.',
            'category_id.required' => 'El campo category_id es obligatorio.',
            'price.required' => 'El campo price es obligatorio.',
        ]);

        if ($validator->fails()) {
            return implode(' ', $validator->errors()->all());
        }

        return null;
    }

    private function normalizeAction(?string $value): string
    {
        $action = strtolower(trim((string) $value));

        if ($action === '') {
            return 'upsert';
        }

        if (in_array($action, ['upsert', 'create', 'update'], true)) {
            return $action;
        }

        return 'upsert';
    }

    private function normalizeHeader(string $value): string
    {
        $header = trim($value);
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;

        return strtolower($header);
    }

    /**
     * @param  array<int, string|null>  $row
     * @param  array<string, int>  $headerIndexes
     */
    private function cell(array $row, array $headerIndexes, string $header): ?string
    {
        $index = $headerIndexes[$header] ?? null;

        if ($index === null) {
            return null;
        }

        return isset($row[$index]) ? trim((string) $row[$index]) : null;
    }

    /**
     * @param  array<int, string|null>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizeNullableString(?string $value): ?string
    {
        $trimmed = trim((string) $value);

        return $trimmed === '' ? null : $trimmed;
    }

    /**
     * @return array{0:int|null,1:bool}
     */
    private function normalizeCategoryReference(?string $value): array
    {
        $trimmed = trim((string) $value);

        if ($trimmed === '') {
            return [null, false];
        }

        if (preg_match('/^-?\d+$/', $trimmed)) {
            $id = (int) $trimmed;

            return isset($this->categoryIds[$id])
                ? [$id, false]
                : [null, true];
        }

        $lookupKey = $this->normalizeLookupKey($trimmed);
        $slugMatch = $this->categoryIdBySlug[$lookupKey] ?? null;

        if (is_int($slugMatch)) {
            return [$slugMatch, false];
        }

        $nameMatch = $this->categoryIdByName[$lookupKey] ?? null;

        if (is_int($nameMatch)) {
            return [$nameMatch, false];
        }

        return [null, true];
    }

    private function normalizeNullableDecimal(?string $value): ?float
    {
        $trimmed = trim((string) $value);

        if ($trimmed === '') {
            return null;
        }

        $normalized = str_replace(' ', '', $trimmed);
        $commaCount = substr_count($normalized, ',');
        $dotCount = substr_count($normalized, '.');

        if ($commaCount > 0 && $dotCount > 0) {
            if (strrpos($normalized, ',') > strrpos($normalized, '.')) {
                $normalized = str_replace('.', '', $normalized);
                $normalized = str_replace(',', '.', $normalized);
            } else {
                $normalized = str_replace(',', '', $normalized);
            }
        } elseif ($commaCount > 0) {
            $normalized = str_replace(',', '.', $normalized);
        }

        if (! is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    /**
     * @return array{0:bool|null,1:bool}
     */
    private function normalizeNullableBoolean(?string $value): array
    {
        $trimmed = strtolower(trim((string) $value));

        if ($trimmed === '') {
            return [null, false];
        }

        if (in_array($trimmed, ['1', 'true', 'si', 'sí', 'yes', 'y'], true)) {
            return [true, false];
        }

        if (in_array($trimmed, ['0', 'false', 'no', 'n'], true)) {
            return [false, false];
        }

        return [null, true];
    }

    private function detectDelimiter(string $absolutePath): string
    {
        $line = '';
        $handle = fopen($absolutePath, 'rb');

        if (is_resource($handle)) {
            $line = (string) fgets($handle);
            fclose($handle);
        }

        $commaCount = substr_count($line, ',');
        $semicolonCount = substr_count($line, ';');

        return $semicolonCount > $commaCount ? ';' : ',';
    }

    /**
     * @param array{
     *   ok:bool,
     *   total_rows:int,
     *   processed_rows:int,
     *   created:int,
     *   updated:int,
     *   skipped:int,
     *   errors:list<array{row:int,sku:string,message:string}>
     * } $report
     */
    private function appendError(array &$report, int $row, string $sku, string $message): void
    {
        if (count($report['errors']) >= self::MAX_ERRORS) {
            return;
        }

        $report['errors'][] = [
            'row' => $row,
            'sku' => $sku,
            'message' => $message,
        ];
    }

    private function findProductBySku(string $sku): ?Product
    {
        if (array_key_exists($sku, $this->productCacheBySku)) {
            return $this->productCacheBySku[$sku];
        }

        $this->productCacheBySku[$sku] = Product::query()->where('sku', $sku)->first();

        return $this->productCacheBySku[$sku];
    }

    /**
     * @param  array<string, int|null>  $lookup
     */
    private function rememberCategoryLookup(array &$lookup, string $key, int $id): void
    {
        if ($key === '') {
            return;
        }

        if (! array_key_exists($key, $lookup)) {
            $lookup[$key] = $id;

            return;
        }

        if ($lookup[$key] !== $id) {
            $lookup[$key] = null;
        }
    }

    private function normalizeLookupKey(string $value): string
    {
        return Str::of($value)
            ->squish()
            ->lower()
            ->toString();
    }

    private function boot(): void
    {
        if ($this->categoryIds !== []) {
            return;
        }

        $categories = Category::query()
            ->orderBy('id')
            ->get(['id', 'slug', 'name']);

        foreach ($categories as $category) {
            $id = (int) $category->id;

            $this->categoryIds[$id] = true;
            $this->rememberCategoryLookup($this->categoryIdBySlug, $this->normalizeLookupKey((string) $category->slug), $id);
            $this->rememberCategoryLookup($this->categoryIdByName, $this->normalizeLookupKey((string) $category->name), $id);
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $report
     */
    private function persistCreate(string $sku, array $attributes, array &$report): void
    {
        $created = Product::query()->create($attributes);
        $this->productCacheBySku[$sku] = $created;
        $report['created']++;
        $report['processed_rows']++;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $report
     */
    private function persistUpdate(Product $existing, array $attributes, array &$report): void
    {
        $existing->update($attributes);
        $this->productCacheBySku[$existing->sku] = $existing->refresh();
        $report['updated']++;
        $report['processed_rows']++;
    }
}
