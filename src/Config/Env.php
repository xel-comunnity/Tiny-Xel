<?php

declare(strict_types=1);

namespace Tiny\Xel\Config;

use Dotenv\Dotenv;

/**
 * Loads a .env file once at boot and reads typed config values out of it,
 * so credentials and per-environment settings (db host/user/password, which
 * db driver contract to use, ...) never have to be hardcoded into a config
 * file that gets committed to the repo.
 *
 * This is boot-time, process-wide, read-only configuration - not per-request
 * state. Unlike a real per-request superglobal ($_GET/$_POST, which this
 * framework deliberately never reads directly - see RequestContext -
 * because it would be shared, mutable state across concurrent coroutines),
 * environment variables are loaded once before the Swoole server even
 * starts and never change afterward, so every coroutine reading them
 * concurrently is safe.
 *
 * Usage (once, at boot - e.g. the top of test/server.php, test/migrate.php,
 * test/seed.php):
 *
 *   Env::load(__DIR__);
 *
 * Then in any config file:
 *
 *   "host" => Env::get("DB_HOST", "localhost"),
 *   "port" => Env::get("DB_PORT", 3306),
 *
 * If no .env file exists (e.g. production, where a container/host already
 * injects real environment variables), load() does nothing and get() still
 * reads getenv()/$_ENV directly - a .env file is a local-dev convenience,
 * never a requirement.
 */
final class Env
{
    private static bool $loaded = false;

    /**
     * @param string $path directory containing the .env file (not the file itself)
     * @param string $filename defaults to ".env"
     */
    public static function load(string $path, string $filename = '.env'): void
    {
        if (self::$loaded) {
            return;
        }

        // ? immutable: a real environment variable the host/container
        // ? already set always wins over the .env file's value.
        // ? safeLoad(): a missing .env file is not an error.
        Dotenv::createImmutable($path, $filename)->safeLoad();

        self::$loaded = true;
    }

    /**
     * Resets the "already loaded" guard. Only useful for tests that need to
     * reload a different .env file within the same process.
     */
    public static function reset(): void
    {
        self::$loaded = false;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $value === '' ? '' : $default;
        }

        if (!is_string($value)) {
            return $value;
        }

        return match (strtolower($value)) {
            'true' => true,
            'false' => false,
            'null' => null,
            default => is_numeric($value)
                ? (str_contains($value, '.') ? (float) $value : (int) $value)
                : $value,
        };
    }
}
