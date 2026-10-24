<?php
namespace App\Models;

use App\Config\Database;
use App\Helpers\Auth;
use App\Helpers\Logger;
use PDO;

class FollowupLog
{
    public static function all(array $filters = []): array
    {
        $db = Database::getConnection();

        $where = ["l.is_archived = 0"];
        $params = [];

        if (Auth::isEmployee()) {
            $where[] = "l.assigned_to = ?";
            $params[] = Auth::id();
        } elseif (!empty($filters['assigned_to'])) {
            $where[] = "l.assigned_to = ?";
            $params[] = (int)$filters['assigned_to'];
        }

        if (!empty($filters['session_id'])) {
            $where[] = "l.session_id = ?";
            $params[] = (int)$filters['session_id'];
        }

        if (!empty($filters['lead_id'])) {
            $where[] = "ful.lead_id = ?";
            $params[] = (int)$filters['lead_id'];
        }

        if (!empty($filters['outcome'])) {
            $where[] = "ful.outcome = ?";
            $params[] = $filters['outcome'];
        }

        if (!empty($filters['search'])) {
            $where[] = "(l.student_name LIKE ? OR l.parent_name LIKE ? OR l.mobile LIKE ? OR l.lead_ref LIKE ? OR ful.remarks LIKE ?)";
            $term = '%' . trim($filters['search']) . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $whereClause = implode(" AND ", $where);

        $sql = "SELECT ful.*, l.lead_ref, l.student_name, l.parent_name, l.mobile, l.status as lead_status,
                       u.name as author_name, assignee.name as assigned_to_name
                FROM follow_up_logs ful
                JOIN leads l ON ful.lead_id = l.id
                JOIN users u ON ful.created_by = u.id
                LEFT JOIN users assignee ON l.assigned_to = assignee.id
                WHERE {$whereClause}
                ORDER BY ful.created_at DESC";

        if (!empty($filters['limit'])) {
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function create(array $data): int
    {
        $db = Database::getConnection();

        $createdBy = $data['created_by'] ?? Auth::id() ?? 1;
        $stmt = $db->prepare("INSERT INTO follow_up_logs (lead_id, contact_method, outcome, remarks, next_followup_date, created_by)
                              VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            (int)$data['lead_id'],
            $data['contact_method'] ?? 'Phone',
            $data['outcome'],
            $data['remarks'],
            !empty($data['next_followup_date']) ? $data['next_followup_date'] : null,
            $createdBy
        ]);

        $newId = (int)$db->lastInsertId();

        // Update the lead's next follow-up date and status if requested
        $updateFields = [];
        $updateParams = [];

        if (!empty($data['next_followup_date'])) {
            $updateFields[] = "next_followup_date = ?";
            $updateParams[] = $data['next_followup_date'];
        }

        if (!empty($data['lead_status'])) {
            $lead = Lead::find((int)$data['lead_id']);
            if ($lead && $lead['status'] !== $data['lead_status']) {
                Lead::notifyStatusChange((int)$data['lead_id'], $lead, $data['lead_status']);
            }
            $updateFields[] = "status = ?";
            $updateParams[] = $data['lead_status'];
        }

        if (!empty($updateFields)) {
            $updateFields[] = "updated_by = ?";
            $updateParams[] = Auth::id();
            $updateParams[] = (int)$data['lead_id'];

            $updateSql = "UPDATE leads SET " . implode(", ", $updateFields) . " WHERE id = ?";
            $updateStmt = $db->prepare($updateSql);
            $updateStmt->execute($updateParams);
        }

        Logger::audit('followup_log', 'follow_up_log', $newId, null, [
            'lead_id' => $data['lead_id'],
            'outcome' => $data['outcome']
        ]);

        return $newId;
    }
}
