<?php

namespace Tests\Feature;

use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Orders\Jobs\SendOrderNotificationEmailJob;
use App\Modules\Orders\Mail\OrderCreatedCustomerQuotationMail;
use App\Modules\Orders\Mail\OrderCreatedNotificationMail;
use App\Modules\Orders\Models\CommerceTierAdvisor;
use App\Modules\Orders\Models\Order;
use App\Modules\Orders\Services\OrderAdvisorResolver;
use App\Modules\Orders\Services\OrderPdfGenerator;
use App\Modules\Shared\Enums\DistributorTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrderAdvisorNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_snapshot_email_has_priority(): void
    {
        $this->runJob($this->order(['advisor_email_snapshot' => 'snapshot@example.test']));

        Mail::assertSent(OrderCreatedNotificationMail::class, fn ($mail) => $mail->hasTo('snapshot@example.test'));
    }

    public function test_current_advisor_is_used_only_when_order_has_distributor_context(): void
    {
        $distributor = Distributor::factory()->silver()->create();
        CommerceTierAdvisor::query()->create([
            'tier' => DistributorTier::Silver,
            'advisor_name' => 'Asesor Plata',
            'advisor_email' => 'plata@example.test',
            'advisor_whatsapp' => '573001112233',
        ]);

        $order = $this->order([
            'distributor_id' => $distributor->id,
            'distributor_tier_snapshot' => DistributorTier::Silver,
            'advisor_email_snapshot' => null,
        ]);

        $this->runJob($order);

        Mail::assertSent(OrderCreatedNotificationMail::class, fn ($mail) => $mail->hasTo('plata@example.test'));
    }

    public function test_legacy_order_without_distributor_does_not_use_tier_advisor(): void
    {
        config(['mail.order_notification_to' => 'global@example.test']);
        CommerceTierAdvisor::query()->create([
            'tier' => DistributorTier::Silver,
            'advisor_name' => 'Asesor Plata',
            'advisor_email' => 'plata@example.test',
            'advisor_whatsapp' => '573001112233',
        ]);

        $order = $this->order([
            'distributor_id' => null,
            'distributor_tier_snapshot' => DistributorTier::Silver,
            'advisor_email_snapshot' => null,
        ]);

        $this->runJob($order);

        Mail::assertSent(OrderCreatedNotificationMail::class, fn ($mail) => $mail->hasTo('global@example.test'));
        Mail::assertNotSent(OrderCreatedNotificationMail::class, fn ($mail) => $mail->hasTo('plata@example.test'));
    }

    public function test_missing_global_recipient_is_skipped_without_throwing(): void
    {
        config(['mail.order_notification_to' => 'not-an-email']);
        $order = $this->order([
            'distributor_id' => null,
            'distributor_tier_snapshot' => DistributorTier::Silver,
            'advisor_email_snapshot' => null,
        ]);

        $this->runJob($order);

        Mail::assertNotSent(OrderCreatedNotificationMail::class);
        Mail::assertSent(OrderCreatedCustomerQuotationMail::class);
    }

    private function runJob(Order $order): void
    {
        Mail::fake();
        Storage::fake(OrderPdfGenerator::diskName());
        Storage::disk(OrderPdfGenerator::diskName())->put($order->pdf_path, 'pdf');

        (new SendOrderNotificationEmailJob($order->id))->handle(
            app(OrderPdfGenerator::class),
            app(OrderAdvisorResolver::class),
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function order(array $attributes): Order
    {
        return Order::factory()->create(array_merge([
            'distributor_id' => Distributor::factory()->create()->id,
            'advisor_email_snapshot' => 'snapshot@example.test',
            'pdf_path' => 'orders/CTC-000001.pdf',
            'contact_email' => 'customer@example.test',
        ], $attributes));
    }
}
