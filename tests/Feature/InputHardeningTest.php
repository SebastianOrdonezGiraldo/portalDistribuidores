<?php

namespace Tests\Feature;

use App\Modules\Catalog\Models\Product;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InputHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_catalog_rejects_suspicious_sql_payload_in_query(): void
    {
        $this->getJson('/catalog?term=abc%27%20OR%201=1')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['term']);
    }

    public function test_catalog_rejects_script_injection_payload_in_query(): void
    {
        $this->getJson('/catalog?term=%3Cscript%3Ealert(1)%3C/script%3E')
            ->assertStatus(422)
            ->assertJsonValidationErrors(['term']);
    }

    public function test_cart_update_rejects_invalid_line_key_format(): void
    {
        $product = Product::factory()->create(['price' => 10000]);

        $this->post(route('cart.store'), ['product_id' => $product->id, 'qty' => 1])
            ->assertRedirect();

        $this->patch(route('cart.update'), [
            'quantities' => [
                'linea-invalida' => 2,
            ],
        ])->assertRedirect()
            ->assertSessionHasErrors(['quantities']);
    }

    public function test_cart_destroy_rejects_invalid_line_key_format(): void
    {
        $this->delete(route('cart.destroy', 'linea-invalida'))
            ->assertRedirect()
            ->assertSessionHasErrors();
    }
}
