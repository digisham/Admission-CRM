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

        // Get max existing sequence number for this prefix
        $stmt = $db->prepare("SELECT lead_ref FROM leads WHERE lead_ref LIKE ?");
        $stmt->execute([$prefix . '-%']);
        $refs = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $maxNum = 0;
        foreach ($refs as $ref) {
            $parts = explode('-', $ref);
            $num = (int)end($parts);
            if ($num > $maxNum) {
                $maxNum = $num;
            }
        }
        $nextNum = $maxNum + 1;

        // Guarantee collision-free reference
        $checkStmt = $db->prepare("SELECT id FROM leads WHERE lead_ref = ? LIMIT 1");
        do {
            $candidate = sprintf('%s-%04d', $prefix, $nextNum);
            $checkStmt->execute([$candidate]);
            $exists = $checkStmt->fetchColumn();
            if ($exists) {
                $nextNum++;
            }
        } while ($exists);

        return $candidate;
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

        // When filtering specifically for unassigned queue, allow viewing leads with assigned_to IS NULL or 0 (Super Admin / Admission Manager only)
        if (!empty($filters['unassigned_only']) && Auth::isAdmin()) {
            $where[] = "(l.assigned_to IS NULL OR l.assigned_to = 0)";
        } else {
            // Role restriction: Admission Counselor only sees their assigned leads in general views
            if (Auth::isEmployee()) {
                $where[] = "l.assigned_to = ?";
                $params[] = Auth::id();
            } elseif (!empty($filters['assigned_to'])) {
                $where[] = "l.assigned_to = ?";
                $params[] = (int)$filters['assigned_to'];
            }
        }

        if (!empty($filters['session_id'])) {
            $where[] = "l.session_id = ?";
            $params[] = (int)$filters['session_id'];
        }

        if (!empty($filters['status'])) {
            if (in_array($filters['status'], ['public_enquiry', 'Public Enquiry', 'Public Enquiries', 'Website / Online Enquiry'])) {
                $where[] = "(l.enquiry_source = 'Website / Online Enquiry' OR l.enquiry_source LIKE '%Online Enquiry%' OR l.enquiry_source LIKE '%Public%')";
            } else {
                $where[] = "l.status = ?";
                $params[] = $filters['status'];
            }
        }

        if (!empty($filters['class_applied'])) {
            $classSynonyms = self::getClassSynonyms($filters['class_applied']);
            if (count($classSynonyms) === 1) {
                $where[] = "l.class_applied = ?";
                $params[] = $classSynonyms[0];
            } else {
                $ph = implode(',', array_fill(0, count($classSynonyms), '?'));
                $where[] = "l.class_applied IN ($ph)";
                foreach ($classSynonyms as $cs) {
                    $params[] = $cs;
                }
            }
        }

        if (!empty($filters['enquiry_source'])) {
            if ($filters['enquiry_source'] === 'Admission Manager') {
                $where[] = "(l.enquiry_source = ? OR l.enquiry_source LIKE 'Admission Manager%')";
                $params[] = $filters['enquiry_source'];
            } else {
                $where[] = "l.enquiry_source = ?";
                $params[] = $filters['enquiry_source'];
            }
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

        if ($perPage <= 0 || $perPage === -1) {
            $totalPages = 1;
            $page = 1;
            $offset = 0;
            $limitClause = "LIMIT 10000"; // Safety limit to protect server memory while showing all leads
        } else {
            $totalPages = (int)ceil($totalRecords / $perPage);
            $page = max(1, min($page, max(1, $totalPages)));
            $offset = ($page - 1) * $perPage;
            $limitClause = "LIMIT {$perPage} OFFSET {$offset}";
        }

        // Fetch records
        $sql = "SELECT l.*, s.session_label, u.name as assigned_name, u.email as assigned_email,
                       (SELECT COUNT(*) FROM follow_up_logs f WHERE f.lead_id = l.id) as followups_count,
                       (SELECT COUNT(*) FROM tasks t WHERE t.lead_id = l.id AND t.status IN ('Pending', 'In Progress')) as pending_tasks_count
                FROM leads l
                JOIN academic_sessions s ON l.session_id = s.id
                LEFT JOIN users u ON l.assigned_to = u.id
                WHERE {$whereClause}
                ORDER BY l.created_at DESC
                {$limitClause}";

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

        // If assigned right away, record assignment history & send notification
        if (!empty($data['assigned_to'])) {
            $stmtHist = $db->prepare("INSERT INTO lead_assignment_history (lead_id, previous_user_id, new_user_id, assigned_by, reason) VALUES (?, NULL, ?, ?, ?)");
            $stmtHist->execute([$newId, (int)$data['assigned_to'], Auth::id(), 'Assigned upon lead creation']);

            // Notify assigned employee if not self-assigned
            if ((int)$data['assigned_to'] !== Auth::id()) {
                $assignerName = Auth::user()['name'] ?? 'Admin';
                Notification::create([
                    'user_id' => (int)$data['assigned_to'],
                    'sender_id' => Auth::id(),
                    'lead_id' => $newId,
                    'type' => 'lead_assigned',
                    'title' => 'New Lead Assigned',
                    'message' => "{$assignerName} assigned new lead {$data['student_name']} ({$leadRef}) to you.",
                    'link' => 'index.php?route=leads/show&id=' . $newId
                ]);
            }
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
        $newStatus = $data['status'] ?? $old['status'];
        $statusChanged = ($newStatus !== $old['status']);

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
            $newStatus,
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

            if ($statusChanged) {
                self::notifyStatusChange($id, $old, $newStatus);
            }
        }
        return $res;
    }

    public static function notifyStatusChange(int $leadId, array $lead, string $newStatus): void
    {
        $changer = Auth::user();
        $changerName = $changer['name'] ?? 'Staff Member';
        $changerId = Auth::id() ?: 1;
        $studentName = $lead['student_name'] ?? 'Student';
        $oldStatus = $lead['status'] ?? 'Unknown';

        // Notify all staff (excluding the user who made the change)
        Notification::notifyAllStaff([
            'sender_id' => $changerId,
            'lead_id' => $leadId,
            'type' => 'status_changed',
            'title' => "Status Updated: {$studentName}",
            'message' => "{$changerName} changed {$studentName}'s status to \"{$newStatus}\" (was \"{$oldStatus}\").",
            'link' => 'index.php?route=leads/show&id=' . $leadId
        ], $changerId);
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
            $assignerId = $assignedBy ?: Auth::id();
            $stmt = $db->prepare("UPDATE leads SET assigned_to = ?, updated_by = ? WHERE id = ?");
            $stmt->execute([$newUserId, $assignerId, $leadId]);

            $histStmt = $db->prepare("INSERT INTO lead_assignment_history (lead_id, previous_user_id, new_user_id, assigned_by, reason) VALUES (?, ?, ?, ?, ?)");
            $histStmt->execute([$leadId, $prevUserId, $newUserId, $assignerId, $reason ?: 'Reassigned by administration']);

            $db->commit();
            Logger::audit('lead_assign', 'lead', $leadId, ['from' => $prevUserId], ['to' => $newUserId, 'reason' => $reason]);

            // Notify assigned employee if not assigning to self
            if ($newUserId !== Auth::id()) {
                $assignerName = Auth::user()['name'] ?? 'Admin';
                Notification::create([
                    'user_id' => $newUserId,
                    'sender_id' => $assignerId,
                    'lead_id' => $leadId,
                    'type' => 'lead_assigned',
                    'title' => 'Lead Assigned: ' . $lead['student_name'],
                    'message' => "{$assignerName} assigned lead {$lead['student_name']} ({$lead['lead_ref']}) to you.",
                    'link' => 'index.php?route=leads/show&id=' . $leadId
                ]);
            }

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
                $lead = self::find($id);
                if ($lead) {
                    $taskTitle = !empty($initialTask['title']) ? $initialTask['title'] : ("Follow-up Call: " . $lead['student_name']);
                    Task::create([
                        'lead_id' => $id,
                        'session_id' => $lead['session_id'],
                        'title' => $taskTitle,
                        'task_type' => $initialTask['task_type'] ?? 'Follow-up Call',
                        'description' => $initialTask['description'] ?? ("Initial follow-up call with parent of " . $lead['student_name'] . " (" . $lead['lead_ref'] . ") - " . ($reason ?: 'Assigned lead')),
                        'assigned_to' => $newUserId,
                        'due_date' => !empty($initialTask['due_date']) ? $initialTask['due_date'] : date('Y-m-d 11:00:00', strtotime('+1 day')),
                        'priority' => $initialTask['priority'] ?? 'Medium',
                    ]);
                }
            }
        }
        return $count;
    }

    /**
     * Get next counselor for automated equal lead distribution (Least-loaded & Round Robin)
     */
    public static function getNextCounselorForAssignment(?int $sessionId = null): ?int
    {
        $counselors = User::getAdmissionCounselors();
        if (empty($counselors)) {
            return null;
        }

        if (count($counselors) === 1) {
            return (int)$counselors[0]['id'];
        }

        $counselorIds = array_column($counselors, 'id');
        $placeholders = implode(',', array_fill(0, count($counselorIds), '?'));

        $db = Database::getConnection();
        // Count active assigned leads per counselor in this session
        $sql = "SELECT u.id, COUNT(l.id) as lead_count, COALESCE(MAX(l.created_at), '1970-01-01') as last_assigned
                FROM users u
                LEFT JOIN leads l ON l.assigned_to = u.id AND l.is_archived = 0 " . ($sessionId ? "AND l.session_id = ?" : "") . "
                WHERE u.id IN ({$placeholders})
                GROUP BY u.id
                ORDER BY lead_count ASC, last_assigned ASC, u.id ASC
                LIMIT 1";

        $params = $sessionId ? array_merge([$sessionId], $counselorIds) : $counselorIds;
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $best = $stmt->fetch();

        return !empty($best['id']) ? (int)$best['id'] : (int)$counselors[0]['id'];
    }

    /**
     * Bulk divide an array of leads equally among all active admission counselors
     */
    public static function bulkEqualDistribute(array $leadIds, ?string $reason = null, ?int $assignedBy = null): array
    {
        $counselors = User::getAdmissionCounselors();
        if (empty($counselors) || empty($leadIds)) {
            return ['total' => 0, 'counselors' => []];
        }

        $counselorCount = count($counselors);
        $distribution = [];
        foreach ($counselors as $c) {
            $distribution[$c['id']] = 0;
        }

        $cIndex = 0;
        $totalAssigned = 0;

        foreach ($leadIds as $id) {
            $id = (int)$id;
            if ($id <= 0) continue;

            $counselor = $counselors[$cIndex % $counselorCount];
            $counselorId = (int)$counselor['id'];

            if (self::assign($id, $counselorId, $reason ?: 'Automated equal distribution', $assignedBy)) {
                $distribution[$counselorId]++;
                $totalAssigned++;
                $cIndex++;

                $lead = self::find($id);
                if ($lead) {
                    Task::create([
                        'lead_id' => $id,
                        'session_id' => $lead['session_id'],
                        'title' => "Initial Call: " . $lead['student_name'],
                        'task_type' => 'Follow-up Call',
                        'description' => "Automated distribution enquiry call with parent of {$lead['student_name']} ({$lead['lead_ref']})",
                        'assigned_to' => $counselorId,
                        'due_date' => date('Y-m-d 11:00:00', strtotime('+1 day')),
                        'priority' => 'Medium',
                    ]);
                }
            }
        }

        return [
            'total' => $totalAssigned,
            'counselors' => $distribution
        ];
    }

    public static function delete(int $id, ?int $deletedBy = null): bool
    {
        $db = Database::getConnection();
        $lead = self::find($id);
        if (!$lead) {
            return false;
        }

        $userId = $deletedBy ?: (Auth::id() ?? 1);
        $stmt = $db->prepare("UPDATE leads SET is_archived = 1, updated_by = ? WHERE id = ?");
        $res = $stmt->execute([$userId, $id]);
        if ($res) {
            $db->prepare("DELETE FROM tasks WHERE lead_id = ?")->execute([$id]);
            Logger::audit('lead_delete', 'lead', $id, ['student_name' => $lead['student_name'], 'lead_ref' => $lead['lead_ref'], 'deleted_by' => $userId], null);
        }
        return $res;
    }

    public static function bulkDelete(array $ids, ?int $deletedBy = null): int
    {
        if (empty($ids)) {
            return 0;
        }

        $ids = array_map('intval', array_unique($ids));
        $ids = array_filter($ids, fn($id) => $id > 0);
        if (empty($ids)) {
            return 0;
        }

        $db = Database::getConnection();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        try {
            $userId = $deletedBy ?: (Auth::id() ?? 1);
            $stmt = $db->prepare("UPDATE leads SET is_archived = 1, updated_by = ? WHERE id IN ({$placeholders})");
            $params = array_merge([$userId], array_values($ids));
            $stmt->execute($params);
            $count = $stmt->rowCount();

            if ($count > 0) {
                $taskStmt = $db->prepare("DELETE FROM tasks WHERE lead_id IN ({$placeholders})");
                $taskStmt->execute(array_values($ids));

                Logger::audit('lead_bulk_delete', 'lead', null, [
                    'count' => $count,
                    'deleted_by' => $userId,
                    'sample_ids' => array_slice($ids, 0, 50)
                ], null);
            }

            return $count;
        } catch (\Throwable $e) {
            Logger::error("Failed to bulk delete leads: " . $e->getMessage());
            return 0;
        }
    }

    public static function getTimeline(int $leadId): array
    {
        $db = Database::getConnection();
        $timeline = [];

        // 1. Lead Registration / Creation Event
        $leadStmt = $db->prepare("SELECT l.*, u.name as creator_name 
                                  FROM leads l 
                                  LEFT JOIN users u ON l.created_by = u.id 
                                  WHERE l.id = ? LIMIT 1");
        $leadStmt->execute([$leadId]);
        $leadInfo = $leadStmt->fetch();
        if ($leadInfo) {
            $timeline[] = [
                'type' => 'lead_created',
                'title' => 'Admission Enquiry Registered',
                'description' => "Enquiry for {$leadInfo['student_name']} (Class: " . ($leadInfo['class_applied'] ?: 'N/A') . ") registered via " . ($leadInfo['enquiry_source'] ?: 'Direct Enquiry') . ".",
                'author' => $leadInfo['creator_name'] ?: 'System / Admin',
                'student_name' => $leadInfo['student_name'],
                'class_applied' => $leadInfo['class_applied'],
                'enquiry_source' => $leadInfo['enquiry_source'],
                'created_at' => $leadInfo['created_at'],
                'badge' => 'primary',
                'icon' => 'user-plus'
            ];
        }

        // 2. Assignment & Reassignment logs
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
                'prev_user_name' => $row['prev_user_name'] ?: 'Unassigned',
                'new_user_name' => $row['new_user_name'],
                'reason' => $row['reason'],
                'created_at' => $row['created_at'],
                'badge' => 'primary',
                'icon' => 'user-check'
            ];
        }

        // 3. Follow-ups / Call logs & Status/Priority Activities
        $stmt = $db->prepare("SELECT ful.*, u.name as author_name 
                              FROM follow_up_logs ful
                              JOIN users u ON ful.created_by = u.id
                              WHERE ful.lead_id = ? ORDER BY ful.created_at ASC");
        $stmt->execute([$leadId]);
        foreach ($stmt->fetchAll() as $row) {
            $isStatusChange = ($row['contact_method'] === 'Status Change' || $row['outcome'] === 'Status Update' || strpos($row['remarks'] ?? '', 'Status changed') === 0 || strpos($row['remarks'] ?? '', 'Status set') === 0);
            $isPriorityChange = ($row['contact_method'] === 'Priority Change' || $row['outcome'] === 'Priority Update' || strpos($row['remarks'] ?? '', 'Priority changed') === 0 || strpos($row['remarks'] ?? '', 'Priority set') === 0);

            if ($isStatusChange) {
                $timeline[] = [
                    'type' => 'status_change',
                    'title' => 'Status Changed by ' . $row['author_name'],
                    'description' => $row['remarks'],
                    'author' => $row['author_name'],
                    'contact_method' => 'Status Change',
                    'outcome' => 'Status Update',
                    'remarks' => $row['remarks'],
                    'created_at' => $row['created_at'],
                    'badge' => 'purple',
                    'icon' => 'tag'
                ];
            } elseif ($isPriorityChange) {
                $timeline[] = [
                    'type' => 'priority_change',
                    'title' => 'Priority Changed by ' . $row['author_name'],
                    'description' => $row['remarks'],
                    'author' => $row['author_name'],
                    'contact_method' => 'Priority Change',
                    'outcome' => 'Priority Update',
                    'remarks' => $row['remarks'],
                    'created_at' => $row['created_at'],
                    'badge' => 'warning',
                    'icon' => 'flame'
                ];
            } else {
                $timeline[] = [
                    'type' => 'call_log',
                    'title' => "Follow-up Call ({$row['contact_method']}) - Outcome: {$row['outcome']}",
                    'description' => $row['remarks'] . ($row['next_followup_date'] ? " | Next Follow-up: " . date('d M Y, h:i A', strtotime($row['next_followup_date'])) : ""),
                    'author' => $row['author_name'],
                    'contact_method' => $row['contact_method'] ?? 'Phone',
                    'outcome' => $row['outcome'] ?? 'Connected',
                    'remarks' => $row['remarks'],
                    'next_followup_date' => $row['next_followup_date'],
                    'created_at' => $row['created_at'],
                    'badge' => 'info',
                    'icon' => ($row['contact_method'] === 'WhatsApp' ? 'message-square' : ($row['contact_method'] === 'In-Person' ? 'users' : 'phone-call'))
                ];
            }
        }

        // 4. Tasks (both Task Created and Task Completed by admission counselor)
        $stmt = $db->prepare("SELECT t.*, u.name as assignee_name, creator.name as creator_name, completer.name as completer_name
                              FROM tasks t
                              JOIN users u ON t.assigned_to = u.id
                              LEFT JOIN users creator ON t.created_by = creator.id
                              LEFT JOIN users completer ON t.completed_by = completer.id
                              WHERE t.lead_id = ? 
                                AND t.description NOT LIKE '%Excel Import lead follow-up%' 
                              ORDER BY t.created_at ASC");
        $stmt->execute([$leadId]);
        foreach ($stmt->fetchAll() as $row) {
            // Task Created Event
            $timeline[] = [
                'type' => 'task_created',
                'title' => "Task Created: {$row['title']}",
                'task_id' => $row['id'],
                'task_title' => $row['title'],
                'task_type' => $row['task_type'] ?? 'Task',
                'task_status' => $row['status'],
                'priority' => $row['priority'],
                'due_date' => $row['due_date'],
                'assignee_name' => $row['assignee_name'],
                'author' => $row['creator_name'] ?: 'Staff',
                'description' => ($row['description'] ?: "Task assigned to {$row['assignee_name']}") . (!empty($row['due_date']) ? " | Due: " . date('d M Y, h:i A', strtotime($row['due_date'])) : ""),
                'created_at' => $row['created_at'],
                'badge' => 'primary',
                'icon' => 'calendar-plus'
            ];

            // Task Completed Event (if task is completed)
            if ($row['status'] === 'Completed') {
                $completer = $row['completer_name'] ?: ($row['assignee_name'] ?: 'Admission Counselor');
                $completedTime = $row['completed_at'] ?: $row['updated_at'] ?: $row['created_at'];
                $timeline[] = [
                    'type' => 'task_completed',
                    'title' => "Task Completed: {$row['title']}",
                    'task_id' => $row['id'],
                    'task_title' => $row['title'],
                    'task_type' => $row['task_type'] ?? 'Task',
                    'task_status' => 'Completed',
                    'priority' => $row['priority'],
                    'assignee_name' => $row['assignee_name'],
                    'author' => $completer,
                    'completion_remarks' => $row['completion_remarks'] ?: 'Task completed successfully',
                    'description' => $row['completion_remarks'] ?: "Task completed by {$completer}",
                    'created_at' => $completedTime,
                    'badge' => 'success',
                    'icon' => 'check-circle-2'
                ];
            } elseif ($row['status'] === 'Cancelled') {
                $timeline[] = [
                    'type' => 'task_cancelled',
                    'title' => "Task Cancelled: {$row['title']}",
                    'task_id' => $row['id'],
                    'task_title' => $row['title'],
                    'task_type' => $row['task_type'] ?? 'Task',
                    'task_status' => 'Cancelled',
                    'priority' => $row['priority'],
                    'assignee_name' => $row['assignee_name'],
                    'author' => $row['creator_name'] ?: 'Staff',
                    'description' => $row['completion_remarks'] ?: 'Task was cancelled',
                    'created_at' => $row['updated_at'] ?: $row['created_at'],
                    'badge' => 'secondary',
                    'icon' => 'x-circle'
                ];
            }
        }

        // 5. Document Uploads
        $stmt = $db->prepare("SELECT ld.*, dt.name as doc_type_name, u.name as uploader_name
                              FROM lead_documents ld
                              LEFT JOIN document_types dt ON ld.document_type_id = dt.id
                              LEFT JOIN users u ON ld.uploaded_by = u.id
                              WHERE ld.lead_id = ?
                              ORDER BY ld.uploaded_at ASC");
        $stmt->execute([$leadId]);
        foreach ($stmt->fetchAll() as $row) {
            $docLabel = $row['doc_type_name'] ?: ($row['file_name'] ?: 'Document');
            $timeline[] = [
                'type' => 'document',
                'title' => 'Document Uploaded: ' . $docLabel,
                'description' => "File {$row['file_name']} uploaded" . ($row['verification_status'] ? " (Verification: {$row['verification_status']})" : ""),
                'author' => $row['uploader_name'] ?: 'Staff',
                'file_name' => $row['file_name'],
                'verification_status' => $row['verification_status'] ?? 'Pending',
                'created_at' => $row['uploaded_at'] ?: $row['updated_at'] ?: date('Y-m-d H:i:s'),
                'badge' => 'secondary',
                'icon' => 'file-text'
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

        // Combined single-pass aggregation query for primary lead metrics
        $metricsStmt = $db->query("
            SELECT 
                COUNT(*) as total_leads,
                COALESCE(SUM(CASE WHEN l.status = 'New' THEN 1 ELSE 0 END), 0) as new_leads,
                COALESCE(SUM(CASE WHEN l.assigned_to IS NULL THEN 1 ELSE 0 END), 0) as unassigned_leads,
                COALESCE(SUM(CASE WHEN l.status = 'Admission Confirmed' THEN 1 ELSE 0 END), 0) as confirmed_admissions,
                COALESCE(SUM(CASE WHEN DATE(l.next_followup_date) = CURDATE() THEN 1 ELSE 0 END), 0) as followups_due_today,
                COALESCE(SUM(CASE WHEN l.next_followup_date < NOW() AND l.status NOT IN ('Admission Confirmed', 'Lost / Unreachable') THEN 1 ELSE 0 END), 0) as overdue_followups,
                COALESCE(SUM(CASE WHEN l.status = 'School Visit Scheduled' THEN 1 ELSE 0 END), 0) as school_visits
            FROM leads l 
            WHERE l.is_archived = 0 {$sessionCondition} {$userCondition}
        ");
        $m = $metricsStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $totalLeads = (int)($m['total_leads'] ?? 0);
        $newLeads = (int)($m['new_leads'] ?? 0);
        $unassignedLeads = (int)($m['unassigned_leads'] ?? 0);
        $confirmedAdmissions = (int)($m['confirmed_admissions'] ?? 0);
        $followupsDueToday = (int)($m['followups_due_today'] ?? 0);
        $overdueFollowups = (int)($m['overdue_followups'] ?? 0);
        $schoolVisits = (int)($m['school_visits'] ?? 0);

        // Overdue tasks (strictly for assigned leads with at least 1 activity change)
        $taskUserCond = $assignedTo ? "AND l.assigned_to = " . (int)$assignedTo : "";
        $taskSessionCond = $sessionId ? "AND t.session_id = " . (int)$sessionId : "";
        $overdueTasksStmt = $db->query("SELECT COUNT(*) FROM tasks t 
            JOIN leads l ON t.lead_id = l.id 
            WHERE l.is_archived = 0 
              AND t.due_date < NOW() 
              AND t.status IN ('Pending', 'In Progress') 
              AND (
                  t.description NOT LIKE '%Excel Import lead follow-up%' 
                  OR l.status != 'New'
                  OR EXISTS (SELECT 1 FROM follow_up_logs f WHERE f.lead_id = l.id)
                  OR EXISTS (SELECT 1 FROM audit_logs a WHERE a.entity_type = 'lead' AND a.entity_id = l.id AND a.action IN ('lead_status_update', 'lead_priority_update', 'lead_update'))
              )
              {$taskSessionCond} {$taskUserCond}");
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

    public static function getFilteredIds(array $filters = []): array
    {
        $db = Database::getConnection();
        $where = ["l.is_archived = 0"];
        $params = [];

        if (!empty($filters['unassigned_only']) && Auth::isAdmin()) {
            $where[] = "(l.assigned_to IS NULL OR l.assigned_to = 0)";
        } else {
            if (Auth::isEmployee()) {
                $where[] = "l.assigned_to = ?";
                $params[] = Auth::id();
            } elseif (!empty($filters['assigned_to'])) {
                $where[] = "l.assigned_to = ?";
                $params[] = (int)$filters['assigned_to'];
            }
        }

        if (!empty($filters['session_id'])) {
            $where[] = "l.session_id = ?";
            $params[] = (int)$filters['session_id'];
        }

        if (!empty($filters['status'])) {
            if (in_array($filters['status'], ['public_enquiry', 'Public Enquiry', 'Public Enquiries', 'Website / Online Enquiry'])) {
                $where[] = "(l.enquiry_source = 'Website / Online Enquiry' OR l.enquiry_source LIKE '%Online Enquiry%' OR l.enquiry_source LIKE '%Public%')";
            } else {
                $where[] = "l.status = ?";
                $params[] = $filters['status'];
            }
        }

        if (!empty($filters['class_applied'])) {
            $classSynonyms = self::getClassSynonyms($filters['class_applied']);
            if (count($classSynonyms) === 1) {
                $where[] = "l.class_applied = ?";
                $params[] = $classSynonyms[0];
            } else {
                $ph = implode(',', array_fill(0, count($classSynonyms), '?'));
                $where[] = "l.class_applied IN ($ph)";
                foreach ($classSynonyms as $cs) {
                    $params[] = $cs;
                }
            }
        }

        if (!empty($filters['enquiry_source'])) {
            if ($filters['enquiry_source'] === 'Admission Manager') {
                $where[] = "(l.enquiry_source = ? OR l.enquiry_source LIKE 'Admission Manager%')";
                $params[] = $filters['enquiry_source'];
            } else {
                $where[] = "l.enquiry_source = ?";
                $params[] = $filters['enquiry_source'];
            }
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
            for ($i = 0; $i < 6; $i++) {
                $params[] = $searchTerm;
            }
        }

        $whereClause = implode(" AND ", $where);
        $stmt = $db->prepare("SELECT l.id FROM leads l WHERE {$whereClause}");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    /**
     * Resolves canonical class names and legacy Roman numeral synonyms
     * (e.g. Grade 1 <=> I, Grade 11 - Science <=> XI(SCIENCE))
     */
    public static function getClassSynonyms(string $class): array
    {
        $clean = trim($class);
        $map = [
            'Pre-Nursery'           => ['Pre-Nursery', 'Pre Nursery', 'Playgroup', 'Play Group', 'PRE-NURSERY'],
            'Nursery'               => ['Nursery', 'NURSERY', 'Nur'],
            'LKG'                   => ['LKG', 'L.K.G.', 'L.K.G', 'Lower KG'],
            'UKG'                   => ['UKG', 'U.K.G.', 'U.K.G', 'Upper KG'],
            'Grade 1'               => ['Grade 1', 'Class 1', 'Class I', 'I', 'Grade I', '1st', '1'],
            'Grade 2'               => ['Grade 2', 'Class 2', 'Class II', 'II', 'Grade II', '2nd', '2'],
            'Grade 3'               => ['Grade 3', 'Class 3', 'Class III', 'III', 'Grade III', '3rd', '3'],
            'Grade 4'               => ['Grade 4', 'Class 4', 'Class IV', 'IV', 'Grade IV', '4th', '4'],
            'Grade 5'               => ['Grade 5', 'Class 5', 'Class V', 'V', 'Grade V', '5th', '5'],
            'Grade 6'               => ['Grade 6', 'Class 6', 'Class VI', 'VI', 'Grade VI', '6th', '6'],
            'Grade 7'               => ['Grade 7', 'Class 7', 'Class VII', 'VII', 'Grade VII', '7th', '7'],
            'Grade 8'               => ['Grade 8', 'Class 8', 'Class VIII', 'VIII', 'Grade VIII', '8th', '8'],
            'Grade 9'               => ['Grade 9', 'Class 9', 'Class IX', 'IX', 'Grade IX', '9th', '9'],
            'Grade 10'              => ['Grade 10', 'Class 10', 'Class X', 'X', 'Grade X', '10th', '10'],
            'Grade 11 - Science'    => ['Grade 11 - Science', 'Grade 11 (Science)', 'XI(SCIENCE)', 'XI - Science', 'XI Science', '11 Science', 'Class XI Science', 'Class 11 Science'],
            'Grade 11 - Commerce'   => ['Grade 11 - Commerce', 'Grade 11 (Commerce)', 'XI(COMMERCE)', 'XI - Commerce', 'XI Commerce', '11 Commerce', 'Class XI Commerce', 'Class 11 Commerce'],
            'Grade 11 - Humanities' => ['Grade 11 - Humanities', 'Grade 11 (Humanities)', 'XI(HUMANITIES)', 'XI - Humanities', 'XI Humanities', 'XI - Arts', 'XI Arts', '11 Humanities', 'Class XI Humanities', 'Class 11 Humanities'],
            'Grade 12 - Science'    => ['Grade 12 - Science', 'Grade 12 (Science)', 'XII(SCIENCE)', 'XII - Science', 'XII Science', '12 Science', 'Class XII Science', 'Class 12 Science'],
            'Grade 12 - Commerce'   => ['Grade 12 - Commerce', 'Grade 12 (Commerce)', 'XII(COMMERCE)', 'XII - Commerce', 'XII Commerce', '12 Commerce', 'Class XII Commerce', 'Class 12 Commerce'],
            'Grade 12 - Humanities' => ['Grade 12 - Humanities', 'Grade 12 (Humanities)', 'XII(HUMANITIES)', 'XII - Humanities', 'XII Humanities', 'XII - Arts', 'XII Arts', '12 Humanities', 'Class XII Humanities', 'Class 12 Humanities'],
        ];

        foreach ($map as $canonical => $synonyms) {
            if (strcasecmp($canonical, $clean) === 0) {
                return array_values(array_unique(array_merge([$canonical], $synonyms)));
            }
            foreach ($synonyms as $syn) {
                if (strcasecmp($syn, $clean) === 0) {
                    return array_values(array_unique(array_merge([$canonical], $synonyms)));
                }
            }
        }
        return [$clean];
    }
}

