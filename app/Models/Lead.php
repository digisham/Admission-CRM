<?php
namespace App\Models;

use App\Config\Database;
use App\Helpers\Auth;
use App\Helpers\Logger;
use App\Helpers\Sanitizer;
use PDO;

class Lead
{
    public static function generateLeadRef(int $sessionId): string
    {
        $db = Database::getConnection();
        $session = AcademicSession::find($sessionId) ?: AcademicSession::getActive();
        $label = $session['session_label'] ?? '2027–28';
        // Clean label: e.g. 2027–28 -> 2728
        $cleanLabel = preg_replace('/[^\d]/', '', $label);
        if (strlen($cleanLabel) >= 4) {
            $prefix = 'GDG-' . substr($cleanLabel, -4);
        } else {
            $prefix = 'GDG-' . date('Y');
        }

        // Get max id or count for this prefix
        $stmt = $db->prepare("SELECT COUNT(*) FROM leads WHERE lead_ref LIKE ?");
        $stmt->execute([$prefix . '-%']);
        $count = (int)$stmt->fetchColumn() + 1;

        return sprintf('%s-%04d', $prefix, $count);
    }

    public static function find(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT l.*, s.session_label, u.name as assigned_name, u.email as assigned_email,
                                     creator.name as created_by_name
                              FROM leads l
                              JOIN academic_sessions s ON l.session_id = s.id
                              LEFT JOIN users u ON l.assigned_to = u.id
                              LEFT JOIN users creator ON l.created_by = creator.id
                              WHERE l.id = ? AND l.is_archived = 0 LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function findDuplicates(string $mobile, int $sessionId, ?int $excludeLeadId = null): array
    {
        $db = Database::getConnection();
        $cleanMobile = Sanitizer::cleanPhone($mobile);
        if (empty($cleanMobile)) {
            return [];
        }

        $sql = "SELECT l.id, l.lead_ref, l.student_name, l.parent_name, l.mobile, l.status, l.class_applied, s.session_label, u.name as assigned_name
                FROM leads l
                JOIN academic_sessions s ON l.session_id = s.id
                LEFT JOIN users u ON l.assigned_to = u.id
                WHERE l.mobile = ? AND l.session_id = ? AND l.is_archived = 0";
        $params = [$cleanMobile, $sessionId];

        if ($excludeLeadId) {
            $sql .= " AND l.id != ?";
            $params[] = $excludeLeadId;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public static function paginate(array $filters = [], int $page = 1, int $perPage = 15): array
    {
        $db = Database::getConnection();

        $where = ["l.is_archived = 0"];
        $params = [];

        // Role restriction: Admission Counselor only sees their assigned leads
        if (Auth::isEmployee()) {
            $where[] = "l.assigned_to = ?";
            $params[] = Auth::id();
        } elseif (!empty($filters['assigned_to'])) {
            $where[] = "l.assigned_to = ?";
            $params[] = (int)$filters['assigned_to'];
        }

        if (!empty($filters['unassigned_only'])) {
            $where[] = "l.assigned_to IS NULL";
        }

        if (!empty($filters['session_id'])) {
            $where[] = "l.session_id = ?";
            $params[] = (int)$filters['session_id'];
        }

        if (!empty($filters['status'])) {
            $where[] = "l.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['class_applied'])) {
            $where[] = "l.class_applied = ?";
            $params[] = $filters['class_applied'];
        }

        if (!empty($filters['enquiry_source'])) {
            $where[] = "l.enquiry_source = ?";
            $params[] = $filters['enquiry_source'];
        }

        if (!empty($filters['priority'])) {
            $where[] = "l.priority = ?";
            $params[] = $filters['priority'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = "l.enquiry_date >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = "l.enquiry_date <= ?";
            $params[] = $filters['date_to'];
        }

        if (!empty($filters['followup_due'])) {
            if ($filters['followup_due'] === 'today') {
                $where[] = "DATE(l.next_followup_date) = CURDATE()";
            } elseif ($filters['followup_due'] === 'overdue') {
                $where[] = "l.next_followup_date < NOW() AND l.status NOT IN ('Admission Confirmed', 'Lost / Unreachable')";
            } elseif ($filters['followup_due'] === 'upcoming') {
                $where[] = "l.next_followup_date > NOW()";
            }
        }

        if (!empty($filters['search'])) {
            $searchTerm = '%' . trim($filters['search']) . '%';
            $where[] = "(l.student_name LIKE ? OR l.parent_name LIKE ? OR l.mobile LIKE ? OR l.alternate_mobile LIKE ? OR l.email LIKE ? OR l.lead_ref LIKE ?)";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $whereClause = implode(" AND ", $where);

        // Count total
        $countStmt = $db->prepare("SELECT COUNT(*) FROM leads l WHERE {$whereClause}");
        $countStmt->execute($params);
        $totalRecords = (int)$countStmt->fetchColumn();

        $totalPages = $perPage > 0 ? (int)ceil($totalRecords / $perPage) : 1;
        $page = max(1, min($page, max(1, $totalPages)));
        $offset = ($page - 1) * $perPage;

        // Fetch records
        $sql = "SELECT l.*, s.session_label, u.name as assigned_name, u.email as assigned_email,
                       (SELECT COUNT(*) FROM follow_up_logs f WHERE f.lead_id = l.id) as followups_count,
                       (SELECT COUNT(*) FROM tasks t WHERE t.lead_id = l.id AND t.status IN ('Pending', 'In Progress')) as pending_tasks_count
                FROM leads l
                JOIN academic_sessions s ON l.session_id = s.id
                LEFT JOIN users u ON l.assigned_to = u.id
                WHERE {$whereClause}
                ORDER BY l.created_at DESC
                LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $records = $stmt->fetchAll();

        return [
            'data' => $records,
            'total' => $totalRecords,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => $totalPages,
        ];
    }

    public static function create(array $data): int
    {
        $db = Database::getConnection();

        $sessionId = !empty($data['session_id']) ? (int)$data['session_id'] : AcademicSession::getActive()['id'];
        $leadRef = self::generateLeadRef($sessionId);
        $cleanMobile = Sanitizer::cleanPhone($data['mobile']);
        $cleanAltMobile = Sanitizer::cleanPhone($data['alternate_mobile'] ?? null);

        $stmt = $db->prepare("INSERT INTO leads (lead_ref, session_id, student_name, parent_name, mobile, alternate_mobile, email, class_applied, enquiry_date, enquiry_source, assigned_to, status, priority, next_followup_date, address, city, remarks, created_by)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $leadRef,
            $sessionId,
            $data['student_name'],
            $data['parent_name'],
            $cleanMobile,
            $cleanAltMobile ?: null,
            !empty($data['email']) ? trim(strtolower($data['email'])) : null,
            $data['class_applied'],
            !empty($data['enquiry_date']) ? $data['enquiry_date'] : date('Y-m-d'),
            $data['enquiry_source'] ?? 'Direct Enquiry',
            !empty($data['assigned_to']) ? (int)$data['assigned_to'] : null,
            $data['status'] ?? 'New',
            $data['priority'] ?? 'Medium',
            !empty($data['next_followup_date']) ? $data['next_followup_date'] : null,
            $data['address'] ?? null,
            $data['city'] ?? 'Purnea',
            $data['remarks'] ?? null,
            $data['created_by'] ?? Auth::id() ?? 1
        ]);

        $newId = (int)$db->lastInsertId();

        // If assigned right away, record assignment history
        if (!empty($data['assigned_to'])) {
            $stmtHist = $db->prepare("INSERT INTO lead_assignment_history (lead_id, previous_user_id, new_user_id, assigned_by, reason) VALUES (?, NULL, ?, ?, ?)");
            $stmtHist->execute([$newId, (int)$data['assigned_to'], Auth::id(), 'Assigned upon lead creation']);
        }

        Logger::audit('lead_create', 'lead', $newId, null, ['ref' => $leadRef, 'student' => $data['student_name']]);
        return $newId;
    }

    public static function update(int $id, array $data): bool
    {
        $db = Database::getConnection();
        $old = self::find($id);
        if (!$old) {
            return false;
        }

        $cleanMobile = Sanitizer::cleanPhone($data['mobile'] ?? $old['mobile']);
        $cleanAltMobile = Sanitizer::cleanPhone($data['alternate_mobile'] ?? $old['alternate_mobile']);

        $stmt = $db->prepare("UPDATE leads SET 
                              student_name = ?, parent_name = ?, mobile = ?, alternate_mobile = ?, email = ?, 
                              class_applied = ?, enquiry_source = ?, status = ?, priority = ?, next_followup_date = ?, 
                              address = ?, city = ?, remarks = ?, updated_by = ?
                              WHERE id = ?");
        $res = $stmt->execute([
            $data['student_name'] ?? $old['student_name'],
            $data['parent_name'] ?? $old['parent_name'],
            $cleanMobile,
            $cleanAltMobile ?: null,
            !empty($data['email']) ? trim(strtolower($data['email'])) : null,
            $data['class_applied'] ?? $old['class_applied'],
            $data['enquiry_source'] ?? $old['enquiry_source'],
            $data['status'] ?? $old['status'],
            $data['priority'] ?? $old['priority'],
            !empty($data['next_followup_date']) ? $data['next_followup_date'] : null,
            $data['address'] ?? $old['address'],
            $data['city'] ?? $old['city'],
            $data['remarks'] ?? $old['remarks'],
            Auth::id(),
            $id
        ]);

        if ($res) {
            Logger::audit('lead_update', 'lead', $id, $old, $data);
        }
        return $res;
    }

    public static function assign(int $leadId, int $newUserId, ?string $reason = null, ?int $assignedBy = null): bool
    {
        $db = Database::getConnection();
        $lead = self::find($leadId);
        if (!$lead) {
            return false;
        }

        $prevUserId = $lead['assigned_to'];
        if ((int)$prevUserId === $newUserId) {
            return true; // Already assigned to this user
        }

        $db->beginTransaction();
        try {
            $stmt = $db->prepare("UPDATE leads SET assigned_to = ?, updated_by = ? WHERE id = ?");
            $stmt->execute([$newUserId, $assignedBy ?: Auth::id(), $leadId]);

            $histStmt = $db->prepare("INSERT INTO lead_assignment_history (lead_id, previous_user_id, new_user_id, assigned_by, reason) VALUES (?, ?, ?, ?, ?)");
            $histStmt->execute([$leadId, $prevUserId, $newUserId, $assignedBy ?: Auth::id(), $reason ?: 'Reassigned by administration']);

            $db->commit();
            Logger::audit('lead_assign', 'lead', $leadId, ['from' => $prevUserId], ['to' => $newUserId, 'reason' => $reason]);
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            return false;
        }
    }

    public static function bulkAssign(array $leadIds, int $newUserId, ?string $reason = null, ?int $assignedBy = null, ?array $initialTask = null): int
    {
        $count = 0;
        foreach ($leadIds as $id) {
            $id = (int)$id;
            if ($id <= 0) continue;
            if (self::assign($id, $newUserId, $reason, $assignedBy)) {
                $count++;
                if (!empty($initialTask) && !empty($initialTask['title'])) {
                    $lead = self::find($id);
                    Task::create([
                        'lead_id' => $id,
                        'session_id' => $lead['session_id'],
                        'title' => $initialTask['title'],
                        'task_type' => $initialTask['task_type'] ?? 'Follow-up Call',
                        'description' => $initialTask['description'] ?? 'Initial contact task from bulk assignment',
                        'assigned_to' => $newUserId,
                        'due_date' => $initialTask['due_date'] ?? date('Y-m-d H:i:s', strtotime('+1 day')),
                        'priority' => $initialTask['priority'] ?? 'Medium',
                    ]);
                }
            }
        }
        return $count;
    }

    public static function getTimeline(int $leadId): array
    {
        $db = Database::getConnection();
        $timeline = [];

        // 1. Assignment logs
        $stmt = $db->prepare("SELECT lah.*, u_prev.name as prev_user_name, u_new.name as new_user_name, u_by.name as assigned_by_name
                              FROM lead_assignment_history lah
                              LEFT JOIN users u_prev ON lah.previous_user_id = u_prev.id
                              JOIN users u_new ON lah.new_user_id = u_new.id
                              LEFT JOIN users u_by ON lah.assigned_by = u_by.id
                              WHERE lah.lead_id = ? ORDER BY lah.created_at ASC");
        $stmt->execute([$leadId]);
        foreach ($stmt->fetchAll() as $row) {
            $timeline[] = [
                'type' => 'assignment',
                'title' => 'Assigned to ' . $row['new_user_name'],
                'description' => $row['prev_user_name'] ? "Reassigned from {$row['prev_user_name']}. Reason: " . ($row['reason'] ?: 'None') : "Assigned. Reason: " . ($row['reason'] ?: 'Initial assignment'),
                'author' => $row['assigned_by_name'] ?: 'System',
                'created_at' => $row['created_at'],
                'badge' => 'primary',
                'icon' => 'user-check'
            ];
        }

        // 2. Follow-ups / Call logs
        $stmt = $db->prepare("SELECT ful.*, u.name as author_name 
                              FROM follow_up_logs ful
                              JOIN users u ON ful.created_by = u.id
                              WHERE ful.lead_id = ? ORDER BY ful.created_at ASC");
        $stmt->execute([$leadId]);
        foreach ($stmt->fetchAll() as $row) {
            $timeline[] = [
                'type' => 'call_log',
                'title' => "Call Log ({$row['contact_method']}) - Outcome: {$row['outcome']}",
                'description' => $row['remarks'] . ($row['next_followup_date'] ? " | Next Follow-up: " . date('d M Y, h:i A', strtotime($row['next_followup_date'])) : ""),
                'author' => $row['author_name'],
                'created_at' => $row['created_at'],
                'badge' => 'info',
                'icon' => 'phone-call'
            ];
        }

        // 3. Tasks
        $stmt = $db->prepare("SELECT t.*, u.name as assignee_name, creator.name as creator_name
                              FROM tasks t
                              JOIN users u ON t.assigned_to = u.id
                              LEFT JOIN users creator ON t.created_by = creator.id
                              WHERE t.lead_id = ? ORDER BY t.created_at ASC");
        $stmt->execute([$leadId]);
        foreach ($stmt->fetchAll() as $row) {
            $timeline[] = [
                'type' => 'task',
                'title' => "Task: {$row['title']} [{$row['status']}]",
                'description' => ($row['description'] ?: 'No details') . ($row['completion_remarks'] ? " | Outcome: {$row['completion_remarks']}" : ""),
                'author' => $row['creator_name'] ?: 'Admin',
                'created_at' => $row['completed_at'] ?: $row['created_at'],
                'badge' => $row['status'] === 'Completed' ? 'success' : ($row['status'] === 'Cancelled' ? 'secondary' : 'warning'),
                'icon' => 'check-square'
            ];
        }

        // Sort chronological descending (latest first)
        usort($timeline, function ($a, $b) {
            return strtotime($b['created_at']) <=> strtotime($a['created_at']);
        });

        return $timeline;
    }

    public static function getDashboardMetrics(?int $sessionId = null, ?int $assignedTo = null): array
    {
        $db = Database::getConnection();

        $sessionCondition = $sessionId ? "AND l.session_id = " . (int)$sessionId : "";
        $userCondition = $assignedTo ? "AND l.assigned_to = " . (int)$assignedTo : "";

        // Total leads
        $totalStmt = $db->query("SELECT COUNT(*) FROM leads l WHERE l.is_archived = 0 {$sessionCondition} {$userCondition}");
        $totalLeads = (int)$totalStmt->fetchColumn();

        // New leads
        $newStmt = $db->query("SELECT COUNT(*) FROM leads l WHERE l.is_archived = 0 AND l.status = 'New' {$sessionCondition} {$userCondition}");
        $newLeads = (int)$newStmt->fetchColumn();

        // Unassigned leads
        $unassignedStmt = $db->query("SELECT COUNT(*) FROM leads l WHERE l.is_archived = 0 AND l.assigned_to IS NULL {$sessionCondition}");
        $unassignedLeads = (int)$unassignedStmt->fetchColumn();

        // Confirmed admissions
        $confirmedStmt = $db->query("SELECT COUNT(*) FROM leads l WHERE l.is_archived = 0 AND l.status = 'Admission Confirmed' {$sessionCondition} {$userCondition}");
        $confirmedAdmissions = (int)$confirmedStmt->fetchColumn();

        // Follow-ups due today
        $dueTodayStmt = $db->query("SELECT COUNT(*) FROM leads l WHERE l.is_archived = 0 AND DATE(l.next_followup_date) = CURDATE() {$sessionCondition} {$userCondition}");
        $followupsDueToday = (int)$dueTodayStmt->fetchColumn();

        // Overdue follow-ups
        $overdueStmt = $db->query("SELECT COUNT(*) FROM leads l WHERE l.is_archived = 0 AND l.next_followup_date < NOW() AND l.status NOT IN ('Admission Confirmed', 'Lost / Unreachable') {$sessionCondition} {$userCondition}");
        $overdueFollowups = (int)$overdueStmt->fetchColumn();

        // Upcoming school visits
        $visitsStmt = $db->query("SELECT COUNT(*) FROM leads l WHERE l.is_archived = 0 AND l.status = 'School Visit Scheduled' {$sessionCondition} {$userCondition}");
        $schoolVisits = (int)$visitsStmt->fetchColumn();

        // Overdue tasks
        $taskUserCond = $assignedTo ? "AND t.assigned_to = " . (int)$assignedTo : "";
        $taskSessionCond = $sessionId ? "AND t.session_id = " . (int)$sessionId : "";
        $overdueTasksStmt = $db->query("SELECT COUNT(*) FROM tasks t WHERE t.due_date < NOW() AND t.status IN ('Pending', 'In Progress') {$taskSessionCond} {$taskUserCond}");
        $overdueTasks = (int)$overdueTasksStmt->fetchColumn();

        // Conversion Rate: Confirmed admissions / Total active leads * 100%
        $eligibleDenominator = $totalLeads;
        $conversionRate = $eligibleDenominator > 0 ? round(($confirmedAdmissions / $eligibleDenominator) * 100, 1) : 0.0;

        // Leads by status
        $statusStmt = $db->query("SELECT l.status, COUNT(*) as count FROM leads l WHERE l.is_archived = 0 {$sessionCondition} {$userCondition} GROUP BY l.status ORDER BY count DESC");
        $leadsByStatus = $statusStmt->fetchAll();

        // Leads by class
        $classStmt = $db->query("SELECT l.class_applied, COUNT(*) as count FROM leads l WHERE l.is_archived = 0 {$sessionCondition} {$userCondition} GROUP BY l.class_applied ORDER BY count DESC LIMIT 10");
        $leadsByClass = $classStmt->fetchAll();

        // Leads by source
        $sourceStmt = $db->query("SELECT l.enquiry_source, COUNT(*) as count FROM leads l WHERE l.is_archived = 0 {$sessionCondition} {$userCondition} GROUP BY l.enquiry_source ORDER BY count DESC");
        $leadsBySource = $sourceStmt->fetchAll();

        return [
            'total_leads' => $totalLeads,
            'new_leads' => $newLeads,
            'unassigned_leads' => $unassignedLeads,
            'confirmed_admissions' => $confirmedAdmissions,
            'followups_due_today' => $followupsDueToday,
            'overdue_followups' => $overdueFollowups,
            'overdue_tasks' => $overdueTasks,
            'school_visits' => $schoolVisits,
            'conversion_rate' => $conversionRate,
            'leads_by_status' => $leadsByStatus,
            'leads_by_class' => $leadsByClass,
            'leads_by_source' => $leadsBySource,
        ];
    }
}
