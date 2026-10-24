<?php
namespace App\Helpers;

use App\Config\Database;
use App\Models\AcademicSession;

class View
{
    public static function render(string $view, array $data = [], bool $includeLayout = true): void
    {
        Auth::startSession();

        // Extract variables to view scope
        extract($data);

        // Common global variables
        $currentUser = Auth::user();
        $activeSession = AcademicSession::getActive();
        $selectedSessionId = $_SESSION['selected_session_id'] ?? ($activeSession['id'] ?? 1);
        $allSessions = AcademicSession::all();

        $alerts = self::getFlashes();

        // Release PHP session lock immediately so concurrent requests & parallel tabs do not block
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }

        $viewPath = dirname(__DIR__) . '/Views/' . $view . '.php';

        if (!file_exists($viewPath)) {
            http_response_code(500);
            echo "View template [{$view}] not found.";
            return;
        }

        if ($includeLayout) {
            require dirname(__DIR__) . '/Views/layouts/header.php';
            require $viewPath;
            require dirname(__DIR__) . '/Views/layouts/footer.php';
        } else {
            require $viewPath;
        }
    }

    public static function json(mixed $data, int $statusCode = 200): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public static function redirect(string $url, ?string $flashType = null, ?string $flashMessage = null): void
    {
        Auth::startSession();
        if ($flashType && $flashMessage) {
            $_SESSION['flashes'][$flashType] = $flashMessage;
        }
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_write_close();
        }
        header("Location: {$url}");
        exit;
    }

    public static function flash(string $type, string $message): void
    {
        Auth::startSession();
        $_SESSION['flashes'][$type] = $message;
    }

    public static function getFlashes(): array
    {
        Auth::startSession();
        $flashes = $_SESSION['flashes'] ?? [];
        unset($_SESSION['flashes']);
        return $flashes;
    }

    public static function baseUrl(): string
    {
        $cleanScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $dir = str_replace('\\', '/', dirname($cleanScript));
        $base = rtrim($dir, '/');
        if ($base === '' || $base === '.' || $base === '/') {
            return '';
        }
        return $base;
    }

    public static function url(string $path = ''): string
    {
        $base = self::baseUrl();
        $path = ltrim($path, '/');

        // Extract route and query string properly whether separated by '?' or '&'
        $queryString = '';
        if (strpos($path, '?') !== false) {
            [$route, $qs] = explode('?', $path, 2);
            $path = $route;
            if ($qs !== '') {
                $queryString = '&' . $qs;
            }
        } elseif (strpos($path, '&') !== false) {
            [$route, $qs] = explode('&', $path, 2);
            $path = $route;
            if ($qs !== '') {
                $queryString = '&' . $qs;
            }
        }

        $basePrefix = $base !== '' ? $base : '';

        // If path is enquire or inquire, produce clean URL for advertising and sharing
        if (in_array($path, ['enquire', 'inquire'])) {
            $qsClean = ltrim($queryString, '&');
            $qsPart = $qsClean !== '' ? "?{$qsClean}" : '';
            return $basePrefix !== '' ? "{$basePrefix}/enquire{$qsPart}" : "/enquire{$qsPart}";
        }

        if ($path === '' && $queryString === '') {
            return $basePrefix !== '' ? "{$basePrefix}/index.php" : "/index.php";
        }

        if ($path === '' && $queryString !== '') {
            $qsClean = ltrim($queryString, '&');
            return $basePrefix !== '' ? "{$basePrefix}/index.php?{$qsClean}" : "/index.php?{$qsClean}";
        }

        return $basePrefix !== '' 
            ? "{$basePrefix}/index.php?route={$path}{$queryString}" 
            : "/index.php?route={$path}{$queryString}";
    }

    public static function asset(string $path): string
    {
        $cleanScript = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $dir = str_replace('\\', '/', dirname($cleanScript));
        $base = rtrim($dir, '/');
        if ($base === '' || $base === '.' || $base === '/') {
            $base = '';
        }
        $path = ltrim($path, '/');

        // Auto cache-buster based on file modification time
        $rootDir = dirname(__DIR__, 2);
        $fullPath = $rootDir . '/public/assets/' . $path;
        if (!file_exists($fullPath)) {
            $fullPath = $rootDir . '/assets/' . $path;
        }

        // Also check interchangeable aliases: images <-> img
        if (!file_exists($fullPath)) {
            if (str_starts_with($path, 'images/')) {
                $altPath = 'img/' . substr($path, 7);
            } elseif (str_starts_with($path, 'img/')) {
                $altPath = 'images/' . substr($path, 4);
            } else {
                $altPath = null;
            }
            if ($altPath) {
                if (file_exists($rootDir . '/public/assets/' . $altPath)) {
                    $path = $altPath;
                    $fullPath = $rootDir . '/public/assets/' . $altPath;
                } elseif (file_exists($rootDir . '/assets/' . $altPath)) {
                    $path = $altPath;
                    $fullPath = $rootDir . '/assets/' . $altPath;
                }
            }
        }

        $v = file_exists($fullPath) ? '?v=' . filemtime($fullPath) : ('?v=' . time());

        // When running at the root of a domain or inside /public
        if ($base === '' || str_ends_with($base, '/public')) {
            $prefix = ($base !== '' ? $base : '');
            if (file_exists($rootDir . '/assets/' . $path)) {
                return "{$prefix}/assets/{$path}{$v}";
            }
            return "{$prefix}/public/assets/{$path}{$v}";
        }

        // When running in a subdirectory (e.g. http://localhost/CRM/)
        if (is_dir($rootDir . '/public/assets')) {
            return "{$base}/public/assets/{$path}{$v}";
        }

        return "{$base}/assets/{$path}{$v}";
    }
}
