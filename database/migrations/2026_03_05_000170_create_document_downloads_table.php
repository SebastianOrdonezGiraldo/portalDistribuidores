<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document_downloads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distributor_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_document_id')->constrained('product_documents')->cascadeOnDelete();
            $table->timestamp('downloaded_at');
            $table->timestamps();

            $table->index(['distributor_id', 'product_document_id', 'downloaded_at'], 'document_downloads_lookup_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_downloads');
    }
};
