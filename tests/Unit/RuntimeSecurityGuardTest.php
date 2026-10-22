<?php

namespace Tests\Unit;

use App\Services\Security\RuntimeSecurityGuard;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\TestCase;

class RuntimeSecurityGuardTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.debug' => false,
            'app.url' => 'https://pedidos.importcorporalmedical.com',
            'app.enforce_runtime_guards' => false,
            'session.secure' => true,
            'database.default' => 'pgsql',
            'database.connections.pgsql.driver' => 'pgsql',
            'database.connections.pgsql.sslmode' => 'require',
            'filesystems.order_pdfs_disk' => 'private',
            'filesystems.tech_sheets_disk' => 'private',
        ]);
    }

    public function test_a_safe_configuration_has_no_violations(): void
    {
        $this->assertSame([], (new RuntimeSecurityGuard)->violations());
    }

    public function test_it_reports_every_unsafe_setting(): void
    {
        config([
            'app.debug' => true,
            'app.url' => 'http://localhost',
            'session.secure' => false,
            'database.connections.pgsql.sslmode' => 'prefer',
            'filesystems.order_pdfs_disk' => 'public',
            'filesystems.tech_sheets_disk' => 'public',
        ]);

        $violations = (new RuntimeSecurityGuard)->violations();

        $this->assertCount(6, $violations);
        $this->assertContains(RuntimeSecurityGuard::NON_CANONICAL_APP_URL, $violations);
    }

    public function test_database_tls_is_only_required_for_postgres(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.driver' => 'sqlite',
        ]);

        $this->assertSame([], (new RuntimeSecurityGuard)->violations());
    }

    public function test_app_url_must_use_a_canonical_host(): void
    {
        $guard = new RuntimeSecurityGuard;

        $this->assertTrue($guard->hasCanonicalAppUrl('https://staging-pedidos.importcorporalmedical.com'));

        foreach ([
            '',
            'not-a-url',
            'http://localhost:8000',
            'http://127.0.0.1',
            'https://portal.test',
            'https://shop.example',
            'https://abc.ngrok-free.dev',
        ] as $url) {
            $this->assertFalse($guard->hasCanonicalAppUrl($url), $url);
        }
    }

    public function test_violations_are_logged_without_stopping_the_boot_by_default(): void
    {
        Log::spy();

        (new RuntimeSecurityGuard)->handle(['APP_DEBUG debe estar deshabilitado fuera de local/testing.']);

        Log::shouldHaveReceived('critical')->once()->withArgs(
            fn (string $message, array $context): bool => $message === 'runtime.security_configuration_invalid'
                && $context['violations'] === ['APP_DEBUG debe estar deshabilitado fuera de local/testing.'],
        );
    }

    public function test_violations_stop_the_boot_when_guards_are_enforced(): void
    {
        config(['app.enforce_runtime_guards' => true]);
        Log::spy();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('A | B');

        (new RuntimeSecurityGuard)->handle(['A', 'B']);
    }

    public function test_no_violations_means_nothing_is_logged(): void
    {
        Log::spy();

        (new RuntimeSecurityGuard)->handle([]);

        Log::shouldNotHaveReceived('critical');
    }
}
