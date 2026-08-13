<?php

namespace App\Modules\Orders\Services;

use App\Models\User;
use App\Modules\Orders\DTOs\TierAdvisorData;
use App\Modules\Orders\Models\Order;
use App\Modules\Shared\Enums\DistributorTier;

final class OrderAdvisorResolver
{
    public function __construct(
        private readonly CommerceTierAdvisorProvider $provider,
    ) {}

    public function forUser(?User $user): ?TierAdvisorData
    {
        return $this->provider->forUser($user);
    }

    public function snapshotFor(Order $order): ?TierAdvisorData
    {
        $tier = $order->distributor_tier_snapshot;

        if (! $tier instanceof DistributorTier) {
            return null;
        }

        $advisor = new TierAdvisorData(
            tier: $tier,
            name: $order->advisor_name_snapshot,
            email: $order->advisor_email_snapshot,
            whatsapp: $order->advisor_whatsapp_snapshot,
        );

        return $advisor->validName() !== null
            || $advisor->validEmail() !== null
            || $advisor->validWhatsapp() !== null
            ? $advisor
            : null;
    }

    public function currentForOrder(Order $order): ?TierAdvisorData
    {
        if ($order->distributor_id === null) {
            return null;
        }

        $tier = $order->distributor_tier_snapshot;

        return $tier instanceof DistributorTier ? $this->provider->forTier($tier) : null;
    }

    public function notificationEmail(Order $order): ?string
    {
        $snapshotEmail = trim((string) $order->advisor_email_snapshot);

        if (filter_var($snapshotEmail, FILTER_VALIDATE_EMAIL)) {
            return $snapshotEmail;
        }

        if ($order->distributor_id !== null) {
            $tier = $order->distributor_tier_snapshot;

            if ($tier instanceof DistributorTier) {
                $currentEmail = $this->provider->forTier($tier)?->validEmail();

                if ($currentEmail !== null) {
                    return $currentEmail;
                }
            }
        }

        $legacyEmail = trim((string) config('mail.order_notification_to'));

        return filter_var($legacyEmail, FILTER_VALIDATE_EMAIL) ? $legacyEmail : null;
    }

    /**
     * @return array<string, string|null>
     */
    public function snapshotAttributes(?TierAdvisorData $advisor): array
    {
        return [
            'advisor_name_snapshot' => $advisor?->validName(),
            'advisor_email_snapshot' => $advisor?->validEmail(),
            'advisor_whatsapp_snapshot' => $advisor?->validWhatsapp(),
        ];
    }
}
