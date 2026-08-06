<?php

use Illuminate\Database\Migrations\Migration;

/**
 * Historical no-op.
 *
 * An earlier revision of this migration published commercial minimums that
 * conflicted with the definitive Oro threshold rules. Admin-published rules
 * are the source of truth; do not overwrite them here.
 */
return new class extends Migration
{
    public function up(): void
    {
        //
    }

    public function down(): void
    {
        //
    }
};
