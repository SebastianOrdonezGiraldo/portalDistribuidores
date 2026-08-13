<?php

namespace Tests;

use App\Modules\Catalog\Models\Product;
use App\Modules\Catalog\Models\ProductVariant;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Product::creating(function (Product $product): void {
            if (array_key_exists('silver_price', $product->getAttributes())) {
                return;
            }

            $product->setAttribute('silver_price', self::legacySilverPrice($product->getAttribute('price')));
        });

        ProductVariant::creating(function (ProductVariant $variant): void {
            if (array_key_exists('silver_price', $variant->getAttributes())) {
                return;
            }

            $variant->setAttribute('silver_price', self::legacySilverPrice($variant->getAttribute('price')));
        });
    }

    private static function legacySilverPrice(mixed $goldPrice): string
    {
        $goldCents = (int) round(((float) $goldPrice) * 100);
        $candidate = $goldCents + intdiv($goldCents * 500, 10_000);
        $silverCents = intdiv($candidate + 100_000 - 1, 100_000) * 100_000;

        return number_format($silverCents / 100, 2, '.', '');
    }
}
