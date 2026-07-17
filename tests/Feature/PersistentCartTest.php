<?php

namespace Tests\Feature;

use App\Models\User;
use App\Modules\Catalog\Models\Product;
use App\Modules\Orders\Mail\AbandonedCartReminderMail;
use App\Modules\Orders\Models\Cart;
use App\Modules\Orders\Services\Cart\AbandonedCartService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PersistentCartTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(ValidateCsrfToken::class);
    }

    public function test_distributor_cart_is_stored_in_database_and_survives_logout(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 20]);

        $this->actingAs($user)
            ->postJson(route('cart.store'), ['product_id' => $product->id, 'qty' => 2])
            ->assertOk();

        $cartId = Cart::query()->where('user_id', $user->id)->value('id');

        $this->assertNotNull($cartId);
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cartId,
            'product_id' => $product->id,
            'qty' => 2,
        ]);

        $this->post(route('logout'))->assertRedirect('/');

        $this->assertDatabaseHas('carts', ['id' => $cartId, 'user_id' => $user->id]);

        $response = $this->actingAs($user)->get(route('cart.index'));

        $response->assertOk();
        $this->assertSame(2, $response->viewData('items')->first()['qty']);
    }

    public function test_same_account_reads_database_cart_from_a_fresh_session(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 20]);

        $this->actingAs($user)
            ->postJson(route('cart.store'), ['product_id' => $product->id, 'qty' => 3])
            ->assertOk();

        $this->flushSession();

        $response = $this->actingAs($user)->get(route('cart.index'));

        $response->assertOk();
        $this->assertSame(3, $response->viewData('items')->first()['qty']);
    }

    public function test_cart_is_discarded_after_forty_eight_hours_without_activity(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 20]);

        $this->actingAs($user)
            ->postJson(route('cart.store'), ['product_id' => $product->id, 'qty' => 1])
            ->assertOk();

        $cart = Cart::query()->where('user_id', $user->id)->firstOrFail();

        DB::table('carts')->where('id', $cart->id)->update([
            'updated_at' => now()->subHours(49),
        ]);

        $this->flushSession();
        $response = $this->actingAs($user)->get(route('cart.index'));

        $response->assertOk();
        $this->assertCount(0, $response->viewData('items'));
        $this->assertDatabaseMissing('carts', ['id' => $cart->id]);
    }

    public function test_abandoned_cart_receives_one_reminder_after_thirty_six_hours(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 20]);

        $this->actingAs($user)
            ->postJson(route('cart.store'), ['product_id' => $product->id, 'qty' => 2])
            ->assertOk();

        $cart = Cart::query()->where('user_id', $user->id)->firstOrFail();
        DB::table('carts')->where('id', $cart->id)->update([
            'updated_at' => now()->subHours(37),
        ]);

        app(AbandonedCartService::class)->process();
        app(AbandonedCartService::class)->process();

        Mail::assertSent(AbandonedCartReminderMail::class, 1);
        $this->assertNotNull($cart->fresh()->reminder_sent_at);
    }

    public function test_cart_mutation_restarts_expiration_and_reminder_cycle(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 20]);

        $this->actingAs($user)
            ->postJson(route('cart.store'), ['product_id' => $product->id, 'qty' => 1])
            ->assertOk();

        $cart = Cart::query()->where('user_id', $user->id)->firstOrFail();
        DB::table('carts')->where('id', $cart->id)->update([
            'reminder_sent_at' => now()->subHour(),
            'updated_at' => now()->subHours(37),
        ]);

        $this->postJson(route('cart.store'), ['product_id' => $product->id, 'qty' => 1])
            ->assertOk();

        $cart->refresh();

        $this->assertNull($cart->reminder_sent_at);
        $this->assertTrue($cart->updated_at->gt(now()->subMinute()));
        $this->assertDatabaseHas('cart_items', [
            'cart_id' => $cart->id,
            'qty' => 2,
        ]);
    }

    public function test_scheduled_cleanup_deletes_expired_cart_without_sending_reminder(): void
    {
        Mail::fake();

        $user = User::factory()->create();
        $product = Product::factory()->create(['stock' => 20]);

        $this->actingAs($user)
            ->postJson(route('cart.store'), ['product_id' => $product->id, 'qty' => 1])
            ->assertOk();

        $cart = Cart::query()->where('user_id', $user->id)->firstOrFail();
        DB::table('carts')->where('id', $cart->id)->update([
            'updated_at' => now()->subHours(49),
        ]);

        app(AbandonedCartService::class)->process();

        Mail::assertNothingSent();
        $this->assertDatabaseMissing('carts', ['id' => $cart->id]);
    }
}
