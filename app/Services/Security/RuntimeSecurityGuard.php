<?php

namespace App\Services\Security;

use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Detects unsafe production-like runtime configuration (debug, cookies,
 * APP_URL, database TLS, public private-file disks).
 */
class RuntimeSecurityGuard
{
    public const NON_CANONICAL_APP_URL = 'APP_URL debe apuntar a un host canonico fuera de local/testing.';

    /**
     * @return list<string>
     */
    public function violations(): array
    {
        $violations = [];

        if ((bool) config('app.debug')) {
            $violations[] = 'APP_DEBUG debe estar deshabilitado fuera de local/testing.';
        }

        if (config('session.secure') !== true) {
            $violations[] = 'SESSION_SECURE_COOKIE debe ser true fuera de local/testing.';
        }

        if (! $this->hasCanonicalAppUrl((string) config('app.url'))) {
            $violations[] = self::NON_CANONICAL_APP_URL;
        }

        if (! $this->usesSecureDatabaseTransport()) {
            $violations[] = 'DB_SSLMODE debe ser require, verify-ca o verify-full fuera de local/testing.';
        }

        if ((string) config('filesystems.order_pdfs_disk', 'private') === 'public') {
            $violations[] = 'Los PDFs de pedidos no pueden usar el disco public fuera de local/testing.';
        }

        if ((string) config('filesystems.tech_sheets_disk', 'private') === 'public') {
            $violations[] = 'Las fichas tecnicas no pueden usar el disco public fuera de local/testing.';
        }

        return $violations;
    }

    /**
     * Log violations and, when app.enforce_runtime_guards is on, stop the boot.
     *
     * @param  list<string>  $violations
     */
    public function handle(array $violations): void
    {
        if ($violations === []) {
            return;
        }

        Log::critical('runtime.security_configuration_invalid', [
            'environment' => config('app.env'),
            'violations' => $violations,
            'app_url' => config('app.url'),
            'session_secure' => config('session.secure'),
            'db_connection' => config('database.default'),
            'db_sslmode' => config('database.connections.'.config('database.default').'.sslmode'),
            'order_pdfs_disk' => config('filesystems.order_pdfs_disk', 'private'),
            'tech_sheets_disk' => config('filesystems.tech_sheets_disk', 'private'),
        ]);

        if ((bool) config('app.enforce_runtime_guards', false)) {
            throw new RuntimeException(implode(' | ', $violations));
        }
    }

    /**
     * Reject local, placeholder and tunnel hosts for production-like APP_URL.
     */
    public function hasCanonicalAppUrl(string $appUrl): bool
    {
        $host = parse_url($appUrl, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return false;
        }

        $host = strtolower($host);

        if (in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return false;
        }

        foreach (['.test', '.localhost', '.invalid', '.example', '.ngrok.io', '.ngrok-free.dev'] as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Require encrypted PostgreSQL transport outside local/testing environments.
     */
    private function usesSecureDatabaseTransport(): bool
    {
        $defaultConnection = (string) config('database.default');
        $connection = config("database.connections.{$defaultConnection}", []);
        $driver = strtolower((string) ($connection['driver'] ?? ''));

        if ($driver !== 'pgsql') {
            return true;
        }

        $sslMode = strtolower((string) ($connection['sslmode'] ?? ''));

        return in_array($sslMode, ['require', 'verify-ca', 'verify-full'], true);
    }
}
