<?php

namespace Tests\Unit;

use App\Modules\Orders\Pricing\DistributorPriceCalculator;
use App\Modules\Shared\Enums\DistributorTier;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;

class DistributorPriceCalculatorTest extends TestCase
{
    private function calculator(): DistributorPriceCalculator
    {
        return new DistributorPriceCalculator;
    }

    public function test_gold_eligible_uses_manual_gold_price(): void
    {
        $price = $this->calculator()->calculate(10_000_000, 9_500_000, DistributorTier::Gold);

        $this->assertSame(10_000_000, $price->effectivePriceCents);
        $this->assertSame('100000.00', $price->effectivePriceDecimal());
        $this->assertSame('100000.00', $price->basePriceDecimal());
        $this->assertSame('95000.00', $price->silverPriceDecimal());
    }

    public function test_silver_uses_manual_silver_price_without_markup_or_rounding(): void
    {
        $price = $this->calculator()->calculate(9_600_000, 10_012_300, DistributorTier::Silver);

        $this->assertSame('100123.00', $price->silverPriceDecimal());
        $this->assertSame('100123.00', $price->effectivePriceDecimal());
        $this->assertSame(0, $price->unitSavingsCents);
    }

    public function test_manual_prices_are_not_changed_by_legacy_values(): void
    {
        $price = $this->calculator()->calculate(10_000_000, 10_512_345, DistributorTier::Silver);

        $this->assertSame(10_512_345, $price->silverPriceCents);
        $this->assertSame('105123.45', $price->silverPriceDecimal());
    }

    public function test_gold_price_is_selected_only_for_gold_tier(): void
    {
        $gold = $this->calculator()->calculate(10_000_000, 10_500_000, DistributorTier::Gold);
        $silver = $this->calculator()->calculate(10_000_000, 10_500_000, DistributorTier::Silver);

        $this->assertSame(10_000_000, $gold->effectivePriceCents);
        $this->assertSame(10_500_000, $silver->effectivePriceCents);
        $this->assertSame(500_000, $gold->unitSavingsCents);
        $this->assertSame('5000.00', $gold->unitSavingsDecimal());
        $this->assertSame(0, $silver->unitSavingsCents);
    }

    public function test_decimal_prices_are_parsed_from_strings(): void
    {
        $price = $this->calculator()->calculateFromDecimal('100000.00', '105123.45', DistributorTier::Silver);

        $this->assertSame(10_000_000, $price->basePriceCents);
        $this->assertSame(10_512_345, $price->silverPriceCents);
        $this->assertSame('105123.45', $price->effectivePriceDecimal());
    }

    public function test_zero_prices_are_valid(): void
    {
        $price = $this->calculator()->calculate(0, 0, DistributorTier::Silver);

        $this->assertSame(0, $price->basePriceCents);
        $this->assertSame(0, $price->silverPriceCents);
        $this->assertSame(0, $price->effectivePriceCents);
        $this->assertSame(0, $price->unitSavingsCents);
    }

    public function test_decimal_to_cents_avoids_float_drift(): void
    {
        $calculator = $this->calculator();

        $this->assertSame(1010, $calculator->decimalToCents('10.10'));
        $this->assertSame(1000, $calculator->decimalToCents('10'));
        $this->assertSame(1005, $calculator->decimalToCents('10.05'));
        $this->assertSame(0, $calculator->decimalToCents('0.00'));
    }

    public function test_negative_gold_price_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator()->calculate(-100, 200, DistributorTier::Silver);
    }

    public function test_negative_silver_price_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator()->calculate(100, -200, DistributorTier::Silver);
    }

    public function test_public_signatures_do_not_accept_float(): void
    {
        $this->assertParameterDisallowsFloat('calculateFromDecimal', 'goldPrice');
        $this->assertParameterDisallowsFloat('calculateFromDecimal', 'silverPrice');
        $this->assertParameterDisallowsFloat('decimalToCents', 'value');
    }

    private function assertParameterDisallowsFloat(string $method, string $parameter): void
    {
        $reflection = new ReflectionMethod(DistributorPriceCalculator::class, $method);
        $target = null;

        foreach ($reflection->getParameters() as $param) {
            if ($param->getName() === $parameter) {
                $target = $param;
                break;
            }
        }

        $this->assertNotNull($target, "No se encontro el parametro {$parameter} en {$method}.");

        $type = $target->getType();
        $names = [];

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $inner) {
                $names[] = $inner->getName();
            }
        } elseif ($type instanceof ReflectionNamedType) {
            $names[] = $type->getName();
        }

        $this->assertNotContains('float', $names, "La firma de {$method}() no debe aceptar float.");
        $this->assertContains('int', $names);
        $this->assertContains('string', $names);
    }
}
