<?php
namespace App\Config;

use PDO;
use PDOException;
use RuntimeException;

class Database
{
    private static ?PDO $pdo = null;

    public static function getConnection(): PDO
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }

        App::init();

        $host = App::get('DB_HOST', '127.0.0.1');
        $port = App::get('DB_PORT', '3306');
        $db   = App::get('DB_NAME', 'school_admission_crm');
        $user = App::get('DB_USER', 'root');
        $pass = App::get('DB_PASS', '');
        $charset = App::get('DB_CHARSET', 'utf8mb4');

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
                throw new RuntimeException("Database connection error: " . $e->getMessage(), (int)$e->getCode(), $e);
            }
            throw new RuntimeException("A secure database connection could not be established. Please verify server configuration.");
        }
    }
}
