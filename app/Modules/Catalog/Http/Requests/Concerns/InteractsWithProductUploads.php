<?php

namespace App\Modules\Catalog\Http\Requests\Concerns;

use App\Modules\Catalog\Support\ProductUploadLimits;

trait InteractsWithProductUploads
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function productUploadRules(): array
    {
        return [
            'photos' => ['nullable', 'array', 'max:'.ProductUploadLimits::photoMaxFiles()],
            'photos.*' => ['image', 'max:'.ProductUploadLimits::photoMaxSizeKb()],
            'generated_photo_variants' => ['nullable', 'array'],
            'generated_photo_variants.*' => ['file', 'mimetypes:image/webp', 'max:'.ProductUploadLimits::photoMaxSizeKb()],
            'generated_photo_variants_manifest' => ['nullable', 'string', 'json'],
            'tech_sheet' => ['nullable', 'file', 'mimes:pdf', 'max:'.ProductUploadLimits::techSheetMaxSizeKb()],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function productUploadMessages(): array
    {
        return [
            'photos.max' => 'Puedes cargar hasta '.ProductUploadLimits::photoMaxFiles().' fotos por producto.',
            'photos.*.image' => 'Cada archivo en fotos debe ser una imagen valida.',
            'photos.*.uploaded' => ProductUploadLimits::photoUploadFailedMessage(),
            'photos.*.max' => 'Cada foto debe pesar como maximo '.ProductUploadLimits::photoMaxSizeLabel().'.',
            'generated_photo_variants.*.uploaded' => ProductUploadLimits::photoUploadFailedMessage(),
            'generated_photo_variants.*.mimetypes' => 'Las variantes optimizadas deben estar en formato WEBP.',
            'generated_photo_variants.*.max' => 'Cada variante optimizada debe pesar como maximo '.ProductUploadLimits::photoMaxSizeLabel().'.',
            'generated_photo_variants_manifest.json' => 'No fue posible interpretar la metadata de las variantes optimizadas.',
            'tech_sheet.file' => 'La ficha tecnica debe cargarse como un archivo adjunto.',
            'tech_sheet.uploaded' => ProductUploadLimits::techSheetUploadFailedMessage(),
            'tech_sheet.mimes' => 'La ficha tecnica debe estar en formato PDF.',
            'tech_sheet.max' => 'La ficha tecnica debe pesar como maximo '.ProductUploadLimits::techSheetMaxSizeLabel().'.',
        ];
    }
}
