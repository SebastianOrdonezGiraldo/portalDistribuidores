<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_photos', function (Blueprint $table) {
            $table->unsignedInteger('width')->nullable()->after('path');
            $table->unsignedInteger('height')->nullable()->after('width');
            $table->json('variants')->nullable()->after('height');
        });
    }

    public function down(): void
    {
        Schema::table('product_photos', function (Blueprint $table) {
            $table->dropColumn(['width', 'height', 'variants']);
        });
    }
};
