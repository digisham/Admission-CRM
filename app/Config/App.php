<?php
namespace App\Config;

class App
{
    private static array $config = [];
    private static bool $loaded = false;

    public static function init(): void
    {
        if (self::$loaded) {
            return;
        }

        // Load .env file
        $envFile = dirname(__DIR__, 2) . '/.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line) || str_starts_with($line, '#')) {
                    continue;
                }
                if (str_contains($line, '=')) {
                    list($k, $v) = explode('=', $line, 2);
                    $k = trim($k);
                    $v = trim($v, " \t\n\r\0\x0B\"'");
                    self::$config[$k] = $v;
                    if (!isset($_ENV[$k])) {
                        $_ENV[$k] = $v;
                        putenv("$k=$v");
                    }
                }
            }
        }

        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::init();
        return self::$config[$key] ?? $_ENV[$key] ?? getenv($key) ?: $default;
    }

    public static function isDebug(): bool
    {
        return filter_var(self::get('APP_DEBUG', true), FILTER_VALIDATE_BOOLEAN);
    }
}
