<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->timestamp('privacy_accepted_at')->nullable()->after('email_verification_code_expires_at');
            $table->string('privacy_policy_version', 32)->nullable()->after('privacy_accepted_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['privacy_accepted_at', 'privacy_policy_version']);
        });
    }
};
