<?php

namespace App\Modules\Catalog\Security;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SafeUploadValidator
{
    /**
     * @var list<string>
     */
    private array $allowedImageMimeTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/avif',
    ];

    /**
     * @var list<string>
     */
    private array $allowedPdfMimeTypes = [
        'application/pdf',
        'application/x-pdf',
    ];

    public function assertSafeImage(UploadedFile $file, string $attribute = 'photos'): void
    {
        $mimeType = Str::lower((string) $file->getMimeType());

        if (! in_array($mimeType, $this->allowedImageMimeTypes, true)) {
            $this->throwValidationError($attribute, 'El archivo de imagen no tiene un formato permitido.');
        }

        $realPath = $file->getRealPath();

        if (! is_string($realPath) || $realPath === '' || ! is_file($realPath)) {
            $this->throwValidationError($attribute, 'No fue posible validar el archivo de imagen.');
        }

        if (@getimagesize($realPath) === false) {
            $this->throwValidationError($attribute, 'El archivo no contiene una imagen valida.');
        }
    }

    public function assertSafePdf(
        UploadedFile $file,
        string $attribute = 'tech_sheet',
        string $label = 'documento PDF',
    ): void
    {
        $normalizedLabel = trim($label) !== '' ? $label : 'documento PDF';
        $mimeType = Str::lower((string) $file->getMimeType());

        if (! in_array($mimeType, $this->allowedPdfMimeTypes, true)) {
            $this->throwValidationError($attribute, 'El archivo de '.$normalizedLabel.' debe estar en formato PDF.');
        }

        $realPath = $file->getRealPath();

        if (! is_string($realPath) || $realPath === '' || ! is_file($realPath)) {
            $this->throwValidationError($attribute, 'No fue posible validar el archivo de '.$normalizedLabel.'.');
        }

        $signature = @file_get_contents($realPath, false, null, 0, 5);

        if (! is_string($signature) || $signature !== '%PDF-') {
            $this->throwValidationError($attribute, 'El contenido del archivo no corresponde a un PDF valido.');
        }
    }

    public function sanitizeOriginalFilename(UploadedFile $file, string $fallbackExtension = 'pdf'): string
    {
        $originalName = (string) $file->getClientOriginalName();
        $baseName = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = Str::lower((string) pathinfo($originalName, PATHINFO_EXTENSION));

        $safeBaseName = Str::of($baseName)
            ->ascii()
            ->replaceMatches('/[^A-Za-z0-9._-]+/', '-')
            ->trim('-_. ')
            ->substr(0, 80)
            ->toString();

        if ($safeBaseName === '') {
            $safeBaseName = 'archivo';
        }

        if (preg_match('/^[a-z0-9]{1,10}$/', $extension) !== 1) {
            $extension = $fallbackExtension;
        }

        return "{$safeBaseName}.{$extension}";
    }

    private function throwValidationError(string $attribute, string $message): never
    {
        throw ValidationException::withMessages([
            $attribute => $message,
        ]);
    }
}
