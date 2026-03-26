<?php

namespace App\Console\Commands;

use App\Modules\Catalog\Models\ProductDocument;
use App\Modules\Orders\Models\Order;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class MigrateProtectedMedia extends Command
{
    protected $signature = 'protected-media:migrate {--keep-public : Conserva las copias legacy del disco public}';

    protected $description = 'Mueve fichas tecnicas y PDFs de pedidos desde el disco public hacia almacenamiento privado.';

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
            'tech_sheets_copied' => 0,
            'tech_sheets_deleted' => 0,
            'tech_sheets_missing' => 0,
            'order_pdfs_copied' => 0,
            'order_pdfs_deleted' => 0,
            'order_pdfs_missing' => 0,
        ];

        $this->components->info('Migrando fichas tecnicas protegidas...');
        ProductDocument::query()
            ->where('type', 'tech_sheet')
            ->whereNotNull('path')
            ->orderBy('id')
            ->chunkById(100, function ($documents) use (&$stats, $techSheetDisk, $keepPublic): void {
                foreach ($documents as $document) {
                    $result = $this->syncPath((string) $document->path, $techSheetDisk, $keepPublic);

                    if ($result['copied']) {
                        $stats['tech_sheets_copied']++;
                    }

                    if ($result['deleted_public']) {
                        $stats['tech_sheets_deleted']++;
                    }

                    if ($result['missing']) {
                        $stats['tech_sheets_missing']++;
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
                ['Fichas tecnicas', $stats['tech_sheets_copied'], $stats['tech_sheets_deleted'], $stats['tech_sheets_missing']],
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
        $missing = ! $targetDisk->exists($path) && ! $legacyDisk->exists($path);

        if ($missing) {
            return [
                'copied' => false,
                'deleted_public' => false,
                'missing' => true,
            ];
        }

        if (! $targetDisk->exists($path) && $legacyDisk->exists($path)) {
            $written = $targetDisk->put($path, (string) $legacyDisk->get($path));

            if ($written !== false) {
                $copied = true;
            }
        }

        if (! $keepPublic && $legacyDisk->exists($path) && $targetDisk->exists($path)) {
            $legacyDisk->delete($path);
            $deletedPublic = true;
        }

        return [
            'copied' => $copied,
            'deleted_public' => $deletedPublic,
            'missing' => false,
        ];
    }
}
