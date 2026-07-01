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

    public static function manualMaxSizeKb(): int
    {
        return max(1, (int) config('product_uploads.manual.max_size_kb', 20480));
    }

    public static function invimaMaxSizeKb(): int
    {
        return max(1, (int) config('product_uploads.invima.max_size_kb', 5120));
    }

    public static function quickGuideMaxSizeKb(): int
    {
        return max(1, (int) config('product_uploads.quick_guide.max_size_kb', 5120));
    }

    public static function calibrationDocumentMaxSizeKb(): int
    {
        return max(1, (int) config('product_uploads.calibration_document.max_size_kb', 5120));
    }

    public static function requestMaxKb(): int
    {
        return max(1, (int) config('product_uploads.request_max_kb', 61440));
    }

    public static function photoMaxSizeLabel(): string
    {
        return self::formatKilobytes(self::photoMaxSizeKb());
    }

    public static function techSheetMaxSizeLabel(): string
    {
        return self::formatKilobytes(self::techSheetMaxSizeKb());
    }

    public static function manualMaxSizeLabel(): string
    {
        return self::formatKilobytes(self::manualMaxSizeKb());
    }

    public static function invimaMaxSizeLabel(): string
    {
        return self::formatKilobytes(self::invimaMaxSizeKb());
    }

    public static function quickGuideMaxSizeLabel(): string
    {
        return self::formatKilobytes(self::quickGuideMaxSizeKb());
    }

    public static function calibrationDocumentMaxSizeLabel(): string
    {
        return self::formatKilobytes(self::calibrationDocumentMaxSizeKb());
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

    public static function manualUploadFailedMessage(): string
    {
        return 'El manual de usuario no se pudo cargar porque supera el limite del servidor. '
            .'Reduce el archivo a maximo '.self::manualMaxSizeLabel().' e intentalo nuevamente.';
    }

    public static function invimaUploadFailedMessage(): string
    {
        return 'El INVIMA no se pudo cargar porque supera el limite del servidor. '
            .'Reduce el archivo a maximo '.self::invimaMaxSizeLabel().' e intentalo nuevamente.';
    }

    public static function quickGuideUploadFailedMessage(): string
    {
        return 'La guia rapida del producto no se pudo cargar porque supera el limite del servidor. '
            .'Reduce el archivo a maximo '.self::quickGuideMaxSizeLabel().' e intentalo nuevamente.';
    }

    public static function calibrationDocumentUploadFailedMessage(): string
    {
        return 'El documento de calibracion no se pudo cargar porque supera el limite del servidor. '
            .'Reduce el archivo a maximo '.self::calibrationDocumentMaxSizeLabel().' e intentalo nuevamente.';
    }

    /**
     * @return array{
     *     photo_max_files: int,
     *     photo_max_size_kb: int,
     *     photo_max_size_label: string,
     *     tech_sheet_max_size_kb: int,
     *     tech_sheet_max_size_label: string,
     *     manual_max_size_kb: int,
     *     manual_max_size_label: string,
     *     invima_max_size_kb: int,
     *     invima_max_size_label: string,
     *     quick_guide_max_size_kb: int,
     *     quick_guide_max_size_label: string,
     *     calibration_document_max_size_kb: int,
     *     calibration_document_max_size_label: string,
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
            'manual_max_size_kb' => self::manualMaxSizeKb(),
            'manual_max_size_label' => self::manualMaxSizeLabel(),
            'invima_max_size_kb' => self::invimaMaxSizeKb(),
            'invima_max_size_label' => self::invimaMaxSizeLabel(),
            'quick_guide_max_size_kb' => self::quickGuideMaxSizeKb(),
            'quick_guide_max_size_label' => self::quickGuideMaxSizeLabel(),
            'calibration_document_max_size_kb' => self::calibrationDocumentMaxSizeKb(),
            'calibration_document_max_size_label' => self::calibrationDocumentMaxSizeLabel(),
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
