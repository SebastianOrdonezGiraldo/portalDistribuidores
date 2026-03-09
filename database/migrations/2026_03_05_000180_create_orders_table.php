<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('distributor_id')->constrained()->restrictOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('oc_number')->unique();
            $table->string('contact_name');
            $table->string('company_name');
            $table->string('phone');
            $table->text('notes')->nullable();
            $table->string('status')->default('draft')->index();
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->string('pdf_path')->nullable();
            $table->timestamps();

            $table->index(['distributor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};

