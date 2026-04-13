<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\Product;
use App\Modules\Categories\Models\Category;
use App\Modules\Shared\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSearchFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_search_requires_strong_match_for_multi_word_terms(): void
    {
        $distributor = Distributor::create(['name' => 'Dist', 'status' => 'active']);
        $user = User::factory()->create([
            'role' => UserRole::Distributor,
            'distributor_id' => $distributor->id,
            'email_verified_at' => now(),
        ]);

        $category = Category::create([
            'name' => 'Protección',
            'slug' => 'proteccion',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $category->synonyms()->create(['term' => 'seguridad']);

        Product::create([
            'name' => 'Guante Nitrilo Premium',
            'sku' => 'BUS-001',
            'description' => 'Protección para procedimiento clínico',
            'category_id' => $category->id,
            'price' => 1000,
            'is_active' => true,
        ]);

        Product::create([
            'name' => 'Caja Básica',
            'sku' => 'BUS-002',
            'description' => 'Incluye guante nitrilo para demo',
            'category_id' => $category->id,
            'price' => 1000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('catalog.index', ['term' => 'guante nitrilo']));

        $response->assertOk();
        $response->assertSee('Guante Nitrilo Premium');
        $response->assertDontSee('Caja Básica');
    }

    public function test_search_can_match_product_brand(): void
    {
        $distributor = Distributor::create(['name' => 'Dist 2', 'status' => 'active']);
        $user = User::factory()->create([
            'role' => UserRole::Distributor,
            'distributor_id' => $distributor->id,
            'email_verified_at' => now(),
        ]);

        $category = Category::create([
            'name' => 'Diagnóstico',
            'slug' => 'diagnostico',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        Product::create([
            'name' => 'Termómetro Digital',
            'brand' => 'Omron',
            'sku' => 'BRAND-001',
            'description' => 'Medición de temperatura',
            'category_id' => $category->id,
            'price' => 1200,
            'is_active' => true,
        ]);

        Product::create([
            'name' => 'Tensiómetro Manual',
            'brand' => 'Generica',
            'sku' => 'BRAND-002',
            'description' => 'Presión arterial',
            'category_id' => $category->id,
            'price' => 900,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('catalog.index', ['term' => 'omron']));

        $response->assertOk();
        $response->assertSee('Termómetro Digital');
        $response->assertDontSee('Tensiómetro Manual');
    }
}
