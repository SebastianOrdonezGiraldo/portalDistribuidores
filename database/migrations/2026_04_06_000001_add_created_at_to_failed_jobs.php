<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('failed_jobs', function (Blueprint $table) {
            // Add created_at column if it doesn't exist
            if (!Schema::hasColumn('failed_jobs', 'created_at')) {
                $table->timestamp('created_at')->useCurrent();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('failed_jobs', function (Blueprint $table) {
            if (Schema::hasColumn('failed_jobs', 'created_at')) {
                $table->dropColumn('created_at');
            }
        });
    }
};
