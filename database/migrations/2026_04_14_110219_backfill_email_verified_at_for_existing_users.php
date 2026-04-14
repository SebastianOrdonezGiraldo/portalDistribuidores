<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Mark existing users as email-verified.
     *
     * These users registered before the OTP verification flow was introduced.
     * They were manually vetted by the admin (distributor status = active) and
     * have operated normally, so their email ownership is considered confirmed.
     *
     * This backfill prevents them from being blocked if MustVerifyEmail is ever
     * enforced on the User model in the future.
     */
    public function up(): void
    {
        DB::table('users')
            ->whereNull('email_verified_at')
            ->update(['email_verified_at' => now()]);
    }

    public function down(): void
    {
        // Intentionally left empty: reverting email_verified_at is destructive
        // and cannot be done safely without knowing the original state.
    }
};
