<?php

namespace App\Console\Commands;

use App\Modules\Catalog\Models\ProductDocument;
use App\Modules\Orders\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\UnableToCheckExistence;
use Throwable;

class MigrateProtectedMedia extends Command
{
    protected $signature = 'protected-media:migrate {--keep-public : Conserva las copias legacy del disco public}';

    protected $description = 'Mueve documentos protegidos de producto y PDFs de pedidos desde el disco public hacia almacenamiento privado.';

    public function handle(): int
    {
        $orderDisk = (string) config('filesystems.order_pdfs_disk', 'private');
        $techSheetDisk = (string) config('filesystems.tech_sheets_disk', 'private');

        foreach (['order_pdfs_disk' => $orderDisk, 'tech_sheets_disk' => $techSheetDisk] as $label => $diskName) {
            if ($diskName === 'public') {
                $this->components->error("{$label} no puede apuntar al disco public.");

                return self::FAILURE;
            }
        }

        $keepPublic = (bool) $this->option('keep-public');
        $stats = [
            'product_documents_copied' => 0,
            'product_documents_deleted' => 0,
            'product_documents_missing' => 0,
            'order_pdfs_copied' => 0,
            'order_pdfs_deleted' => 0,
            'order_pdfs_missing' => 0,
        ];

        $this->components->info('Migrando documentos protegidos de producto...');
        ProductDocument::query()
            ->whereIn('type', ['tech_sheet', 'manual'])
            ->whereNotNull('path')
            ->orderBy('id')
            ->chunkById(100, function ($documents) use (&$stats, $techSheetDisk, $keepPublic): void {
                foreach ($documents as $document) {
                    $result = $this->syncPath((string) $document->path, $techSheetDisk, $keepPublic);

                    if ($result['copied']) {
                        $stats['product_documents_copied']++;
                    }

                    if ($result['deleted_public']) {
                        $stats['product_documents_deleted']++;
                    }

                    if ($result['missing']) {
                        $stats['product_documents_missing']++;
                    }
                }
            });

        $this->components->info('Migrando PDFs de pedidos protegidos...');
        Order::query()
            ->whereNotNull('pdf_path')
            ->orderBy('id')
            ->chunkById(100, function ($orders) use (&$stats, $orderDisk, $keepPublic): void {
                foreach ($orders as $order) {
                    $result = $this->syncPath((string) $order->pdf_path, $orderDisk, $keepPublic);

                    if ($result['copied']) {
                        $stats['order_pdfs_copied']++;
                    }

                    if ($result['deleted_public']) {
                        $stats['order_pdfs_deleted']++;
                    }

                    if ($result['missing']) {
                        $stats['order_pdfs_missing']++;
                    }
                }
            });

        $this->table(
            ['Activo', 'Copiados', 'Eliminados de public', 'Faltantes'],
            [
                ['Documentos de producto', $stats['product_documents_copied'], $stats['product_documents_deleted'], $stats['product_documents_missing']],
                ['PDFs de pedidos', $stats['order_pdfs_copied'], $stats['order_pdfs_deleted'], $stats['order_pdfs_missing']],
            ],
        );

        $this->components->info('Migracion de media protegida finalizada.');

        return self::SUCCESS;
    }

    /**
     * @return array{copied: bool, deleted_public: bool, missing: bool}
     */
    private function syncPath(string $path, string $targetDiskName, bool $keepPublic): array
    {
        $targetDisk = Storage::disk($targetDiskName);
        $legacyDisk = Storage::disk('public');

        $copied = false;
        $deletedPublic = false;
        $targetExists = $this->pathExists($targetDisk, $path);
        $legacyExists = $this->pathExists($legacyDisk, $path);
        $missing = ! $targetExists && ! $legacyExists;

        if ($missing) {
            return [
                'copied' => false,
                'deleted_public' => false,
                'missing' => true,
            ];
        }

        if (! $targetExists && $legacyExists) {
            $written = $targetDisk->put($path, (string) $legacyDisk->get($path));

            if ($written !== false) {
                $copied = true;
                $targetExists = true;
            }
        }

        if (! $keepPublic && $legacyExists && $targetExists) {
            $legacyDisk->delete($path);
            $deletedPublic = true;
        }

        return [
            'copied' => $copied,
            'deleted_public' => $deletedPublic,
            'missing' => false,
        ];
    }

    private function pathExists($disk, string $path): bool
    {
        try {
            return $disk->exists($path);
        } catch (UnableToCheckExistence $exception) {
            if ($this->wasMissingObjectReportedAsCheckFailure($exception)) {
                return false;
            }

            throw $exception;
        }
    }

    private function wasMissingObjectReportedAsCheckFailure(Throwable $exception): bool
    {
        do {
            $message = $exception->getMessage();

            if (
                str_contains($message, 'NoSuchKey')
                || str_contains($message, '404 Not Found')
                || str_contains($message, 'The specified key does not exist')
            ) {
                return true;
            }

            $exception = $exception->getPrevious();
        } while ($exception instanceof Throwable);

        return false;
    }
}
