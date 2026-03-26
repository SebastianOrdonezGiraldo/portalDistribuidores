<?php

namespace App\Modules\Catalog\Support;

class ProductUploadLimits
{
    public static function photoMaxFiles(): int
    {
        return max(1, (int) config('product_uploads.photos.max_files', 10));
    }

    public static function photoMaxSizeKb(): int
    {
        return max(1, (int) config('product_uploads.photos.max_size_kb', 3072));
    }

    public static function techSheetMaxSizeKb(): int
    {
        return max(1, (int) config('product_uploads.tech_sheet.max_size_kb', 5120));
    }

    public static function requestMaxKb(): int
    {
        return max(1, (int) config('product_uploads.request_max_kb', 40960));
    }

    public static function photoMaxSizeLabel(): string
    {
        return self::formatKilobytes(self::photoMaxSizeKb());
    }

    public static function techSheetMaxSizeLabel(): string
    {
        return self::formatKilobytes(self::techSheetMaxSizeKb());
    }

    public static function requestMaxSizeLabel(): string
    {
        return self::formatKilobytes(self::requestMaxKb());
    }

    public static function totalSizeExceededMessage(): string
    {
        return 'Los archivos seleccionados superan el tamano maximo permitido para la carga total ('
            .self::requestMaxSizeLabel()
            .'). Reduce la cantidad o el peso de fotos y documentos e intentalo nuevamente.';
    }

    public static function photoUploadFailedMessage(): string
    {
        return 'Una de las fotos no se pudo cargar porque supera el limite del servidor. '
            .'Reduce cada imagen a maximo '.self::photoMaxSizeLabel().' e intentalo nuevamente.';
    }

    public static function techSheetUploadFailedMessage(): string
    {
        return 'La ficha tecnica no se pudo cargar porque supera el limite del servidor. '
            .'Reduce el archivo a maximo '.self::techSheetMaxSizeLabel().' e intentalo nuevamente.';
    }

    /**
     * @return array{
     *     photo_max_files: int,
     *     photo_max_size_kb: int,
     *     photo_max_size_label: string,
     *     tech_sheet_max_size_kb: int,
     *     tech_sheet_max_size_label: string,
     *     request_max_kb: int,
     *     request_max_size_label: string
     * }
     */
    public static function viewData(): array
    {
        return [
            'photo_max_files' => self::photoMaxFiles(),
            'photo_max_size_kb' => self::photoMaxSizeKb(),
            'photo_max_size_label' => self::photoMaxSizeLabel(),
            'tech_sheet_max_size_kb' => self::techSheetMaxSizeKb(),
            'tech_sheet_max_size_label' => self::techSheetMaxSizeLabel(),
            'request_max_kb' => self::requestMaxKb(),
            'request_max_size_label' => self::requestMaxSizeLabel(),
        ];
    }

    private static function formatKilobytes(int $kilobytes): string
    {
        $megabytes = $kilobytes / 1024;

        if (abs($megabytes - floor($megabytes)) < 0.00001) {
            return number_format($megabytes, 0, ',', '.').' MB';
        }

        return number_format($megabytes, 1, ',', '.').' MB';
    }
}
