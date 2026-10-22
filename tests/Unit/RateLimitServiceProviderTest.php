<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RateLimitServiceProviderTest extends TestCase
{
    /**
     * @return array<string, array{string, string, list<int>, list<int>}>
     */
    public static function perActorLimiters(): array
    {
        return [
            'api' => ['api-endpoints', 'api', [60, 600], [180, 3600]],
            'catalog' => ['catalog-scraping', 'catalog', [40, 450], [120, 2400]],
            'ai' => ['ai-generation', 'ai', [3, 30], [30, 500]],
        ];
    }

    /**
     * @param  list<int>  $guestLimits
     * @param  list<int>  $authLimits
     */
    #[DataProvider('perActorLimiters')]
    public function test_per_actor_limiters_use_their_defaults_and_keys(string $name, string $prefix, array $guestLimits, array $authLimits): void
    {
        $guest = Request::create('/', 'GET', server: ['REMOTE_ADDR' => '10.0.0.1', 'HTTP_USER_AGENT' => 'Bot']);
        $guestActor = 'guest:10.0.0.1:'.substr(sha1('bot'), 0, 12);

        $this->assertLimits(
            RateLimiter::limiter($name)($guest),
            [[$guestLimits[0], 60, "{$prefix}:minute:{$guestActor}"], [$guestLimits[1], 3600, "{$prefix}:hour:{$guestActor}"]],
        );

        $user = new User;
        $user->id = 42;
        $authenticated = Request::create('/');
        $authenticated->setUserResolver(fn () => $user);

        $this->assertLimits(
            RateLimiter::limiter($name)($authenticated),
            [[$authLimits[0], 60, "{$prefix}:minute:user:42"], [$authLimits[1], 3600, "{$prefix}:hour:user:42"]],
        );
    }

    public function test_per_actor_limiters_read_their_config(): void
    {
        config(['abuse_protection.ai_generation.guest_per_minute' => 7]);

        $limits = RateLimiter::limiter('ai-generation')(Request::create('/'));

        $this->assertSame(7, $limits[0]->maxAttempts);
    }

    public function test_login_and_registration_limiters_are_registered(): void
    {
        $request = Request::create('/', 'POST', ['email' => ' Foo@Example.com '], server: ['REMOTE_ADDR' => '10.0.0.2']);

        $this->assertLimits(RateLimiter::limiter('login-attempts')($request), [
            [20, 60, 'login:minute:10.0.0.2'],
            [250, 3600, 'login:hour:10.0.0.2'],
        ]);
        $this->assertLimits(RateLimiter::limiter('account-creation')($request), [
            [5, 3600, 'register:hour:ip:10.0.0.2'],
            [25, 86400, 'register:day:ip:10.0.0.2'],
            [3, 3600, 'register:hour:email:foo@example.com:10.0.0.2'],
        ]);
    }

    /**
     * @param  list<Limit>  $limits
     * @param  list<array{int, int, string}>  $expected
     */
    private function assertLimits(array $limits, array $expected): void
    {
        $this->assertSame(
            $expected,
            array_map(fn (Limit $limit): array => [$limit->maxAttempts, $limit->decaySeconds, $limit->key], $limits),
        );
    }
}
