<?php

namespace Tests\Unit;

use App\Modules\Shared\Enums\ShippingCarrier;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ShippingCarrierTest extends TestCase
{
    #[DataProvider('trackingNumbers')]
    public function test_it_detects_carrier_by_tracking_number_prefix(string $trackingNumber, ?ShippingCarrier $expected): void
    {
        $this->assertSame($expected, ShippingCarrier::detect($trackingNumber));
    }

    /** @return array<string, array{string, ShippingCarrier|null}> */
    public static function trackingNumbers(): array
    {
        return [
            'servientrega prefix 2' => ['2258298191', ShippingCarrier::Servientrega],
            'servientrega prefix 3' => ['3012241226', ShippingCarrier::Servientrega],
            'interrapidisimo' => ['700184205491', ShippingCarrier::Interrapidisimo],
            'deprisa' => ['888004907296', ShippingCarrier::Deprisa],
            'envia' => ['957000255300', ShippingCarrier::Envia],
            'unknown prefix' => ['612345', null],
            'empty number' => ['', null],
        ];
    }

    public function test_custom_value_overrides_detected_carrier(): void
    {
        $this->assertSame(
            'Carga aérea especial',
            ShippingCarrier::resolveValue('  Carga aérea especial  ', '2258298191'),
        );
    }

    public function test_detected_carrier_is_used_when_custom_value_is_empty(): void
    {
        $this->assertSame('servientrega', ShippingCarrier::resolveValue('', '3012241226'));
    }
}
