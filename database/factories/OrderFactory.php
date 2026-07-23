<?php

namespace Database\Factories;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Orders\Models\Order;
use App\Modules\Shared\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    public function definition(): array
    {
        return [
            'distributor_id' => Distributor::factory(),
            'user_id' => User::factory(),
            'oc_number' => 'CTC-'.fake()->unique()->numerify('######'),
            'contact_name' => fake()->name(),
            'contact_email' => fake()->email(),
            'company_name' => fake()->company(),
            'company_nit' => fake()->numerify('########-#'),
            'company_address' => fake()->streetAddress(),
            'city' => fake()->city(),
            'department' => fake()->randomElement(config('locations.colombia_departments', ['Antioquia'])),
            'notes' => null,
            'status' => OrderStatus::Submitted,
            'payment_status' => \App\Modules\Shared\Enums\PaymentStatus::NotApplicable,
            'total_amount' => fake()->numberBetween(5000, 500000),
            'pdf_path' => null,
        ];
    }

    public function pendingApproval(): static
    {
        return $this->state(fn () => ['status' => OrderStatus::PendingApproval]);
    }

    public function withPdfPath(string $path): static
    {
        return $this->state(fn () => ['pdf_path' => $path]);
    }

    public function forDistributor(Distributor $distributor): static
    {
        return $this->state(fn () => ['distributor_id' => $distributor->id]);
    }
}
