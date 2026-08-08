<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table): void {
            $table->decimal('reserved_stock', 12, 2)->default(0)->after('stock');
        });

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->decimal('reserved_stock', 12, 2)->default(0)->after('stock');
        });

        Schema::table('orders', function (Blueprint $table): void {
            $table->string('inventory_reconciliation_status', 32)->nullable()->index();
            $table->timestamp('inventory_reconciliation_attempted_at')->nullable();
            $table->timestamp('inventory_reconciled_at')->nullable();
            $table->text('inventory_reconciliation_error')->nullable();
        });

        Schema::create('inventory_holds', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants')->restrictOnDelete();
            $table->string('inventory_key', 64);
            $table->decimal('quantity', 12, 2);
            $table->string('status', 16)->default('active')->index();
            $table->timestamp('released_at')->nullable();
            $table->string('release_reason', 120)->nullable();
            $table->timestamps();

            $table->unique(['order_id', 'inventory_key'], 'inventory_holds_order_inventory_unique');
            $table->index(['product_id', 'status']);
            $table->index(['product_variant_id', 'status']);
        });

        Schema::create('inventory_hold_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('inventory_hold_id')->constrained('inventory_holds')->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 32);
            $table->decimal('previous_quantity', 12, 2)->default(0);
            $table->decimal('new_quantity', 12, 2)->default(0);
            $table->string('reason', 120)->nullable();
            $table->timestamps();

            $table->index(['order_id', 'created_at']);
        });

        $this->backfillSubmittedOrderHolds();
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_hold_events');
        Schema::dropIfExists('inventory_holds');

        Schema::table('orders', function (Blueprint $table): void {
            $table->dropColumn([
                'inventory_reconciliation_status',
                'inventory_reconciliation_attempted_at',
                'inventory_reconciled_at',
                'inventory_reconciliation_error',
            ]);
        });

        Schema::table('product_variants', function (Blueprint $table): void {
            $table->dropColumn('reserved_stock');
        });

        Schema::table('products', function (Blueprint $table): void {
            $table->dropColumn('reserved_stock');
        });
    }

    private function backfillSubmittedOrderHolds(): void
    {
        $now = now();
        $rows = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('orders.status', 'submitted')
            ->whereNotNull('order_items.product_id')
            ->selectRaw('order_items.order_id, order_items.product_id, order_items.product_variant_id, SUM(order_items.qty) as quantity')
            ->groupBy('order_items.order_id', 'order_items.product_id', 'order_items.product_variant_id')
            ->orderBy('order_items.order_id')
            ->get();

        foreach ($rows as $row) {
            $quantity = max(0.0, (float) $row->quantity);

            if ($quantity <= 0.0) {
                continue;
            }

            $variantId = $row->product_variant_id !== null ? (int) $row->product_variant_id : null;
            $inventoryKey = $variantId !== null ? 'variant:'.$variantId : 'product:'.(int) $row->product_id;

            $holdId = DB::table('inventory_holds')->insertGetId([
                'order_id' => (int) $row->order_id,
                'product_id' => (int) $row->product_id,
                'product_variant_id' => $variantId,
                'inventory_key' => $inventoryKey,
                'quantity' => $quantity,
                'status' => 'active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('products')->where('id', (int) $row->product_id)->increment('reserved_stock', $quantity);

            if ($variantId !== null) {
                DB::table('product_variants')->where('id', $variantId)->increment('reserved_stock', $quantity);
            }

            DB::table('inventory_hold_events')->insert([
                'inventory_hold_id' => $holdId,
                'order_id' => (int) $row->order_id,
                'action' => 'backfilled',
                'previous_quantity' => 0,
                'new_quantity' => $quantity,
                'reason' => 'migration_submitted_order',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
};
