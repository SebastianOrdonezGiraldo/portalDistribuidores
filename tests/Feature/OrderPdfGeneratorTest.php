<?php

namespace Tests\Feature;

use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Models\OrderItem;
use App\Modules\Orders\Services\OrderPdfGenerator;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class OrderPdfGeneratorTest extends TestCase
{
    use RefreshDatabase;

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
}
