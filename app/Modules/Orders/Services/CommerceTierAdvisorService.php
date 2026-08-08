<?php

namespace App\Modules\Orders\Services;

use App\Modules\Orders\Models\CommerceTierAdvisor;
use App\Modules\Shared\Enums\DistributorTier;
use Illuminate\Support\Facades\DB;

final class CommerceTierAdvisorService
{
    public function __construct(
        private readonly CommerceTierAdvisorProvider $provider,
    ) {}

    /**
     * @param  array<string, array{advisor_name:string,advisor_email:string,advisor_whatsapp:string}>  $advisors
     */
    public function update(array $advisors): void
    {
        $now = now();
        $rows = [];

        foreach (DistributorTier::cases() as $tier) {
            $data = $advisors[$tier->value];

            $rows[] = [
                'tier' => $tier->value,
                'advisor_name' => $data['advisor_name'],
                'advisor_email' => $data['advisor_email'],
                'advisor_whatsapp' => $data['advisor_whatsapp'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::transaction(function () use ($rows): void {
            CommerceTierAdvisor::query()->upsert(
                $rows,
                ['tier'],
                ['advisor_name', 'advisor_email', 'advisor_whatsapp', 'updated_at'],
            );
        });

        $this->provider->forgetCache();
    }
}
