<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('distributors', function (Blueprint $table) {
            $table->timestamp('portal_tour_completed_at')->nullable();
        });

        // The automatic welcome is for companies registered after this feature ships.
        DB::table('distributors')->update(['portal_tour_completed_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('distributors', function (Blueprint $table) {
            $table->dropColumn('portal_tour_completed_at');
        });
    }
};
