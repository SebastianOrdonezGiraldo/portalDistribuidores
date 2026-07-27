<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\AuthAccess\Models\Distributor;
use App\Modules\Catalog\Models\Product;
use App\Modules\Categories\Models\Category;
use App\Modules\Shared\Enums\DistributorTier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductNewFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_creates_normal_product_when_new_fields_are_omitted(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->productPayload($category, 'NEW-NORMAL'))
            ->assertRedirect();

        $this->assertDatabaseHas('products', [
            'sku' => 'NEW-NORMAL',
            'is_new' => false,
            'new_until' => null,
        ]);
    }

    public function test_admin_creates_new_product_without_expiration(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->productPayload($category, 'NEW-NO-DATE', [
                'is_new' => 1,
                'new_until' => '',
            ]))
            ->assertRedirect();

        $product = Product::query()->where('sku', 'NEW-NO-DATE')->firstOrFail();

        $this->assertTrue($product->is_new);
        $this->assertNull($product->new_until);
        $this->assertTrue($product->isCurrentlyNew());
    }

    public function test_admin_creates_new_product_with_current_or_future_expiration(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();
        $futureDate = now(config('app.timezone'))->addDays(5)->toDateString();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->productPayload($category, 'NEW-FUTURE', [
                'is_new' => 1,
                'new_until' => $futureDate,
            ]))
            ->assertRedirect();

        $product = Product::query()->where('sku', 'NEW-FUTURE')->firstOrFail();

        $this->assertSame($futureDate, $product->new_until?->format('Y-m-d'));
        $this->assertTrue($product->isCurrentlyNew());
    }

    public function test_admin_can_store_past_date_as_expired_state(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();
        $pastDate = now(config('app.timezone'))->subDay()->toDateString();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->productPayload($category, 'NEW-PAST', [
                'is_new' => 1,
                'new_until' => $pastDate,
            ]))
            ->assertRedirect();

        $product = Product::query()->where('sku', 'NEW-PAST')->firstOrFail();

        $this->assertTrue($product->isNewExpired());
        $this->assertFalse($product->isCurrentlyNew());
    }

    public function test_invalid_new_until_date_is_rejected(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->productPayload($category, 'NEW-BAD-DATE', [
                'is_new' => 1,
                'new_until' => '31/12/2026',
            ]))
            ->assertSessionHasErrors('new_until');

        $this->assertDatabaseMissing('products', ['sku' => 'NEW-BAD-DATE']);
    }

    public function test_unchecked_product_can_keep_date_without_showing_badge(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();
        $futureDate = now(config('app.timezone'))->addMonth()->toDateString();

        $this->actingAs($admin)
            ->post(route('admin.products.store'), $this->productPayload($category, 'NEW-UNCHECKED', [
                'is_new' => 0,
                'new_until' => $futureDate,
            ]))
            ->assertRedirect();

        $product = Product::query()->where('sku', 'NEW-UNCHECKED')->firstOrFail();

        $this->assertFalse($product->is_new);
        $this->assertSame($futureDate, $product->new_until?->format('Y-m-d'));
        $this->assertFalse($product->isCurrentlyNew());
    }

    public function test_admin_can_activate_change_remove_extend_and_disable_new_configuration(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'NEW-EDIT',
            'is_new' => false,
            'new_until' => null,
        ]);
        $firstFutureDate = now(config('app.timezone'))->addDays(5)->toDateString();
        $secondFutureDate = now(config('app.timezone'))->addDays(10)->toDateString();
        $pastDate = now(config('app.timezone'))->subDays(2)->toDateString();

        $this->updateProduct($admin, $product, ['is_new' => 1, 'new_until' => '']);
        $this->assertTrue($product->fresh()->isCurrentlyNew());
        $this->assertNull($product->fresh()->new_until);

        $this->updateProduct($admin, $product, ['is_new' => 1, 'new_until' => $firstFutureDate]);
        $this->assertSame($firstFutureDate, $product->fresh()->new_until?->format('Y-m-d'));

        $this->updateProduct($admin, $product, ['is_new' => 1, 'new_until' => $secondFutureDate]);
        $this->assertSame($secondFutureDate, $product->fresh()->new_until?->format('Y-m-d'));

        $this->updateProduct($admin, $product, ['is_new' => 1, 'new_until' => '']);
        $this->assertNull($product->fresh()->new_until);

        $product->update(['is_new' => true, 'new_until' => $pastDate]);
        $this->assertTrue($product->fresh()->isNewExpired());

        $this->updateProduct($admin, $product, ['is_new' => 1, 'new_until' => $secondFutureDate]);
        $this->assertTrue($product->fresh()->isCurrentlyNew());

        $this->updateProduct($admin, $product, ['is_new' => 0, 'new_until' => $secondFutureDate]);
        $product->refresh();
        $this->assertFalse($product->is_new);
        $this->assertSame($secondFutureDate, $product->new_until?->format('Y-m-d'));
        $this->assertFalse($product->isCurrentlyNew());
    }

    public function test_edit_form_shows_each_calculated_administrative_status(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();
        $today = now(config('app.timezone'))->toImmutable();

        $cases = [
            [false, null, 'No marcado como nuevo.'],
            [true, null, 'Nuevo activo sin vencimiento.'],
            [true, $today->addDays(3)->toDateString(), 'Nuevo activo hasta '.$today->addDays(3)->format('d/m/Y').'.'],
            [true, $today->subDays(3)->toDateString(), 'Etiqueta vencida desde '.$today->subDays(3)->format('d/m/Y').'.'],
        ];

        foreach ($cases as $index => [$isNew, $newUntil, $expectedStatus]) {
            $product = Product::factory()->create([
                'category_id' => $category->id,
                'sku' => 'NEW-STATUS-'.$index,
                'is_new' => $isNew,
                'new_until' => $newUntil,
            ]);

            $this->actingAs($admin)
                ->get(route('admin.products.edit', $product))
                ->assertOk()
                ->assertSee($expectedStatus);
        }
    }

    public function test_old_input_is_preserved_after_validation_error(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin)
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), $this->productPayload($category, 'NEW-OLD', [
                'name' => '',
                'is_new' => 1,
                'new_until' => '2026-12-31',
            ]))
            ->assertRedirect(route('admin.products.create'))
            ->assertSessionHasErrors('name')
            ->assertSessionHasInput('is_new', 1)
            ->assertSessionHasInput('new_until', '2026-12-31');

        $this->actingAs($admin)
            ->get(route('admin.products.create'))
            ->assertOk()
            ->assertSee('value="2026-12-31"', false)
            ->assertSee('name="is_new"', false);
    }

    public function test_new_badge_appears_in_catalog_search_and_category_results(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->markedAsNew()->create([
            'category_id' => $category->id,
            'name' => 'Producto Nuevo Buscable',
            'sku' => 'NEW-PUBLIC-LISTS',
        ]);

        $this->get(route('catalog.index'))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee('data-product-new-badge', false);

        $this->get(route('catalog.index', ['term' => 'Nuevo Buscable']))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee('data-product-new-badge', false);

        $this->get(route('catalog.index', ['category_id' => $category->id]))
            ->assertOk()
            ->assertSee($product->name)
            ->assertSee('data-product-new-badge', false);
    }

    public function test_new_badge_appears_on_related_card_and_product_detail(): void
    {
        $category = Category::factory()->create();
        $mainProduct = Product::factory()->create([
            'category_id' => $category->id,
            'name' => 'Producto Principal',
            'sku' => 'NEW-MAIN',
            'is_new' => false,
        ]);
        $relatedProduct = Product::factory()->markedAsNew()->create([
            'category_id' => $category->id,
            'name' => 'Producto Relacionado Nuevo',
            'sku' => 'NEW-RELATED',
        ]);

        $mainResponse = $this->get(route('products.show', $mainProduct));
        $mainResponse->assertOk()->assertSee($relatedProduct->name);
        $this->assertSame(1, substr_count($mainResponse->getContent(), 'data-product-new-badge'));

        $this->get(route('products.show', $relatedProduct))
            ->assertOk()
            ->assertSee('data-product-new-badge', false)
            ->assertSee('NUEVO');
    }

    public function test_badge_works_for_silver_and_gold_clients_and_coexists_with_out_of_stock(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->markedAsNew()->create([
            'category_id' => $category->id,
            'sku' => 'NEW-TIERS-STOCK',
            'stock' => 0,
        ]);

        foreach ([DistributorTier::Silver, DistributorTier::Gold] as $tier) {
            $user = $this->distributorUser($tier);

            $this->actingAs($user)
                ->get(route('catalog.index'))
                ->assertOk()
                ->assertSee($product->name)
                ->assertSee('data-product-new-badge', false)
                ->assertSee('Agotado');
        }
    }

    public function test_expired_and_unmarked_products_do_not_show_public_badge(): void
    {
        $category = Category::factory()->create();
        $expired = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'NEW-EXPIRED-PUBLIC',
            'is_new' => true,
            'new_until' => now(config('app.timezone'))->subDay()->toDateString(),
        ]);
        $unmarked = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'NEW-UNMARKED-PUBLIC',
            'is_new' => false,
            'new_until' => now(config('app.timezone'))->addWeek()->toDateString(),
        ]);

        foreach ([$expired, $unmarked] as $product) {
            $this->get(route('products.show', $product))
                ->assertOk()
                ->assertDontSee('data-product-new-badge', false);
        }
    }

    public function test_distributor_cannot_modify_new_product_fields_manually(): void
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'sku' => 'NEW-PERMISSION',
            'is_new' => false,
            'new_until' => null,
        ]);
        $user = $this->distributorUser(DistributorTier::Silver);

        $this->actingAs($user)
            ->put(route('admin.products.update', $product), $this->productPayload($category, $product->sku, [
                'is_new' => 1,
                'new_until' => now(config('app.timezone'))->addMonth()->toDateString(),
            ]))
            ->assertForbidden();

        $product->refresh();
        $this->assertFalse($product->is_new);
        $this->assertNull($product->new_until);
    }

    public function test_admin_product_index_has_no_new_product_controls_or_badges(): void
    {
        $admin = User::factory()->admin()->create();
        Product::factory()->markedAsNew()->create();

        $this->actingAs($admin)
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertDontSee('Marcar como producto nuevo')
            ->assertDontSee('Mostrar como nuevo hasta')
            ->assertDontSee('data-product-new-badge', false)
            ->assertDontSee('name="new_until"', false);
    }

    public function test_duplicate_preserves_new_configuration_without_publishing_copy(): void
    {
        $admin = User::factory()->admin()->create();
        $newUntil = now(config('app.timezone'))->addDays(8)->toDateString();
        $product = Product::factory()->create([
            'sku' => 'NEW-DUPLICATE',
            'is_new' => true,
            'new_until' => $newUntil,
        ]);

        $this->actingAs($admin)
            ->post(route('admin.products.duplicate', $product))
            ->assertRedirect();

        $duplicate = Product::query()->whereKeyNot($product->id)->firstOrFail();
        $this->assertFalse($duplicate->is_active);
        $this->assertTrue($duplicate->is_new);
        $this->assertSame($newUntil, $duplicate->new_until?->format('Y-m-d'));
    }

    private function updateProduct(User $admin, Product $product, array $overrides): void
    {
        $category = $product->category()->firstOrFail();

        $this->actingAs($admin)
            ->put(
                route('admin.products.update', $product),
                $this->productPayload($category, $product->sku, $overrides),
            )
            ->assertRedirect();
    }

    private function productPayload(Category $category, string $sku, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Producto '.$sku,
            'brand' => 'Marca Prueba',
            'sku' => $sku,
            'description' => 'Descripción de prueba',
            'category_id' => $category->id,
            'price' => 12500,
            'stock' => 25,
            'is_active' => 1,
            'is_vat_excluded' => 0,
            'has_variants' => 0,
        ], $overrides);
    }

    private function distributorUser(DistributorTier $tier): User
    {
        $distributor = Distributor::factory()->create(['tier' => $tier]);

        return User::factory()->create(['distributor_id' => $distributor->id]);
    }
}
