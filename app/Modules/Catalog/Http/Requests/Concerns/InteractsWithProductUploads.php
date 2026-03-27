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
            'tech_sheet.file' => 'La ficha tecnica debe cargarse como un archivo adjunto.',
            'tech_sheet.uploaded' => ProductUploadLimits::techSheetUploadFailedMessage(),
            'tech_sheet.mimes' => 'La ficha tecnica debe estar en formato PDF.',
            'tech_sheet.max' => 'La ficha tecnica debe pesar como maximo '.ProductUploadLimits::techSheetMaxSizeLabel().'.',
        ];
    }
}
