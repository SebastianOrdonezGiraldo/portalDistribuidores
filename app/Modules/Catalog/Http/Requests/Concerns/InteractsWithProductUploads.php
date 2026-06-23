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
            'photos.*' => [
                'file',
                'image',
                'mimes:jpg,jpeg,png,gif,webp,avif',
                'mimetypes:image/jpeg,image/png,image/gif,image/webp,image/avif',
                'max:'.ProductUploadLimits::photoMaxSizeKb(),
            ],
            'tech_sheet' => [
                'nullable',
                'file',
                'mimes:pdf',
                'mimetypes:application/pdf,application/x-pdf',
                'max:'.ProductUploadLimits::techSheetMaxSizeKb(),
            ],
            'manual' => [
                'nullable',
                'file',
                'mimes:pdf',
                'mimetypes:application/pdf,application/x-pdf',
                'max:'.ProductUploadLimits::manualMaxSizeKb(),
            ],
            'invima' => [
                'nullable',
                'file',
                'mimes:pdf',
                'mimetypes:application/pdf,application/x-pdf',
                'max:'.ProductUploadLimits::invimaMaxSizeKb(),
            ],
            'quick_guide' => [
                'nullable',
                'file',
                'mimes:pdf',
                'mimetypes:application/pdf,application/x-pdf',
                'max:'.ProductUploadLimits::quickGuideMaxSizeKb(),
            ],
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
            'photos.*.mimes' => 'Las fotos solo admiten JPG, PNG, GIF, WEBP o AVIF.',
            'photos.*.mimetypes' => 'Las fotos solo admiten JPG, PNG, GIF, WEBP o AVIF.',
            'photos.*.max' => 'Cada foto debe pesar como maximo '.ProductUploadLimits::photoMaxSizeLabel().'.',
            'tech_sheet.file' => 'La ficha tecnica debe cargarse como un archivo adjunto.',
            'tech_sheet.uploaded' => ProductUploadLimits::techSheetUploadFailedMessage(),
            'tech_sheet.mimes' => 'La ficha tecnica debe estar en formato PDF.',
            'tech_sheet.mimetypes' => 'La ficha tecnica debe estar en formato PDF.',
            'tech_sheet.max' => 'La ficha tecnica debe pesar como maximo '.ProductUploadLimits::techSheetMaxSizeLabel().'.',
            'manual.file' => 'El manual de usuario debe cargarse como un archivo adjunto.',
            'manual.uploaded' => ProductUploadLimits::manualUploadFailedMessage(),
            'manual.mimes' => 'El manual de usuario debe estar en formato PDF.',
            'manual.mimetypes' => 'El manual de usuario debe estar en formato PDF.',
            'manual.max' => 'El manual de usuario debe pesar como maximo '.ProductUploadLimits::manualMaxSizeLabel().'.',
            'invima.file' => 'El INVIMA debe cargarse como un archivo adjunto.',
            'invima.uploaded' => ProductUploadLimits::invimaUploadFailedMessage(),
            'invima.mimes' => 'El INVIMA debe estar en formato PDF.',
            'invima.mimetypes' => 'El INVIMA debe estar en formato PDF.',
            'invima.max' => 'El INVIMA debe pesar como maximo '.ProductUploadLimits::invimaMaxSizeLabel().'.',
            'quick_guide.file' => 'La guia rapida del producto debe cargarse como un archivo adjunto.',
            'quick_guide.uploaded' => ProductUploadLimits::quickGuideUploadFailedMessage(),
            'quick_guide.mimes' => 'La guia rapida del producto debe estar en formato PDF.',
            'quick_guide.mimetypes' => 'La guia rapida del producto debe estar en formato PDF.',
            'quick_guide.max' => 'La guia rapida del producto debe pesar como maximo '.ProductUploadLimits::quickGuideMaxSizeLabel().'.',
        ];
    }
}
