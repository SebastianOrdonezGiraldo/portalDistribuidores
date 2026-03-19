<?php

namespace Database\Factories;

use App\Modules\Shared\Enums\DistributorStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Modules\AuthAccess\Models\Distributor>
 */
class DistributorFactory extends Factory
{
    protected $model = \App\Modules\AuthAccess\Models\Distributor::class;

    public function definition(): array
    {
        return [
            'name'          => fake()->company(),
            'status'        => DistributorStatus::Active,
            'nit'           => fake()->numerify('########-#'),
            'address'       => fake()->streetAddress(),
            'city'          => fake()->city(),
            'phone'         => fake()->phoneNumber(),
            'contact_email' => fake()->companyEmail(),
            'contact_name'  => fake()->name(),
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn () => ['status' => DistributorStatus::Suspended]);
    }
}
