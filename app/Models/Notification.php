<?php
namespace App\Models;

use App\Config\Database;
use App\Helpers\Auth;
use App\Helpers\Logger;
use PDO;

class Notification
{
    public static function create(array $data): int
    {
        $db = Database::getConnection();
        $senderId = $data['sender_id'] ?? Auth::id() ?? 1;

        $stmt = $db->prepare("INSERT INTO notifications (user_id, sender_id, lead_id, type, title, message, link)
                              VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            (int)$data['user_id'],
            $senderId,
            !empty($data['lead_id']) ? (int)$data['lead_id'] : null,
            $data['type'] ?? 'general',
            $data['title'],
            $data['message'],
            $data['link'] ?? null
        ]);

        return (int)$db->lastInsertId();
    }

    public static function notifyAdmins(array $data, ?int $excludeUserId = null): void
    {
        $db = Database::getConnection();
        $sql = "SELECT u.id FROM users u 
                JOIN roles r ON u.role_id = r.id 
                WHERE u.is_active = 1 AND r.slug IN ('super_admin', 'admin')";
        if ($excludeUserId) {
            $sql .= " AND u.id != " . (int)$excludeUserId;
        }

        $admins = $db->query($sql)->fetchAll();
        foreach ($admins as $admin) {
            $notifData = $data;
            $notifData['user_id'] = $admin['id'];
            self::create($notifData);
        }
    }

    public static function notifyAllStaff(array $data, ?int $excludeUserId = null): void
    {
        $db = Database::getConnection();
        $sql = "SELECT u.id FROM users u WHERE u.is_active = 1";
        if ($excludeUserId) {
            $sql .= " AND u.id != " . (int)$excludeUserId;
        }

        $users = $db->query($sql)->fetchAll();
        foreach ($users as $u) {
            $notifData = $data;
            $notifData['user_id'] = $u['id'];
            self::create($notifData);
        }
    }

    public static function getUnreadCount(int $userId): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public static function timeAgo(string $datetime): string
    {
        $time = strtotime($datetime);
        $diff = time() - $time;
        if ($diff < 10) return 'Just now';
        if ($diff < 60) return $diff . 's ago';
        if ($diff < 3600) return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        if ($diff < 604800) return floor($diff / 86400) . 'd ago';
        return date('M d, h:i A', $time);
    }

    public static function getUnreadForUser(int $userId, int $limit = 35): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT n.*, sender.name as sender_name, 
                                     l.student_name, l.parent_name, l.lead_ref, 
                                     l.class_applied, l.class_applied as class_applying_for, 
                                     l.status as lead_status, l.priority as lead_priority,
                                     l.mobile, l.mobile as primary_mobile
                              FROM notifications n
                              LEFT JOIN users sender ON n.sender_id = sender.id
                              LEFT JOIN leads l ON n.lead_id = l.id
                              WHERE n.user_id = ? AND n.is_read = 0
                              ORDER BY n.created_at DESC
                              LIMIT ?");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        foreach ($rows as &$r) {
            $r['time_ago'] = self::timeAgo($r['created_at']);
            $r['created_formatted'] = date('M d, Y h:i A', strtotime($r['created_at']));
        }
        return $rows;
    }

    public static function getAllForUser(int $userId, int $limit = 35): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT n.*, sender.name as sender_name, 
                                     l.student_name, l.parent_name, l.lead_ref, 
                                     l.class_applied, l.class_applied as class_applying_for, 
                                     l.status as lead_status, l.priority as lead_priority,
                                     l.mobile, l.mobile as primary_mobile
                              FROM notifications n
                              LEFT JOIN users sender ON n.sender_id = sender.id
                              LEFT JOIN leads l ON n.lead_id = l.id
                              WHERE n.user_id = ?
                              ORDER BY n.created_at DESC
                              LIMIT ?");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll();

        foreach ($rows as &$r) {
            $r['time_ago'] = self::timeAgo($r['created_at']);
            $r['created_formatted'] = date('M d, Y h:i A', strtotime($r['created_at']));
        }
        return $rows;
    }

    public static function markAsRead(int $id, int $userId): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM notifications WHERE id = ? AND user_id = ? LIMIT 1");
        $stmt->execute([$id, $userId]);
        $notif = $stmt->fetch();

        if ($notif) {
            $updateStmt = $db->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE id = ?");
            $updateStmt->execute([$id]);
        }

        return $notif ?: null;
    }

    public static function markAllAsRead(int $userId): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("UPDATE notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0");
        $stmt->execute([$userId]);
    }
}
