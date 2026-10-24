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

    public static function getAdmissionCounselors(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT u.id, u.name, u.email, u.phone, u.designation, r.slug as role_slug
                            FROM users u 
                            JOIN roles r ON u.role_id = r.id 
                            WHERE u.is_active = 1 AND r.slug = 'employee'
                            ORDER BY u.id ASC");
        $counselors = $stmt->fetchAll();
        if (empty($counselors)) {
            // Fallback to active staff if no counselors defined yet
            $stmt = $db->query("SELECT u.id, u.name, u.email, u.phone, u.designation, r.slug as role_slug
                                FROM users u 
                                JOIN roles r ON u.role_id = r.id 
                                WHERE u.is_active = 1 AND r.slug IN ('employee', 'admin')
                                ORDER BY u.id ASC");
            $counselors = $stmt->fetchAll();
        }
        return $counselors;
    }

    public static function getAdmissionManagers(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT u.id, u.name, u.email, u.phone, u.designation, r.slug as role_slug, r.name as role_name
                            FROM users u 
                            JOIN roles r ON u.role_id = r.id 
                            WHERE u.is_active = 1 AND r.slug IN ('admin', 'super_admin')
                            ORDER BY CASE WHEN r.slug = 'admin' THEN 1 ELSE 2 END, u.name ASC");
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

        $tasksStmt = $db->prepare("SELECT COUNT(*) FROM tasks t JOIN leads l ON t.lead_id = l.id WHERE t.assigned_to = ? AND l.is_archived = 0 AND t.status IN ('Pending', 'In Progress')");
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

        // 1. Fetch active target users
        $usersStmt = $db->query("
            SELECT u.id, u.name, u.email, u.designation, r.name as role_name
            FROM users u
            JOIN roles r ON u.role_id = r.id
            WHERE u.is_active = 1 AND r.slug IN ('employee', 'admin', 'super_admin')
        ");
        $users = $usersStmt->fetchAll(PDO::FETCH_ASSOC);

        // 2. Aggregate leads counts by counselor
        $leadsStmt = $db->query("
            SELECT l.assigned_to,
                   COUNT(*) as total_leads,
                   SUM(CASE WHEN l.status NOT IN ('Admission Confirmed', 'Lost / Unreachable') THEN 1 ELSE 0 END) as active_leads,
                   SUM(CASE WHEN l.status = 'Admission Confirmed' THEN 1 ELSE 0 END) as confirmed_leads
            FROM leads l
            WHERE l.is_archived = 0 {$sessionFilter} AND l.assigned_to IS NOT NULL
            GROUP BY l.assigned_to
        ");
        $leadsByUser = [];
        foreach ($leadsStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $leadsByUser[$row['assigned_to']] = $row;
        }

        // 3. Aggregate tasks counts by counselor (strictly assigned leads with at least 1 activity)
        $tasksStmt = $db->query("
            SELECT l.assigned_to,
                   COUNT(*) as pending_tasks,
                   SUM(CASE WHEN t.due_date < NOW() THEN 1 ELSE 0 END) as overdue_tasks
            FROM tasks t
            JOIN leads l ON t.lead_id = l.id AND l.is_archived = 0
            WHERE t.status IN ('Pending', 'In Progress')
              AND (
                  t.description NOT LIKE '%Excel Import lead follow-up%' 
                  OR l.status != 'New'
                  OR EXISTS (SELECT 1 FROM follow_up_logs f WHERE f.lead_id = l.id)
                  OR EXISTS (SELECT 1 FROM audit_logs a WHERE a.entity_type = 'lead' AND a.entity_id = l.id AND a.action IN ('lead_status_update', 'lead_priority_update', 'lead_update'))
              )
            GROUP BY l.assigned_to
        ");
        $tasksByUser = [];
        foreach ($tasksStmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $tasksByUser[$row['assigned_to']] = $row;
        }

        // 4. Combine in memory
        $workload = [];
        foreach ($users as $u) {
            $uid = (int)$u['id'];
            $lData = $leadsByUser[$uid] ?? [];
            $tData = $tasksByUser[$uid] ?? [];

            $workload[] = [
                'id' => $uid,
                'name' => $u['name'],
                'email' => $u['email'],
                'designation' => $u['designation'],
                'role_name' => $u['role_name'],
                'total_leads' => (int)($lData['total_leads'] ?? 0),
                'active_leads' => (int)($lData['active_leads'] ?? 0),
                'confirmed_leads' => (int)($lData['confirmed_leads'] ?? 0),
                'pending_tasks' => (int)($tData['pending_tasks'] ?? 0),
                'overdue_tasks' => (int)($tData['overdue_tasks'] ?? 0),
            ];
        }

        usort($workload, function ($a, $b) {
            if ($b['active_leads'] !== $a['active_leads']) {
                return $b['active_leads'] <=> $a['active_leads'];
            }
            return strcasecmp($a['name'], $b['name']);
        });

        return $workload;
    }

    public static function updatePassword(int $userId, string $newPassword): bool
    {
        $db = Database::getConnection();
        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        return $stmt->execute([$hash, $userId]);
    }

    public static function updateProfile(int $id, array $data): bool
    {
        $db = Database::getConnection();
        $old = self::find($id);
        $stmt = $db->prepare("UPDATE users SET name = ?, phone = ?, designation = COALESCE(?, designation), updated_at = NOW() WHERE id = ?");
        $res = $stmt->execute([
            trim($data['name']),
            !empty($data['phone']) ? trim($data['phone']) : null,
            !empty($data['designation']) ? trim($data['designation']) : null,
            $id
        ]);
        if ($res) {
            Logger::audit('user_profile_update', 'user', $id, $old, $data);
        }
        return $res;
    }

    public static function getProfileStats(int $userId): array
    {
        $db = Database::getConnection();

        $stmtLeads = $db->prepare("SELECT 
            COUNT(*) as total_leads,
            COUNT(CASE WHEN status NOT IN ('Admission Confirmed', 'Lost / Unreachable') THEN 1 END) as active_leads,
            COUNT(CASE WHEN status = 'Admission Confirmed' THEN 1 END) as confirmed_leads
            FROM leads WHERE assigned_to = ? AND is_archived = 0");
        $stmtLeads->execute([$userId]);
        $leadStats = $stmtLeads->fetch() ?: ['total_leads' => 0, 'active_leads' => 0, 'confirmed_leads' => 0];

        $stmtTasks = $db->prepare("SELECT 
            COUNT(*) as total_tasks,
            COUNT(CASE WHEN t.status IN ('Pending', 'In Progress') THEN 1 END) as pending_tasks,
            COUNT(CASE WHEN t.status = 'Completed' THEN 1 END) as completed_tasks,
            COUNT(CASE WHEN t.status IN ('Pending', 'In Progress') AND t.due_date < NOW() THEN 1 END) as overdue_tasks
            FROM tasks t
            JOIN leads l ON t.lead_id = l.id
            WHERE t.assigned_to = ? AND l.is_archived = 0");
        $stmtTasks->execute([$userId]);
        $taskStats = $stmtTasks->fetch() ?: ['total_tasks' => 0, 'pending_tasks' => 0, 'completed_tasks' => 0, 'overdue_tasks' => 0];

        $stmtLogs = $db->prepare("SELECT * FROM audit_logs WHERE user_id = ? ORDER BY id DESC LIMIT 10");
        $stmtLogs->execute([$userId]);
        $recentLogs = $stmtLogs->fetchAll() ?: [];

        return [
            'total_leads' => (int)($leadStats['total_leads'] ?? 0),
            'active_leads' => (int)($leadStats['active_leads'] ?? 0),
            'confirmed_leads' => (int)($leadStats['confirmed_leads'] ?? 0),
            'total_tasks' => (int)($taskStats['total_tasks'] ?? 0),
            'pending_tasks' => (int)($taskStats['pending_tasks'] ?? 0),
            'completed_tasks' => (int)($taskStats['completed_tasks'] ?? 0),
            'overdue_tasks' => (int)($taskStats['overdue_tasks'] ?? 0),
            'leads' => $leadStats,
            'tasks' => $taskStats,
            'recent_logs' => $recentLogs
        ];
    }
}
