<?php
namespace App\Models;

use App\Config\Database;
use App\Helpers\Auth;
use App\Helpers\Logger;
use PDO;

class Task
{
    public static function all(array $filters = []): array
    {
        $db = Database::getConnection();

        $where = ["l.is_archived = 0"];
        $params = [];

        if (Auth::isEmployee()) {
            if (!empty($filters['lead_id'])) {
                $where[] = "t.lead_id = ? AND l.assigned_to = ?";
                $params[] = (int)$filters['lead_id'];
                $params[] = Auth::id();
            } else {
                // Admission Counselor strictly sees tasks/actions on their assigned leads
                $where[] = "l.assigned_to = ?";
                $params[] = Auth::id();
            }
        } elseif (!empty($filters['assigned_to'])) {
            $where[] = "l.assigned_to = ?";
            $params[] = (int)$filters['assigned_to'];
        }

        // Only show tasks where there is at least 1 activity change in lead data (or manual task created)
        $where[] = "(
            t.description NOT LIKE '%Excel Import lead follow-up%' 
            OR l.status != 'New'
            OR EXISTS (SELECT 1 FROM follow_up_logs f WHERE f.lead_id = l.id)
            OR EXISTS (SELECT 1 FROM audit_logs a WHERE a.entity_type = 'lead' AND a.entity_id = l.id AND a.action IN ('lead_status_update', 'lead_priority_update', 'lead_update'))
        )";

        if (!empty($filters['session_id'])) {
            $where[] = "t.session_id = ?";
            $params[] = (int)$filters['session_id'];
        }

        if (!empty($filters['lead_id'])) {
            $where[] = "t.lead_id = ?";
            $params[] = (int)$filters['lead_id'];
        }

        if (!empty($filters['status'])) {
            $where[] = "t.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['priority'])) {
            $where[] = "t.priority = ?";
            $params[] = $filters['priority'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(l.student_name LIKE ? OR l.parent_name LIKE ? OR l.mobile LIKE ? OR l.lead_ref LIKE ? OR t.title LIKE ?)";
            $term = '%' . trim($filters['search']) . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        if (!empty($filters['due_filter'])) {
            if ($filters['due_filter'] === 'today') {
                $where[] = "DATE(t.due_date) = CURDATE()";
            } elseif ($filters['due_filter'] === 'overdue') {
                $where[] = "t.due_date < NOW() AND t.status IN ('Pending', 'In Progress')";
            } elseif ($filters['due_filter'] === 'upcoming') {
                $where[] = "t.due_date > NOW()";
            }
        }

        $whereClause = implode(" AND ", $where);

        $sql = "SELECT t.*, l.lead_ref, l.student_name, l.parent_name, l.mobile,
                       u.name as assignee_name, creator.name as creator_name, completer.name as completer_name,
                       CASE WHEN t.due_date < NOW() AND t.status IN ('Pending', 'In Progress') THEN 1 ELSE 0 END as is_overdue
                FROM tasks t
                JOIN leads l ON t.lead_id = l.id
                JOIN users u ON t.assigned_to = u.id
                LEFT JOIN users creator ON t.created_by = creator.id
                LEFT JOIN users completer ON t.completed_by = completer.id
                WHERE {$whereClause}
                ORDER BY is_overdue DESC, t.due_date ASC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT t.*, l.lead_ref, l.student_name, l.parent_name, l.mobile,
                                     u.name as assignee_name, creator.name as creator_name, completer.name as completer_name,
                                     CASE WHEN t.due_date < NOW() AND t.status IN ('Pending', 'In Progress') THEN 1 ELSE 0 END as is_overdue
                              FROM tasks t
                              JOIN leads l ON t.lead_id = l.id
                              JOIN users u ON t.assigned_to = u.id
                              LEFT JOIN users creator ON t.created_by = creator.id
                              LEFT JOIN users completer ON t.completed_by = completer.id
                              WHERE t.id = ? AND l.is_archived = 0 LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(array $data): int
    {
        $db = Database::getConnection();

        $lead = Lead::find((int)$data['lead_id']);
        $sessionId = $lead['session_id'] ?? AcademicSession::getActive()['id'];

        $stmt = $db->prepare("INSERT INTO tasks (lead_id, session_id, title, task_type, description, assigned_to, due_date, priority, status, created_by)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['lead_id'],
            $sessionId,
            $data['title'],
            $data['task_type'] ?? 'Follow-up Call',
            $data['description'] ?? null,
            (int)$data['assigned_to'],
            $data['due_date'],
            $data['priority'] ?? 'Medium',
            $data['status'] ?? 'Pending',
            $data['created_by'] ?? Auth::id() ?? 1
        ]);

        $newId = (int)$db->lastInsertId();
        Logger::audit('task_create', 'task', $newId, null, ['title' => $data['title'], 'lead_id' => $data['lead_id']]);
        return $newId;
    }

    public static function update(int $id, array $data): bool
    {
        $db = Database::getConnection();
        $old = self::find($id);

        $stmt = $db->prepare("UPDATE tasks SET title = ?, task_type = ?, description = ?, assigned_to = ?, due_date = ?, priority = ?, status = ? WHERE id = ?");
        $res = $stmt->execute([
            $data['title'] ?? $old['title'],
            $data['task_type'] ?? $old['task_type'],
            $data['description'] ?? $old['description'],
            !empty($data['assigned_to']) ? (int)$data['assigned_to'] : $old['assigned_to'],
            $data['due_date'] ?? $old['due_date'],
            $data['priority'] ?? $old['priority'],
            $data['status'] ?? $old['status'],
            $id
        ]);

        if ($res) {
            Logger::audit('task_update', 'task', $id, $old, $data);
        }
        return $res;
    }

    public static function complete(int $id, string $outcomeRemarks, ?int $completedBy = null): bool
    {
        $db = Database::getConnection();
        $completedBy = $completedBy ?: Auth::id();
        $stmt = $db->prepare("UPDATE tasks SET status = 'Completed', completion_remarks = ?, completed_by = ?, completed_at = NOW() WHERE id = ?");
        $res = $stmt->execute([$outcomeRemarks, $completedBy, $id]);
        Logger::audit('task_complete', 'task', $id, null, ['outcome' => $outcomeRemarks, 'completed_by' => $completedBy]);
        return $res;
    }
}
