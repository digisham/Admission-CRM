<?php
namespace App\Helpers;

use App\Config\Database;
use Throwable;

class Logger
{
    public static function audit(string $action, string $entityType, ?int $entityId = null, mixed $oldValues = null, mixed $newValues = null): void
    {
        try {
            $db = Database::getConnection();
            $userId = Auth::id();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250);

            $oldJson = $oldValues !== null ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null;
            $newJson = $newValues !== null ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null;

            $stmt = $db->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent) 
                                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $action, $entityType, $entityId, $oldJson, $newJson, $ip, $userAgent]);
        } catch (Throwable $e) {
            // Write to error log file if DB logging fails
            $logFile = dirname(__DIR__, 2) . '/storage/logs/audit_error.log';
            @file_put_contents($logFile, date('Y-m-d H:i:s') . " - " . $e->getMessage() . PHP_EOL, FILE_APPEND);
        }
    }

    public static function log(string $message, string $level = 'info'): void
    {
        $logFile = dirname(__DIR__, 2) . '/storage/logs/app.log';
        $entry = sprintf("[%s] [%s] %s%s", date('Y-m-d H:i:s'), strtoupper($level), $message, PHP_EOL);
        @file_put_contents($logFile, $entry, FILE_APPEND);
    }
}
