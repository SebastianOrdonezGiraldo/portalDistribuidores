<?php

namespace Database\Factories;

use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Shared\Enums\DistributorStatus;
use App\Modules\Shared\Enums\DistributorTier;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Distributor>
 */
class DistributorFactory extends Factory
{
    protected $model = Distributor::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'status' => DistributorStatus::Active,
            'tier' => DistributorTier::Silver,
            'nit' => fake()->numerify('########-#'),
            'address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'phone' => fake()->phoneNumber(),
            'contact_email' => fake()->companyEmail(),
            'contact_name' => fake()->name(),
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => DistributorStatus::Suspended]);
    }

    public function silver(): static
    {
        return $this->state(fn () => ['tier' => DistributorTier::Silver]);
    }

    public function gold(): static
    {
        return $this->state(fn () => ['tier' => DistributorTier::Gold]);
    }
}
