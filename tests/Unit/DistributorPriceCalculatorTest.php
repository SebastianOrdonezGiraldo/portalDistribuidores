<?php

namespace Tests\Unit;

use App\Modules\Orders\Pricing\CommercePricingRules;
use App\Modules\Orders\Pricing\DistributorPriceCalculator;
use App\Modules\Shared\Enums\DistributorTier;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;

class DistributorPriceCalculatorTest extends TestCase
{
    private function calculator(int $basisPoints = 500, int $rounding = 1000): DistributorPriceCalculator
    {
        return new DistributorPriceCalculator(new CommercePricingRules($basisPoints, $rounding));
    }

    public function test_gold_uses_base_price(): void
    {
        $price = $this->calculator()->calculate(10_000_000, DistributorTier::Gold);

        $this->assertSame(10_000_000, $price->effectivePriceCents);
        $this->assertSame('100000.00', $price->effectivePriceDecimal());
        $this->assertSame('100000.00', $price->basePriceDecimal());
    }

    public function test_silver_uses_calculated_price_with_rounding_up(): void
    {
        // 96.000 + 5% = 100.800 -> techo a 101.000
        $price = $this->calculator()->calculate(9_600_000, DistributorTier::Silver);

        $this->assertSame('101000.00', $price->silverPriceDecimal());
        $this->assertSame('101000.00', $price->effectivePriceDecimal());
    }

    public function test_exact_multiple_is_not_incremented(): void
    {
        // 100.000 + 5% = 105.000 exacto -> no debe subir a 106.000
        $price = $this->calculator()->calculate(10_000_000, DistributorTier::Silver);

        $this->assertSame('105000.00', $price->silverPriceDecimal());
    }

    public function test_seven_percent_markup_calculates_correctly(): void
    {
        // 100.000 + 7% = 107.000 exacto
        $price = $this->calculator(700, 1000)->calculate(10_000_000, DistributorTier::Silver);

        $this->assertSame('107000.00', $price->silverPriceDecimal());
    }

    public function test_six_point_fifty_percent_via_basis_points(): void
    {
        // 100.000 + 6.50% = 106.500 -> techo a 107.000 con múltiplo 1000
        $price = $this->calculator(650, 1000)->calculate(10_000_000, DistributorTier::Silver);

        $this->assertSame('107000.00', $price->silverPriceDecimal());
    }

    public function test_rounding_to_100(): void
    {
        // 96.000 + 5% = 100.800 -> techo a 100.800 (ya múltiplo de 100)
        $price = $this->calculator(500, 100)->calculate(9_600_000, DistributorTier::Silver);

        $this->assertSame('100800.00', $price->silverPriceDecimal());
    }

    public function test_rounding_to_500(): void
    {
        // 96.000 + 5% = 100.800 -> techo a 101.000
        $price = $this->calculator(500, 500)->calculate(9_600_000, DistributorTier::Silver);

        $this->assertSame('101000.00', $price->silverPriceDecimal());
    }

    public function test_rounding_to_1000(): void
    {
        $price = $this->calculator(500, 1000)->calculate(9_600_000, DistributorTier::Silver);

        $this->assertSame('101000.00', $price->silverPriceDecimal());
    }

    public function test_decimal_base_price_is_parsed_from_string(): void
    {
        $price = $this->calculator()->calculateFromDecimal('100000.00', DistributorTier::Silver);

        $this->assertSame(10_000_000, $price->basePriceCents);
        $this->assertSame('105000.00', $price->silverPriceDecimal());
    }

    public function test_zero_price_produces_zero_everywhere(): void
    {
        $price = $this->calculator()->calculate(0, DistributorTier::Silver);

        $this->assertSame(0, $price->basePriceCents);
        $this->assertSame(0, $price->silverPriceCents);
        $this->assertSame(0, $price->effectivePriceCents);
        $this->assertSame(0, $price->unitSavingsCents);
    }

    public function test_savings_are_correct_for_each_tier(): void
    {
        $gold = $this->calculator()->calculate(10_000_000, DistributorTier::Gold);
        $silver = $this->calculator()->calculate(10_000_000, DistributorTier::Silver);

        $this->assertSame(500_000, $gold->unitSavingsCents);
        $this->assertSame('5000.00', $gold->unitSavingsDecimal());
        $this->assertSame(0, $silver->unitSavingsCents);
    }

    public function test_decimal_to_cents_avoids_float_drift(): void
    {
        $calculator = $this->calculator();

        $this->assertSame(1010, $calculator->decimalToCents('10.10'));
        $this->assertSame(1000, $calculator->decimalToCents('10'));
        $this->assertSame(1005, $calculator->decimalToCents('10.05'));
        $this->assertSame(0, $calculator->decimalToCents('0.00'));
    }

    public function test_negative_base_price_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->calculator()->calculate(-100, DistributorTier::Silver);
    }

    public function test_negative_markup_basis_points_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DistributorPriceCalculator(new CommercePricingRules(-1, 1000));
    }

    public function test_invalid_rounding_multiple_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new DistributorPriceCalculator(new CommercePricingRules(500, 0));
    }

    public function test_public_signatures_do_not_accept_float(): void
    {
        $this->assertParameterDisallowsFloat('calculateFromDecimal', 'basePrice');
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

        $this->assertNotNull($target, "No se encontró el parámetro {$parameter} en {$method}.");

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
