<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_status', 32)->default('not_applicable')->after('status');
            $table->string('payment_method', 32)->nullable()->after('payment_status');
            $table->string('payment_receipt_path')->nullable()->after('payment_method');
            $table->string('payment_receipt_filename')->nullable()->after('payment_receipt_path');
            $table->timestamp('payment_receipt_uploaded_at')->nullable()->after('payment_receipt_filename');
            $table->timestamp('payment_reservation_expires_at')->nullable()->after('payment_receipt_uploaded_at');

            $table->index(['payment_status', 'payment_reservation_expires_at'], 'orders_payment_status_expires_idx');
        });

        Schema::create('payment_upload_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('consumed_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'expires_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_upload_tokens');

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_payment_status_expires_idx');
            $table->dropColumn([
                'payment_status',
                'payment_method',
                'payment_receipt_path',
                'payment_receipt_filename',
                'payment_receipt_uploaded_at',
                'payment_reservation_expires_at',
            ]);
        });
    }
};
