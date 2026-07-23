<?php

namespace App\Modules\Orders\DTOs;

use App\Modules\Shared\Enums\PaymentMethod;

final readonly class CreateOrderData
{
    /**
     * @param  array<int, array{product_id:int,variant_id:int|null,qty:int,unit_label:string}>  $items
     */
    public function __construct(
        public string $contactName,
        public string $contactEmail,
        public string $phone,
        public string $companyName,
        public string $companyNit,
        public string $companyAddress,
        public string $city,
        public string $department,
        public ?string $notes,
        public array $items,
        public bool $requiresApproval = false,
        public string $checkoutIntent = 'quote',
        public ?PaymentMethod $paymentMethod = null,
    ) {}

    public static function fromArray(array $payload): self
    {
        $intent = (string) ($payload['checkout_intent'] ?? $payload['intent'] ?? 'quote');
        $intent = in_array($intent, ['quote', 'pay'], true) ? $intent : 'quote';

        $methodValue = $payload['payment_method'] ?? null;
        $paymentMethod = is_string($methodValue) && $methodValue !== ''
            ? PaymentMethod::tryFrom($methodValue)
            : null;

        if ($intent === 'pay' && $paymentMethod === null) {
            $paymentMethod = null;
        }

        if ($intent !== 'pay') {
            $paymentMethod = null;
        }

        return new self(
            contactName: $payload['contact_name'],
            contactEmail: $payload['contact_email'],
            phone: $payload['phone'],
            companyName: $payload['company_name'],
            companyNit: $payload['company_nit'],
            companyAddress: $payload['company_address'],
            city: $payload['city'],
            department: $payload['department'],
            notes: $payload['notes'] ?? null,
            items: $payload['items'] ?? [],
            requiresApproval: (bool) ($payload['requires_approval'] ?? false),
            checkoutIntent: $intent,
            paymentMethod: $paymentMethod,
        );
    }

    public function isPayIntent(): bool
    {
        return $this->checkoutIntent === 'pay';
    }
}
