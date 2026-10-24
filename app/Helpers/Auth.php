<?php
namespace App\Helpers;

use App\Config\Database;
use PDO;

class Auth
{
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.use_strict_mode', '1');
            
            $isSecure = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on');
            if ($isSecure) {
                ini_set('session.cookie_secure', '1');
            }

            if (PHP_VERSION_ID >= 70300) {
                session_set_cookie_params([
                    'lifetime' => 0,
                    'path' => '/',
                    'domain' => '',
                    'secure' => $isSecure,
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]);
            }
            @session_start();
        }
    }

    public static function login(array $user): void
    {
        self::startSession();
        // Regenerate session id to protect against session fixation attacks
        session_regenerate_id(true);

        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['role_id'] = (int)$user['role_id'];
        $_SESSION['role_slug'] = $user['role_slug'] ?? 'employee';
        $_SESSION['logged_in_at'] = time();

        // Update last_login_at in database
        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("UPDATE users SET last_login_at = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
        } catch (\Throwable) {
            // Non-critical update failure
        }
    }

    private static ?array $cachedUser = null;

    public static function resetCache(): void
    {
        self::$cachedUser = null;
    }

    public static function logout(): void
    {
        self::startSession();
        self::$cachedUser = null;
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }

    public static function check(): bool
    {
        self::startSession();
        return !empty($_SESSION['user_id']);
    }

    public static function requireLogin(): void
    {
        self::startSession();
        if (!self::check()) {
            View::redirect(View::url('login'));
        }
    }

    public static function user(): ?array
    {
        self::startSession();
        if (!self::check()) {
            return null;
        }

        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT u.*, r.name as role_name, r.slug as role_slug, r.permissions 
                                  FROM users u 
                                  JOIN roles r ON u.role_id = r.id 
                                  WHERE u.id = ? AND u.is_active = 1 LIMIT 1");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            if (!$user) {
                self::logout();
                return null;
            }
            self::$cachedUser = $user;
            return $user;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function id(): ?int
    {
        self::startSession();
        return $_SESSION['user_id'] ?? null;
    }

    public static function role(): string
    {
        $user = self::user();
        return $user['role_slug'] ?? 'guest';
    }

    public static function isSuperAdmin(): bool
    {
        return self::role() === 'super_admin';
    }

    public static function isAdmin(): bool
    {
        $role = self::role();
        return $role === 'super_admin' || $role === 'admin';
    }

    public static function isEmployee(): bool
    {
        return self::role() === 'employee';
    }

    public static function isReadOnly(): bool
    {
        return self::role() === 'read_only';
    }

    public static function hasPermission(string $permission): bool
    {
        $user = self::user();
        if (!$user) {
            return false;
        }
        if ($user['role_slug'] === 'super_admin') {
            return true;
        }

        $permissions = json_decode($user['permissions'] ?? '[]', true) ?: [];
        if (in_array('*', $permissions, true) || in_array($permission, $permissions, true)) {
            return true;
        }

        return false;
    }

    public static function canAccessLead(array|object $lead): bool
    {
        if (self::isAdmin()) {
            return true;
        }
        $assignedTo = is_array($lead) ? ($lead['assigned_to'] ?? null) : ($lead->assigned_to ?? null);
        return (int)$assignedTo === (int)self::id();
    }
}
