<?php

namespace App\Modules\Orders\Services;

use App\Models\User;
use App\Modules\Orders\DTOs\TierAdvisorData;
use App\Modules\Orders\Models\CommerceTierAdvisor;
use App\Modules\Shared\Enums\DistributorTier;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Throwable;

final class CommerceTierAdvisorProvider
{
    public const CACHE_KEY = 'commerce.tier-advisors.current.v1';

    /**
     * @return array<string, TierAdvisorData|null>
     */
    public function all(): array
    {
        /** @var array<string, array<string, string|null>> $payload */
        $payload = app()->environment('testing')
            ? $this->resolveFresh()
            : Cache::remember(self::CACHE_KEY, now()->addMinutes(5), fn (): array => $this->resolveFresh());

        $advisors = [];

        foreach (DistributorTier::cases() as $tier) {
            $row = $payload[$tier->value] ?? null;

            $advisors[$tier->value] = $row === null
                ? null
                : new TierAdvisorData(
                    tier: $tier,
                    name: $row['name'] ?? null,
                    email: $row['email'] ?? null,
                    whatsapp: $row['whatsapp'] ?? null,
                );
        }

        return $advisors;
    }

    public function forTier(DistributorTier $tier): ?TierAdvisorData
    {
        return $this->all()[$tier->value] ?? null;
    }

    public function forUser(?User $user): ?TierAdvisorData
    {
        if (! $user?->isDistributor()) {
            return null;
        }

        $tier = $user->distributor?->tier;

        return $tier instanceof DistributorTier ? $this->forTier($tier) : null;
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, array<string, string|null>>
     */
    private function resolveFresh(): array
    {
        try {
            if (! Schema::hasTable('commerce_tier_advisors')) {
                return [];
            }

            return CommerceTierAdvisor::query()
                ->get(['tier', 'advisor_name', 'advisor_email', 'advisor_whatsapp'])
                ->mapWithKeys(function (CommerceTierAdvisor $advisor): array {
                    $tier = $advisor->tier;

                    if (! $tier instanceof DistributorTier) {
                        return [];
                    }

                    return [$tier->value => [
                        'name' => $advisor->advisor_name,
                        'email' => $advisor->advisor_email,
                        'whatsapp' => $advisor->advisor_whatsapp,
                    ]];
                })
                ->all();
        } catch (Throwable) {
            return [];
        }
    }
}
