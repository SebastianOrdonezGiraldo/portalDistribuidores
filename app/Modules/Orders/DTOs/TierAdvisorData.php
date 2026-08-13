<?php

namespace App\Modules\Orders\DTOs;

use App\Modules\Orders\Support\AdvisorWhatsappNormalizer;
use App\Modules\Shared\Enums\DistributorTier;

final readonly class TierAdvisorData
{
    public function __construct(
        public DistributorTier $tier,
        public ?string $name,
        public ?string $email,
        public ?string $whatsapp,
    ) {}

    public function validName(): ?string
    {
        $name = trim((string) $this->name);

        return $name !== '' ? $name : null;
    }

    public function validEmail(): ?string
    {
        $email = trim((string) $this->email);

        return filter_var($email, FILTER_VALIDATE_EMAIL) ? $email : null;
    }

    public function validWhatsapp(): ?string
    {
        return AdvisorWhatsappNormalizer::normalize($this->whatsapp);
    }

    public function whatsappUrl(?string $message = null): ?string
    {
        $number = $this->validWhatsapp();

        if ($number === null) {
            return null;
        }

        $url = 'https://wa.me/'.$number;

        return filled($message) ? $url.'?text='.rawurlencode($message) : $url;
    }
}
