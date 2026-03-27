<?php

namespace App\Console\Commands;

use App\Modules\Catalog\Models\ProductPhoto;
use App\Modules\Catalog\Services\ProductPhotoVariantService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class BackfillProductPhotoVariants extends Command
{
    protected $signature = 'catalog:backfill-product-photo-variants {--force : Regenera metadata y variantes aunque ya existan}';

    protected $description = 'Completa dimensiones y variantes responsive para fotos de producto existentes.';

    public function handle(ProductPhotoVariantService $productPhotoVariantService): int
    {
        $force = (bool) $this->option('force');
        $stats = [
            'scanned' => 0,
            'updated' => 0,
            'skipped' => 0,
            'missing' => 0,
        ];

        $this->components->info('Analizando fotos de producto...');

        ProductPhoto::query()
            ->orderBy('id')
            ->chunkById(100, function ($photos) use ($productPhotoVariantService, $force, &$stats): void {
                foreach ($photos as $photo) {
                    $stats['scanned']++;

                    if (! Storage::disk('public')->exists($photo->path)) {
                        $stats['missing']++;
                        continue;
                    }

                    if ($productPhotoVariantService->backfillPhoto($photo, $force)) {
                        $stats['updated']++;
                        continue;
                    }

                    $stats['skipped']++;
                }
            });

        $this->table(
            ['Escaneadas', 'Actualizadas', 'Sin cambios', 'Faltantes'],
            [[
                $stats['scanned'],
                $stats['updated'],
                $stats['skipped'],
                $stats['missing'],
            ]],
        );

        $this->components->info('Backfill de fotos completado.');

        return self::SUCCESS;
    }
}
