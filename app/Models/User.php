<?php
namespace App\Models;

use App\Config\Database;
use App\Helpers\Logger;
use PDO;

class User
{
    public static function all(bool $activeOnly = false): array
    {
        $db = Database::getConnection();
        $sql = "SELECT u.*, r.name as role_name, r.slug as role_slug 
                FROM users u 
                JOIN roles r ON u.role_id = r.id";
        if ($activeOnly) {
            $sql .= " WHERE u.is_active = 1";
        }
        $sql .= " ORDER BY u.role_id ASC, u.name ASC";
        return $db->query($sql)->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT u.*, r.name as role_name, r.slug as role_slug 
                              FROM users u 
                              JOIN roles r ON u.role_id = r.id 
                              WHERE u.id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findByEmail(string $email): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT u.*, r.name as role_name, r.slug as role_slug, r.permissions 
                              FROM users u 
                              JOIN roles r ON u.role_id = r.id 
                              WHERE u.email = ? LIMIT 1");
        $stmt->execute([trim(strtolower($email))]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function getActiveCounselors(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT u.id, u.name, u.email, u.phone, u.designation, r.slug as role_slug
                            FROM users u 
                            JOIN roles r ON u.role_id = r.id 
                            WHERE u.is_active = 1 AND r.slug IN ('employee', 'admin', 'super_admin')
                            ORDER BY u.name ASC");
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $db = Database::getConnection();
        $passwordHash = password_hash($data['password'], PASSWORD_DEFAULT);

        $stmt = $db->prepare("INSERT INTO users (role_id, name, email, password, phone, designation, is_active, created_by)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['role_id'],
            $data['name'],
            trim(strtolower($data['email'])),
            $passwordHash,
            $data['phone'] ?? null,
            $data['designation'] ?? null,
            isset($data['is_active']) ? (int)$data['is_active'] : 1,
            $data['created_by'] ?? null
        ]);

        $newId = (int)$db->lastInsertId();
        Logger::audit('user_create', 'user', $newId, null, ['name' => $data['name'], 'email' => $data['email']]);
        return $newId;
    }

    public static function update(int $id, array $data): bool
    {
        $db = Database::getConnection();
        $old = self::find($id);

        $params = [
            $data['role_id'],
            $data['name'],
            trim(strtolower($data['email'])),
            $data['phone'] ?? null,
            $data['designation'] ?? null,
            isset($data['is_active']) ? (int)$data['is_active'] : 1,
        ];

        $sql = "UPDATE users SET role_id = ?, name = ?, email = ?, phone = ?, designation = ?, is_active = ?";

        if (!empty($data['password'])) {
            $sql .= ", password = ?";
            $params[] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $sql .= " WHERE id = ?";
        $params[] = $id;

        $stmt = $db->prepare($sql);
        $res = $stmt->execute($params);

        if ($res) {
            Logger::audit('user_update', 'user', $id, $old, $data);
        }
        return $res;
    }

    public static function getActiveRecordCounts(int $userId): array
    {
        $db = Database::getConnection();
        $leadsStmt = $db->prepare("SELECT COUNT(*) FROM leads WHERE assigned_to = ? AND is_archived = 0 AND status NOT IN ('Admission Confirmed', 'Lost / Unreachable')");
        $leadsStmt->execute([$userId]);
        $activeLeads = (int)$leadsStmt->fetchColumn();

        $tasksStmt = $db->prepare("SELECT COUNT(*) FROM tasks WHERE assigned_to = ? AND status IN ('Pending', 'In Progress')");
        $tasksStmt->execute([$userId]);
        $pendingTasks = (int)$tasksStmt->fetchColumn();

        return [
            'active_leads' => $activeLeads,
            'pending_tasks' => $pendingTasks,
        ];
    }

    public static function getWorkload(?int $sessionId = null): array
    {
        $db = Database::getConnection();
        $sessionFilter = $sessionId ? "AND l.session_id = " . (int)$sessionId : "";

        $sql = "SELECT u.id, u.name, u.email, u.designation, r.name as role_name,
                       COUNT(DISTINCT CASE WHEN l.is_archived = 0 {$sessionFilter} THEN l.id END) as total_leads,
                       COUNT(DISTINCT CASE WHEN l.is_archived = 0 AND l.status NOT IN ('Admission Confirmed', 'Lost / Unreachable') {$sessionFilter} THEN l.id END) as active_leads,
                       COUNT(DISTINCT CASE WHEN l.is_archived = 0 AND l.status = 'Admission Confirmed' {$sessionFilter} THEN l.id END) as confirmed_leads,
                       COUNT(DISTINCT CASE WHEN t.status IN ('Pending', 'In Progress') THEN t.id END) as pending_tasks,
                       COUNT(DISTINCT CASE WHEN t.status IN ('Pending', 'In Progress') AND t.due_date < NOW() THEN t.id END) as overdue_tasks
                FROM users u
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN leads l ON l.assigned_to = u.id
                LEFT JOIN tasks t ON t.assigned_to = u.id
                WHERE u.is_active = 1 AND r.slug IN ('employee', 'admin', 'super_admin')
                GROUP BY u.id
                ORDER BY active_leads DESC, u.name ASC";
        return $db->query($sql)->fetchAll();
    }
}
