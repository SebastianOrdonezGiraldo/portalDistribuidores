<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement(<<<'SQL'
            SELECT setval(
                pg_get_serial_sequence('public.users', 'id'),
                GREATEST(COALESCE((SELECT MAX(id) FROM public.users), 0) + 1, 1),
                false
            );
        SQL);
    }

    public function down(): void
    {
        // no-op
    }
};

