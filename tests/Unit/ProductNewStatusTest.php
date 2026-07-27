<?php

namespace Tests\Unit;

use App\Modules\Catalog\Models\Product;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class ProductNewStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('app.timezone', 'America/Bogota');
    }

    public function test_unmarked_product_without_date_is_not_currently_new(): void
    {
        $product = $this->product(false, null);

        $this->assertFalse($product->isCurrentlyNew($this->localNoon()));
        $this->assertFalse($product->isNewExpired($this->localNoon()));
        $this->assertSame('No marcado como nuevo.', $product->newStatusText($this->localNoon()));
    }

    public function test_unmarked_product_with_future_date_is_not_currently_new(): void
    {
        $product = $this->product(false, '2026-07-30');

        $this->assertFalse($product->isCurrentlyNew($this->localNoon()));
        $this->assertFalse($product->isNewExpired($this->localNoon()));
    }

    public function test_marked_product_without_date_remains_currently_new(): void
    {
        $product = $this->product(true, null);

        $this->assertTrue($product->isCurrentlyNew($this->localNoon()));
        $this->assertSame('Nuevo activo sin vencimiento.', $product->newStatusText($this->localNoon()));
    }

    public function test_marked_product_with_future_date_is_currently_new(): void
    {
        $product = $this->product(true, '2026-07-28');

        $this->assertTrue($product->isCurrentlyNew($this->localNoon()));
        $this->assertFalse($product->isNewExpired($this->localNoon()));
        $this->assertSame('Nuevo activo hasta 28/07/2026.', $product->newStatusText($this->localNoon()));
    }

    public function test_marked_product_with_today_date_remains_new_for_the_full_local_day(): void
    {
        $product = $this->product(true, '2026-07-27');
        $endOfLocalDay = CarbonImmutable::parse('2026-07-27 23:59:59', 'America/Bogota');

        $this->assertTrue($product->isCurrentlyNew($endOfLocalDay));
        $this->assertFalse($product->isNewExpired($endOfLocalDay));
    }

    public function test_marked_product_with_past_date_is_expired(): void
    {
        $product = $this->product(true, '2026-07-26');

        $this->assertFalse($product->isCurrentlyNew($this->localNoon()));
        $this->assertTrue($product->isNewExpired($this->localNoon()));
        $this->assertSame('Etiqueta vencida desde 26/07/2026.', $product->newStatusText($this->localNoon()));
    }

    public function test_status_uses_application_timezone_when_instant_is_on_next_utc_day(): void
    {
        $product = $this->product(true, '2026-07-27');
        $instant = CarbonImmutable::parse('2026-07-28 03:30:00', 'UTC');

        $this->assertSame('2026-07-27', $instant->setTimezone('America/Bogota')->format('Y-m-d'));
        $this->assertTrue($product->isCurrentlyNew($instant));
    }

    private function product(bool $isNew, ?string $newUntil): Product
    {
        return new Product([
            'is_new' => $isNew,
            'new_until' => $newUntil,
        ]);
    }

    private function localNoon(): CarbonImmutable
    {
        return CarbonImmutable::parse('2026-07-27 12:00:00', 'America/Bogota');
    }
}
