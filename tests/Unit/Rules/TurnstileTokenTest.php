<?php

namespace Tests\Unit\Rules;

use App\Rules\TurnstileToken;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class TurnstileTokenTest extends TestCase
{
    public function test_passes_when_turnstile_is_disabled(): void
    {
        config(['services.turnstile.enabled' => false]);

        $validator = Validator::make([], [
            'cf-turnstile-response' => [new TurnstileToken],
        ]);

        $this->assertTrue($validator->passes());
    }

    public function test_fails_when_token_is_missing_and_enabled(): void
    {
        config([
            'services.turnstile.enabled' => true,
            'services.turnstile.secret_key' => 'test-secret',
        ]);

        $validator = Validator::make([], [
            'cf-turnstile-response' => [new TurnstileToken],
        ]);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('cf-turnstile-response', $validator->errors()->toArray());
    }

    public function test_fails_when_cloudflare_rejects_token(): void
    {
        Http::fake([
            'challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
                'success' => false,
            ], 200),
        ]);

        config([
            'services.turnstile.enabled' => true,
            'services.turnstile.secret_key' => 'test-secret',
        ]);

        $validator = Validator::make([
            'cf-turnstile-response' => 'bad-token',
        ], [
            'cf-turnstile-response' => [new TurnstileToken],
        ]);

        $this->assertTrue($validator->fails());
    }

    public function test_passes_when_cloudflare_accepts_token(): void
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

        $validator = Validator::make([
            'cf-turnstile-response' => 'good-token',
        ], [
            'cf-turnstile-response' => [new TurnstileToken],
        ]);

        $this->assertTrue($validator->passes());
    }
}
