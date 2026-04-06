<?php

namespace App\Modules\Orders\DTOs;

final readonly class CreateOrderData
{
    /**
     * @param array<int, array{product_id:int,variant_id:int|null,qty:int,unit_label:string}> $items
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
    ) {
    }

    public static function fromArray(array $payload): self
    {
        return new self(
            contactName:      $payload['contact_name'],
            contactEmail:     $payload['contact_email'],
            phone:            $payload['phone'],
            companyName:      $payload['company_name'],
            companyNit:       $payload['company_nit'],
            companyAddress:   $payload['company_address'],
            city:             $payload['city'],
            department:       $payload['department'],
            notes:            $payload['notes'] ?? null,
            items:            $payload['items'] ?? [],
            requiresApproval: (bool) ($payload['requires_approval'] ?? false),
        );
    }
}
