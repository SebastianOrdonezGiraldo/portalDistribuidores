<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_photos', function (Blueprint $table) {
            $table->unsignedInteger('photo_width')->nullable()->after('path');
            $table->unsignedInteger('photo_height')->nullable()->after('photo_width');
        });
    }

    public function down(): void
    {
        Schema::table('product_photos', function (Blueprint $table) {
            $table->dropColumn(['photo_width', 'photo_height']);
        });
    }
};
