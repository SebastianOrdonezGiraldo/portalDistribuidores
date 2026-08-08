<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->string('advisor_name_snapshot', 120)->nullable()->after('distributor_tier_snapshot');
            $table->string('advisor_email_snapshot', 120)->nullable()->after('advisor_name_snapshot');
            $table->string('advisor_whatsapp_snapshot', 15)->nullable()->after('advisor_email_snapshot');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'advisor_name_snapshot',
                'advisor_email_snapshot',
                'advisor_whatsapp_snapshot',
            ]);
        });
    }
};
