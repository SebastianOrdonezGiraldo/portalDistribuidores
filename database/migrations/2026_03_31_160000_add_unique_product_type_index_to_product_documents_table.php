<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('product_documents')
            ->select('product_id', 'type')
            ->groupBy('product_id', 'type')
            ->havingRaw('COUNT(*) > 1')
            ->get()
            ->each(function (object $group): void {
                $duplicateIds = DB::table('product_documents')
                    ->where('product_id', $group->product_id)
                    ->where('type', $group->type)
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->skip(1)
                    ->pluck('id');

                if ($duplicateIds->isNotEmpty()) {
                    DB::table('product_documents')->whereIn('id', $duplicateIds)->delete();
                }
            });

        Schema::table('product_documents', function (Blueprint $table) {
            $table->unique(['product_id', 'type'], 'product_documents_product_id_type_unique');
        });
    }

    public function down(): void
    {
        Schema::table('product_documents', function (Blueprint $table) {
            $table->dropUnique('product_documents_product_id_type_unique');
        });
    }
};
