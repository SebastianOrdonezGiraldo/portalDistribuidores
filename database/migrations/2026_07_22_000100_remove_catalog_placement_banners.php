<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('catalog_banners')) {
            return;
        }

        $catalogBannerPaths = DB::table('catalog_banners')
            ->where('placement', 'catalog')
            ->pluck('path');

        DB::table('catalog_banners')
            ->where('placement', 'catalog')
            ->delete();

        foreach ($catalogBannerPaths as $path) {
            if (is_string($path) && $path !== '' && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    public function down(): void
    {
        // Los banners del catálogo eliminados no se restauran automáticamente.
    }
};
