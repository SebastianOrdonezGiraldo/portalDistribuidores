<?php

namespace App\Modules\Catalog\Actions;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductDocument;
use App\Modules\Catalog\Security\SafeUploadValidator;
use App\Modules\Shared\Enums\DocumentType;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AttachProtectedProductDocumentAction
{
    public function __construct(
        private readonly SafeUploadValidator $safeUploadValidator,
    ) {}

    public function execute(
        Product $product,
        UploadedFile $file,
        DocumentType $type,
        string $attribute,
    ): ProductDocument {
        $this->safeUploadValidator->assertSafePdf($file, $attribute, mb_strtolower($type->label()));

        $diskName = (string) config('filesystems.tech_sheets_disk', 'private');
        $newPath = $file->store('products/documents', $diskName);

        if (! is_string($newPath) || $newPath === '') {
            throw new \RuntimeException('No fue posible almacenar el documento protegido.');
        }

        $newFilename = $this->safeUploadValidator->sanitizeOriginalFilename($file, 'pdf');
        $oldPath = null;

        try {
            /** @var ProductDocument $document */
            $document = DB::transaction(function () use ($product, $type, $newPath, $newFilename, &$oldPath): ProductDocument {
                $existingDocument = $product->documents()
                    ->where('type', $type->value)
                    ->first();

                if ($existingDocument) {
                    $oldPath = $existingDocument->path;
                    $existingDocument->fill([
                        'path' => $newPath,
                        'filename' => $newFilename,
                    ])->save();

                    return $existingDocument->refresh();
                }

                return $product->documents()->create([
                    'type' => $type->value,
                    'path' => $newPath,
                    'filename' => $newFilename,
                ]);
            });
        } catch (Throwable $exception) {
            $this->deletePathFromDisks($newPath, [$diskName]);

            throw $exception;
        }

        if ($oldPath && $oldPath !== $newPath) {
            $this->deletePathFromDisks($oldPath, [$diskName, 'public']);
        }

        return $document;
    }

    /**
     * @param list<string> $diskNames
     */
    private function deletePathFromDisks(string $path, array $diskNames): void
    {
        foreach (collect($diskNames)->filter()->unique() as $diskName) {
            $disk = Storage::disk($diskName);

            if ($disk->exists($path)) {
                $disk->delete($path);
            }
        }
    }
}
