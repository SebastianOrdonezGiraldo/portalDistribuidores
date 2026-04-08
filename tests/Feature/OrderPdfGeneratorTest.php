<?php

namespace Tests\Feature;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Orders\Services\OrderPdfGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Storage;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class OrderPdfGeneratorTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Facade::clearResolvedInstance('filesystem');
        Facade::clearResolvedInstance('filesystem.disk');
        Facade::clearResolvedInstance('files');

        parent::tearDown();
    }

    public function test_disk_name_uses_configured_disk(): void
    {
        config(['filesystems.order_pdfs_disk' => 's3-private']);

        $this->assertSame('s3-private', OrderPdfGenerator::diskName());
    }

    public function test_generator_regenerates_pdf_when_order_data_changes(): void
    {
        Storage::fake('private');
        Storage::fake('public');

        config([
            'filesystems.order_pdfs_disk' => 'private',
        ]);

        $order = Order::factory()->create([
            'oc_number' => 'CTC-000001',
        ]);

        $item = OrderItem::factory()->for($order)->create([
            'product_name_snapshot' => 'Producto inicial',
            'sku_snapshot' => 'SKU-INI',
            'qty' => 1,
            'price_each' => 1000,
            'subtotal' => 1000,
        ]);

        $outputs = ['%PDF-FAKE-FIRST', '%PDF-FAKE-SECOND'];
        $callIndex = 0;

        Pdf::shouldReceive('loadView')
            ->twice()
            ->with('orders.pdf', Mockery::type('array'))
            ->andReturnUsing(function () use (&$callIndex, $outputs) {
                $pdfMock = Mockery::mock(DomPdfWrapper::class);
                $pdfMock->shouldReceive('setPaper')
                    ->once()
                    ->with('a4', 'portrait')
                    ->andReturnSelf();
                $pdfMock->shouldReceive('output')
                    ->once()
                    ->andReturn($outputs[$callIndex++]);

                return $pdfMock;
            });

        $generator = new OrderPdfGenerator();

        $path = $generator->generate($order);

        $this->assertSame('orders/'.$order->oc_number.'.pdf', $path);
        $this->assertSame($outputs[0], Storage::disk('private')->get($path));

        $item->forceFill([
            'qty' => 3,
            'subtotal' => 3000,
            'updated_at' => now()->addMinute(),
        ])->saveQuietly();

        $order->forceFill([
            'updated_at' => now()->addMinute(),
        ])->saveQuietly();

        $generator->generate($order);

        $this->assertSame($outputs[1], Storage::disk('private')->get($path));
    }

    public function test_generator_builds_expected_view_data_and_total_final(): void
    {
        Storage::fake('private');
        Storage::fake('public');

        config([
            'filesystems.order_pdfs_disk' => 'private',
            'billing.vat_rate' => 0.19,
        ]);

        $order = Order::factory()->create([
            'oc_number' => 'CTC-000777',
        ]);

        OrderItem::factory()->for($order)->create([
            'product_name_snapshot' => 'Producto A',
            'sku_snapshot' => 'SKU-A',
            'qty' => 2,
            'price_each' => 1000,
            'subtotal' => 2000,
        ]);

        OrderItem::factory()->for($order)->create([
            'product_name_snapshot' => 'Producto B',
            'sku_snapshot' => 'SKU-B',
            'qty' => 1,
            'price_each' => 500,
            'subtotal' => 500,
        ]);

        Pdf::shouldReceive('loadView')
            ->once()
            ->with('orders.pdf', Mockery::on(function (array $data) use ($order) {
                $this->assertTrue($data['order']->is($order));
                $this->assertSame(0.19, $data['vatRate']);
                $this->assertCount(2, $data['lineItems']);
                $this->assertSame(840.34, $data['lineItems'][0]['valorUnit']);
                $this->assertSame(159.66, $data['lineItems'][0]['valorIva']);
                $this->assertSame(1000.0, $data['lineItems'][0]['valorTotal']);
                $this->assertSame(2000.0, $data['lineItems'][0]['valorTotalLinea']);
                $this->assertSame(420.17, $data['lineItems'][1]['valorUnit']);
                $this->assertSame(79.83, $data['lineItems'][1]['valorIva']);
                $this->assertSame(500.0, $data['lineItems'][1]['valorTotal']);
                $this->assertSame(500.0, $data['lineItems'][1]['valorTotalLinea']);
                $this->assertSame(2500.0, $data['totalFinal']);
                $this->assertTrue(array_key_exists('logoBase64', $data));

                return true;
            }))
            ->andReturnUsing(function () {
                $pdfMock = Mockery::mock(DomPdfWrapper::class);
                $pdfMock->shouldReceive('setPaper')
                    ->once()
                    ->with('a4', 'portrait')
                    ->andReturnSelf();
                $pdfMock->shouldReceive('output')
                    ->once()
                    ->andReturn('%PDF-VIEW-DATA');

                return $pdfMock;
            });

        $path = (new OrderPdfGenerator())->generate($order);

        $this->assertSame('orders/CTC-000777.pdf', $path);
        $this->assertSame('%PDF-VIEW-DATA', Storage::disk('private')->get($path));
    }

    public function test_generator_migrates_legacy_public_pdf_before_regenerating_and_deletes_public_copy(): void
    {
        Storage::fake('private');
        Storage::fake('public');

        config([
            'filesystems.order_pdfs_disk' => 'private',
        ]);

        $order = Order::factory()->create([
            'oc_number' => 'CTC-LEGACY-001',
        ]);

        $legacyPath = 'orders/'.$order->oc_number.'.pdf';
        Storage::disk('public')->put($legacyPath, '%PDF-LEGACY');

        Pdf::shouldReceive('loadView')
            ->once()
            ->with('orders.pdf', Mockery::type('array'))
            ->andReturnUsing(function () {
                $pdfMock = Mockery::mock(DomPdfWrapper::class);
                $pdfMock->shouldReceive('setPaper')
                    ->once()
                    ->with('a4', 'portrait')
                    ->andReturnSelf();
                $pdfMock->shouldReceive('output')
                    ->once()
                    ->andReturn('%PDF-REGENERATED');

                return $pdfMock;
            });

        $path = (new OrderPdfGenerator())->generate($order);

        $this->assertSame($legacyPath, $path);
        $this->assertFalse(Storage::disk('public')->exists($legacyPath));
        $this->assertTrue(Storage::disk('private')->exists($legacyPath));
        $this->assertSame('%PDF-REGENERATED', Storage::disk('private')->get($legacyPath));
    }

    public function test_generator_throws_when_target_disk_cannot_write_pdf(): void
    {
        config([
            'filesystems.order_pdfs_disk' => 'private',
        ]);

        $order = Order::factory()->create([
            'oc_number' => 'CTC-FAIL-001',
        ]);

        $privateDisk = Mockery::mock(FilesystemAdapter::class);
        $privateDisk->shouldReceive('exists')
            ->once()
            ->with('orders/CTC-FAIL-001.pdf')
            ->andReturn(false);
        $privateDisk->shouldReceive('put')
            ->once()
            ->with('orders/CTC-FAIL-001.pdf', '%PDF-WRITE-FAIL')
            ->andReturn(false);

        $publicDisk = Mockery::mock(FilesystemAdapter::class);
        $publicDisk->shouldReceive('exists')
            ->once()
            ->with('orders/CTC-FAIL-001.pdf')
            ->andReturn(false);
        $publicDisk->shouldReceive('delete')
            ->never();

        Storage::shouldReceive('disk')
            ->with('private')
            ->andReturn($privateDisk);
        Storage::shouldReceive('disk')
            ->with('public')
            ->andReturn($publicDisk);

        Pdf::shouldReceive('loadView')
            ->once()
            ->with('orders.pdf', Mockery::type('array'))
            ->andReturnUsing(function () {
                $pdfMock = Mockery::mock(DomPdfWrapper::class);
                $pdfMock->shouldReceive('setPaper')
                    ->once()
                    ->with('a4', 'portrait')
                    ->andReturnSelf();
                $pdfMock->shouldReceive('output')
                    ->once()
                    ->andReturn('%PDF-WRITE-FAIL');

                return $pdfMock;
            });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No fue posible guardar el PDF de la orden '.$order->id.'.');

        (new OrderPdfGenerator())->generate($order);
    }
}
