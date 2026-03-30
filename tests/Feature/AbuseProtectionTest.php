<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Categories\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class AbuseProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_route_is_rate_limited_by_ip(): void
    {
        config()->set('abuse_protection.login.per_minute', 2);
        config()->set('abuse_protection.login.per_hour', 999);

        $payload = [
            'email' => 'nobody@example.com',
            'password' => 'invalid-password',
        ];

        $headers = $this->browserHeaders();

        $this->withHeaders($headers)->post('/login', $payload)->assertStatus(302);
        $this->withHeaders($headers)->post('/login', $payload)->assertStatus(302);
        $this->withHeaders($headers)->post('/login', $payload)->assertStatus(429);
    }

    public function test_registration_route_is_rate_limited(): void
    {
        config()->set('auth.allow_public_registration', true);
        config()->set('abuse_protection.registration.per_hour', 2);
        config()->set('abuse_protection.registration.per_day', 999);

        $headers = $this->browserHeaders();

        $this->withHeaders($headers)->post('/register', [
            'name' => 'Registro Uno',
            'email' => 'registro-uno@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertStatus(302);

        $this->withHeaders($headers)->post('/register', [
            'name' => 'Registro Dos',
            'email' => 'registro-dos@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertStatus(302);

        $this->withHeaders($headers)->post('/register', [
            'name' => 'Registro Tres',
            'email' => 'registro-tres@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertStatus(429);
    }

    public function test_api_endpoint_is_rate_limited(): void
    {
        config()->set('abuse_protection.api.auth_per_minute', 2);
        config()->set('abuse_protection.api.auth_per_hour', 999);

        $admin = User::factory()->admin()->create();
        $category = Category::create([
            'parent_id' => null,
            'name' => 'Categoria API',
            'slug' => 'categoria-api',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Product::create([
            'name' => 'Producto API',
            'sku' => 'SKU-API-001',
            'description' => 'Producto para test API.',
            'category_id' => $category->id,
            'price' => 1000,
            'stock' => 5,
            'is_active' => true,
        ]);

        $headers = $this->browserHeaders(['Accept' => 'application/json']);

        $this->actingAs($admin)
            ->withHeaders($headers)
            ->get('/admin/products/check-sku?sku=SKU-API-NEW')
            ->assertOk();

        $this->actingAs($admin)
            ->withHeaders($headers)
            ->get('/admin/products/check-sku?sku=SKU-API-NEW')
            ->assertOk();

        $this->actingAs($admin)
            ->withHeaders($headers)
            ->get('/admin/products/check-sku?sku=SKU-API-NEW')
            ->assertStatus(429);
    }

    public function test_catalog_ajax_requests_are_rate_limited_to_reduce_scraping(): void
    {
        config()->set('abuse_protection.catalog.guest_per_minute', 2);
        config()->set('abuse_protection.catalog.guest_per_hour', 999);

        $headers = $this->browserHeaders([
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ]);

        $this->withHeaders($headers)->get('/catalog')->assertOk();
        $this->withHeaders($headers)->get('/catalog')->assertOk();
        $this->withHeaders($headers)->get('/catalog')->assertStatus(429);
    }

    public function test_suspicious_automation_user_agent_gets_throttled(): void
    {
        config()->set('abuse_protection.automation.enabled', true);
        config()->set('abuse_protection.automation.max_per_minute', 2);
        config()->set('abuse_protection.catalog.guest_per_minute', 999);
        config()->set('abuse_protection.catalog.guest_per_hour', 9999);

        $headers = [
            'User-Agent' => 'curl/8.6.0',
        ];

        $this->withHeaders($headers)->get('/catalog')->assertOk();
        $this->withHeaders($headers)->get('/catalog')->assertOk();
        $this->withHeaders($headers)->get('/catalog')->assertStatus(429);
    }

    public function test_ai_generation_like_payloads_are_rate_limited(): void
    {
        config()->set('abuse_protection.ai_generation.guest_per_minute', 2);
        config()->set('abuse_protection.ai_generation.guest_per_hour', 999);

        Route::middleware('web')->post('/tests/ai-generation-probe', function () {
            return response()->json(['ok' => true]);
        });

        $headers = $this->browserHeaders(['Accept' => 'application/json']);
        $payload = [
            'model' => 'gpt-5.4-mini',
            'prompt' => 'Genera una respuesta.',
        ];

        $this->withHeaders($headers)->post('/tests/ai-generation-probe', $payload)->assertOk();
        $this->withHeaders($headers)->post('/tests/ai-generation-probe', $payload)->assertOk();
        $this->withHeaders($headers)->post('/tests/ai-generation-probe', $payload)->assertStatus(429);
    }

    /**
     * @param array<string, string> $extra
     * @return array<string, string>
     */
    private function browserHeaders(array $extra = []): array
    {
        return array_merge([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        ], $extra);
    }
}
