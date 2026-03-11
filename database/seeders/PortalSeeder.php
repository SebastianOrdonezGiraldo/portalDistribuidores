<?php

namespace Database\Seeders;

use App\Modules\Catalog\Models\Product;
use App\Modules\Categories\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PortalSeeder extends Seeder
{
    public function run(): void
    {
        $categories = $this->seedCategories();
        $this->seedProducts($categories);
    }

    /**
     * @return array<string, Category>
     */
    private function seedCategories(): array
    {
        $categories = [];

        $categories['insumos'] = $this->upsertCategory('Insumos Médicos', null, 1, ['insumos', 'material medico']);
        $categories['proteccion'] = $this->upsertCategory('Protección', $categories['insumos']->id, 1, ['seguridad', 'barrera']);
        $categories['diagnostico'] = $this->upsertCategory('Diagnóstico', $categories['insumos']->id, 2, ['medicion', 'revision']);
        $categories['rehabilitacion'] = $this->upsertCategory('Rehabilitación', null, 2, ['terapia', 'recuperacion']);
        $categories['instrumental'] = $this->upsertCategory('Instrumental', null, 3, ['herramientas', 'equipo']);

        return $categories;
    }

    private function upsertCategory(string $name, ?int $parentId, int $sortOrder, array $synonyms): Category
    {
        $category = Category::updateOrCreate(
            ['slug' => Str::slug($name)],
            [
                'parent_id' => $parentId,
                'name' => $name,
                'is_active' => true,
                'sort_order' => $sortOrder,
            ],
        );

        $category->synonyms()->delete();

        foreach ($synonyms as $term) {
            $category->synonyms()->create(['term' => $term]);
        }

        return $category;
    }

    /**
     * @param array<string, Category> $categories
     */
    private function seedProducts(array $categories): void
    {
        $products = [
            ['Guantes de Nitrilo Talla M', 'ICM-GUA-001', 'Guante desechable de alta resistencia.', 'proteccion', 42000],
            ['Mascarilla Quirúrgica 3 Capas', 'ICM-MAS-002', 'Mascarilla de uso clínico.', 'proteccion', 18000],
            ['Termómetro Infrarrojo', 'ICM-TER-003', 'Lectura rápida sin contacto.', 'diagnostico', 149000],
            ['Oxímetro de Pulso', 'ICM-OXI-004', 'Monitoreo de saturación y pulso.', 'diagnostico', 118000],
            ['Banda Elástica Rehabilitación', 'ICM-BAN-005', 'Banda terapéutica de resistencia media.', 'rehabilitacion', 25000],
            ['Balón Terapéutico 65cm', 'ICM-BAL-006', 'Balón para ejercicios de estabilidad.', 'rehabilitacion', 78000],
            ['Pinza Kelly Curva', 'ICM-PIN-007', 'Instrumental quirúrgico en acero inoxidable.', 'instrumental', 56000],
            ['Tijera Mayo Recta', 'ICM-TIJ-008', 'Corte preciso para procedimientos.', 'instrumental', 61000],
            ['Jeringa 10ml Luer', 'ICM-JER-009', 'Jeringa estéril de un solo uso.', 'insumos', 9000],
            ['Venda Elástica 5cm', 'ICM-VEN-010', 'Soporte compresivo para vendajes.', 'insumos', 11000],
        ];

        Storage::disk('public')->makeDirectory('products/photos');
        Storage::disk('public')->makeDirectory('products/documents');

        $pdfSeeded = 0;

        foreach ($products as $index => [$name, $sku, $description, $categoryKey, $price]) {
            $product = Product::updateOrCreate(
                ['sku' => $sku],
                [
                    'name' => $name,
                    'brand' => 'Import Corporal Medical',
                    'description' => $description,
                    'category_id' => $categories[$categoryKey]->id,
                    'price' => $price,
                    'is_active' => true,
                ],
            );

            $photoPath = "products/photos/{$sku}.svg";
            Storage::disk('public')->put($photoPath, $this->svgPlaceholder($name));
            $product->photos()->updateOrCreate(
                ['path' => $photoPath],
                ['is_primary' => true, 'sort_order' => 1],
            );
            $product->photos()->where('path', '!=', $photoPath)->update(['is_primary' => false]);

            $product->videos()->updateOrCreate(
                ['url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
                ['sort_order' => 1],
            );

            if ($pdfSeeded < 5) {
                $documentPath = "products/documents/techsheet-{$sku}.pdf";
                Storage::disk('public')->put($documentPath, $this->dummyPdf($name));
                $product->documents()->updateOrCreate(
                    ['type' => 'tech_sheet', 'path' => $documentPath],
                    ['filename' => "Ficha-{$sku}.pdf"],
                );
                $pdfSeeded++;
            }
        }
    }

    private function svgPlaceholder(string $text): string
    {
        $safe = e($text);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="800" height="520">
  <rect width="100%" height="100%" fill="#eef6df" />
  <rect x="24" y="24" width="752" height="472" rx="20" fill="#ffffff" stroke="#8DC543" stroke-width="4" />
  <text x="50%" y="48%" text-anchor="middle" fill="#BC2983" font-family="Arial, sans-serif" font-size="36" font-weight="700">{$safe}</text>
  <text x="50%" y="58%" text-anchor="middle" fill="#5f6b7a" font-family="Arial, sans-serif" font-size="20">Import Corporal Medical SAS</text>
</svg>
SVG;
    }

    private function dummyPdf(string $title): string
    {
        $safe = str_replace(['(', ')'], '', $title);

        return "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n2 0 obj<</Type/Pages/Count 1/Kids[3 0 R]>>endobj\n3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 595 842]/Contents 4 0 R/Resources<</Font<</F1 5 0 R>>>>>>endobj\n4 0 obj<</Length 93>>stream\nBT\n/F1 18 Tf\n50 780 Td\n(Ficha Tecnica {$safe}) Tj\n0 -30 Td\n/F1 12 Tf\n(Import Corporal Medical SAS) Tj\nET\nendstream\nendobj\n5 0 obj<</Type/Font/Subtype/Type1/BaseFont/Helvetica>>endobj\nxref\n0 6\n0000000000 65535 f \n0000000010 00000 n \n0000000058 00000 n \n0000000115 00000 n \n0000000244 00000 n \n0000000388 00000 n \ntrailer<</Root 1 0 R/Size 6>>\nstartxref\n462\n%%EOF";
    }
}
