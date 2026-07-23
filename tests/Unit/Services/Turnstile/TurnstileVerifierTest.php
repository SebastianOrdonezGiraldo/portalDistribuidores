<?php

namespace Tests\Unit\Services\Turnstile;

use App\Services\Turnstile\TurnstileVerifier;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TurnstileVerifierTest extends TestCase
{
    public function test_bypasses_verification_when_disabled(): void
    {
        config(['services.turnstile.enabled' => false]);

        $verifier = app(TurnstileVerifier::class);

        $this->assertTrue($verifier->verify(null));
        $this->assertTrue($verifier->verify(''));
        Http::assertNothingSent();
    }

    public function test_rejects_empty_token_when_enabled(): void
    {
        config([
            'services.turnstile.enabled' => true,
            'services.turnstile.secret_key' => 'secret',
        ]);

        $this->assertFalse(app(TurnstileVerifier::class)->verify(''));
        Http::assertNothingSent();
    }

    public function test_verifies_token_against_cloudflare(): void
    {
        Http::fake([
            'challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => true,
            ], 200),
        ]);

        config([
            'services.turnstile.enabled' => true,
            'services.turnstile.secret_key' => 'test-secret',
        ]);

        $this->assertTrue(app(TurnstileVerifier::class)->verify('tok', '127.0.0.1'));

        Http::assertSent(function ($request): bool {
            return $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
                && $request['secret'] === 'test-secret'
                && $request['response'] === 'tok'
                && $request['remoteip'] === '127.0.0.1';
        });
    }

    public function test_fails_when_cloudflare_rejects_token(): void
    {
        Http::fake([
            'challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => false,
                'error-codes' => ['invalid-input-response'],
            ], 200),
        ]);

        config([
            'services.turnstile.enabled' => true,
            'services.turnstile.secret_key' => 'test-secret',
        ]);

        $this->assertFalse(app(TurnstileVerifier::class)->verify('bad'));
    }
}
