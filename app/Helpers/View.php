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

    public static function url(string $path = ''): string
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $base = rtrim(dirname($scriptName), '/\\');
        if ($base === '' || $base === '.') {
            $base = '';
        }
        $path = ltrim($path, '/');
        return $base ? "{$base}/index.php?route={$path}" : "index.php?route={$path}";
    }

    public static function asset(string $path): string
    {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $base = rtrim(dirname($scriptName), '/\\');
        if ($base === '' || $base === '.') {
            $base = '';
        }
        $path = ltrim($path, '/');
        return "{$base}/assets/{$path}";
    }
}
