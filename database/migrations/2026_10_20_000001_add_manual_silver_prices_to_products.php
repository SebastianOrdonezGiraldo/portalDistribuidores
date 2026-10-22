<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'silver_price')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->decimal('silver_price', 12, 2)->nullable()->after('price');
            });
        }

        if (! Schema::hasColumn('product_variants', 'silver_price')) {
            Schema::table('product_variants', function (Blueprint $table): void {
                $table->decimal('silver_price', 12, 2)->nullable()->after('price');
            });
        }

        [$markupBasisPoints, $roundingMultiple] = $this->currentLegacyPricingRule();

        // Only backfill missing values so manually set Silver prices survive a re-run.
        DB::table('product_variants')->whereNull('silver_price')->orderBy('id')->eachById(function (object $variant) use ($markupBasisPoints, $roundingMultiple): void {
            DB::table('product_variants')
                ->where('id', $variant->id)
                ->update(['silver_price' => $this->silverPrice($variant->price, $markupBasisPoints, $roundingMultiple)]);
        });

        DB::table('products')
            ->whereNull('silver_price')
            ->whereExists(function ($query): void {
                $query->select(DB::raw('1'))
                    ->from('product_variants')
                    ->whereColumn('product_variants.product_id', 'products.id')
                    ->where('product_variants.is_active', true);
            })
            ->orderBy('id')
            ->eachById(function (object $product): void {
                $minimum = DB::table('product_variants')
                    ->where('product_id', $product->id)
                    ->where('is_active', true)
                    ->min('silver_price');

                DB::table('products')
                    ->where('id', $product->id)
                    ->update(['silver_price' => $minimum]);
            });

        DB::table('products')->whereNull('silver_price')->orderBy('id')->eachById(function (object $product) use ($markupBasisPoints, $roundingMultiple): void {
            DB::table('products')
                ->where('id', $product->id)
                ->update(['silver_price' => $this->silverPrice($product->price, $markupBasisPoints, $roundingMultiple)]);
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('product_variants', 'silver_price')) {
            Schema::table('product_variants', function (Blueprint $table): void {
                $table->dropColumn('silver_price');
            });
        }

        if (Schema::hasColumn('products', 'silver_price')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->dropColumn('silver_price');
            });
        }
    }

    /** @return array{0:int,1:int} */
    private function currentLegacyPricingRule(): array
    {
        $markupBasisPoints = 500;
        $roundingMultiple = 1000;

        if (Schema::hasTable('commerce_pricing_rules')) {
            $rule = DB::table('commerce_pricing_rules')->latest('id')->first();

            if ($rule !== null) {
                $markupBasisPoints = (int) $rule->silver_markup_basis_points;
                $roundingMultiple = max(1, (int) $rule->silver_rounding_multiple);
            }
        }

        return [$markupBasisPoints, $roundingMultiple];
    }

    private function silverPrice(mixed $basePrice, int $markupBasisPoints, int $roundingMultiple): string
    {
        $baseCents = (int) round(((float) $basePrice) * 100);
        $percentageBase = 10_000;
        $unroundedCents = intdiv(
            ($baseCents * ($percentageBase + $markupBasisPoints)) + $percentageBase - 1,
            $percentageBase,
        );
        $multipleCents = $roundingMultiple * 100;
        $silverCents = intdiv($unroundedCents + $multipleCents - 1, $multipleCents) * $multipleCents;

        return number_format($silverCents / 100, 2, '.', '');
    }
};
