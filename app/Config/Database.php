<?php
namespace App\Config;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $pdo = null;

    // =========================================================================
    // ⚙️ HOSTING / CPANEL DATABASE CONFIGURATION
    // 
    // Simply change these 4 values to match your hosting database details:
    // =========================================================================
    public static string $host     = 'localhost';              // Usually 'localhost' or '127.0.0.1' on cPanel / Plesk
    public static string $port     = '3306';
    public static string $database = 'u716314755_admissioncrm';   // Your hosting DB Name (e.g. u123456_school_crm)
    public static string $username = 'u716314755_shamshercrm';                   // Your hosting DB User (e.g. u123456_admin)
    public static string $password = 'Shamsher9650@@';                       // Your hosting DB Password
    public static string $charset  = 'utf8mb4';

    public static function getConnection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        App::init();

        // If credentials above were edited away from local defaults, use them directly:
        $isManuallyConfigured = (
            (self::$host !== 'localhost' && self::$host !== '127.0.0.1') ||
            self::$database !== 'school_admission_crm' ||
            self::$username !== 'root' ||
            self::$password !== ''
        );

        if ($isManuallyConfigured) {
            $host    = self::$host;
            $port    = self::$port;
            $db      = self::$database;
            $user    = self::$username;
            $pass    = self::$password;
            $charset = self::$charset;
        } else {
            // Otherwise use .env settings or fall back to defaults:
            $host    = App::get('DB_HOST', self::$host);
            $port    = App::get('DB_PORT', self::$port);
            $db      = App::get('DB_NAME', self::$database);
            $user    = App::get('DB_USER', self::$username);
            $pass    = App::get('DB_PASS', self::$password);
            $charset = App::get('DB_CHARSET', self::$charset);
        }

        $dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$charset} COLLATE {$charset}_unicode_ci",
        ];

        try {
            self::$pdo = new PDO($dsn, $user, $pass, $options);
            return self::$pdo;
        } catch (PDOException $e) {
            if (App::isDebug()) {
                throw new RuntimeException("Database connection error: " . $e->getMessage() . " (Host: {$host}, DB: {$db}, User: {$user})", (int)$e->getCode(), $e);
            }
            throw new RuntimeException("A secure database connection could not be established. Please verify database credentials in app/Config/Database.php.");
        }
    }
}
