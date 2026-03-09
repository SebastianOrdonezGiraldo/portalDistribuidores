<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_synonyms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('term');
            $table->timestamps();

            $table->unique(['category_id', 'term']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_synonyms');
    }
};

