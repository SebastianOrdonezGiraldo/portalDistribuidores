<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('catalog_banners', function (Blueprint $table): void {
            $table->string('placement', 32)->default('catalog')->after('title')->index();
        });
    }

    public function down(): void
    {
        Schema::table('catalog_banners', function (Blueprint $table): void {
            $table->dropIndex(['placement']);
            $table->dropColumn('placement');
        });
    }
};
