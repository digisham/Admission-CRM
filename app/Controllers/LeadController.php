<?php
namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\View;
use App\Models\AcademicSession;
use App\Models\FollowupLog;
use App\Models\Lead;
use App\Models\LeadDocument;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;

class LeadController
{
    public function index(): void
    {
        if (!Auth::check()) {
            View::redirect(View::url('login'));
        }

        $activeSession = AcademicSession::getActive();
        $selectedSessionId = $_SESSION['selected_session_id'] ?? $activeSession['id'];

        $filters = [
            'session_id' => $_GET['session_id'] ?? $selectedSessionId,
            'status' => $_GET['status'] ?? null,
            'class_applied' => $_GET['class_applied'] ?? null,
            'enquiry_source' => $_GET['enquiry_source'] ?? null,
            'assigned_to' => $_GET['assigned_to'] ?? null,
            'priority' => $_GET['priority'] ?? null,
            'search' => $_GET['search'] ?? null,
            'date_from' => $_GET['date_from'] ?? null,
            'date_to' => $_GET['date_to'] ?? null,
            'followup_due' => $_GET['followup_due'] ?? null,
        ];

        if (Auth::isEmployee()) {
            $filters['assigned_to'] = Auth::id();
        }

        $perPageParam = $_GET['per_page'] ?? $_GET['limit'] ?? 15;
        if ($perPageParam === 'all' || $perPageParam === '-1') {
            $perPage = -1;
            $filters['per_page'] = 'all';
        } else {
            $perPage = max(1, min((int)$perPageParam, 5000));
            $filters['per_page'] = $perPage;
        }

        $page = (int)($_GET['page'] ?? 1);
        $paginated = Lead::paginate($filters, $page, $perPage);

        $counselors = User::getActiveCounselors();
        $sources = Setting::getLeadSources();
        $statuses = Setting::getLeadStatuses();
        $classes = Setting::getClassesOffered();

        View::render('leads/index', [
            'leads' => $paginated['data'],
            'pagination' => $paginated,
            'filters' => $filters,
            'counselors' => $counselors,
            'sources' => $sources,
            'statuses' => $statuses,
            'classes' => $classes,
            'pageTitle' => 'Admission Enquiries & Leads'
        ]);
    }

    public function unassigned(): void
    {
        if (!Auth::check()) {
            View::redirect(View::url('login'));
        }

        if (!Auth::isAdmin()) {
            View::flash('error', 'Access denied. The Unassigned Leads Queue is restricted to Super Admin and Admission Manager only.');
            View::redirect(View::url('dashboard'));
        }

        $activeSession = AcademicSession::getActive();
        $selectedSessionId = $_SESSION['selected_session_id'] ?? $activeSession['id'];

        $filters = [
            'session_id' => $_GET['session_id'] ?? $selectedSessionId,
            'unassigned_only' => true,
            'search' => $_GET['search'] ?? null,
            'class_applied' => $_GET['class_applied'] ?? null,
            'enquiry_source' => $_GET['enquiry_source'] ?? null,
        ];

        $page = (int)($_GET['page'] ?? 1);
        $paginated = Lead::paginate($filters, $page, 15);

        $counselors = User::getActiveCounselors();
        $classes = Setting::getClassesOffered();
        $sources = Setting::getLeadSources();

        View::render('leads/unassigned', [
            'leads' => $paginated['data'],
            'pagination' => $paginated,
            'filters' => $filters,
            'counselors' => $counselors,
            'classes' => $classes,
            'sources' => $sources,
            'pageTitle' => 'Unassigned Leads Queue'
        ]);
    }

    public function create(): void
    {
        if (!Auth::check()) {
            View::redirect(View::url('login'));
        }

        $activeSession = AcademicSession::getActive();
        $counselors = User::getActiveCounselors();
        $admissionManagers = User::getAdmissionManagers();
        $sources = Setting::getLeadSources();
        $statuses = Setting::getLeadStatuses();
        $classes = Setting::getClassesOffered();

        View::render('leads/create', [
            'activeSession' => $activeSession,
            'counselors' => $counselors,
            'admissionManagers' => $admissionManagers,
            'sources' => $sources,
            'statuses' => $statuses,
            'classes' => $classes,
        ]);
    }

    public function store(): void
    {
        if (!Csrf::validate($_POST['_csrf_token'] ?? null)) {
            View::flash('error', 'Security token invalid.');
            View::redirect(View::url('leads/create'));
        }

        $studentName = trim($_POST['student_name'] ?? '');
        $parentName = trim($_POST['parent_name'] ?? '');
        $mobile = Sanitizer::cleanPhone($_POST['mobile'] ?? '');
        $sessionId = (int)($_POST['session_id'] ?? AcademicSession::getActive()['id']);

        if (empty($studentName) || empty($parentName) || empty($mobile)) {
            View::flash('error', 'Student Name, Parent Name, and Mobile Number are required.');
            View::redirect(View::url('leads/create'));
        }

        // Duplicate check
        $duplicates = Lead::findDuplicates($mobile, $sessionId);
        $confirmDuplicate = !empty($_POST['confirm_duplicate']);

        if (!empty($duplicates) && !$confirmDuplicate) {
            View::flash('warning', "A lead with mobile number {$mobile} already exists in this academic session ({$duplicates[0]['lead_ref']} - {$duplicates[0]['student_name']}). Please check duplicate confirmation to proceed.");
            View::redirect(View::url('leads/create'));
        }

        $rawAssigned = $_POST['assigned_to'] ?? 'auto';
        $assignedTo = null;
        $autoAssignedName = null;

        if (Auth::isEmployee()) {
            // Counselor logging enquiry always assigns to themselves
            $assignedTo = Auth::id();
        } elseif ($rawAssigned === 'auto') {
            // Auto-distribute equally across active admission counselors
            $assignedTo = Lead::getNextCounselorForAssignment($sessionId);
            if ($assignedTo) {
                $cUser = User::find($assignedTo);
                $autoAssignedName = $cUser['name'] ?? 'Counselor';
            }
        } elseif (!empty($rawAssigned)) {
            $assignedTo = (int)$rawAssigned;
        }

        $enquirySource = trim($_POST['enquiry_source'] ?? 'Direct Enquiry');
        $managerName = trim($_POST['admission_manager_name'] ?? '');
        if ($enquirySource === 'Admission Manager' && !empty($managerName)) {
            $enquirySource = 'Admission Manager - ' . $managerName;
        }

        $newId = Lead::create([
            'session_id' => $sessionId,
            'student_name' => $studentName,
            'parent_name' => $parentName,
            'mobile' => $mobile,
            'alternate_mobile' => Sanitizer::cleanPhone($_POST['alternate_mobile'] ?? null),
            'email' => !empty($_POST['email']) ? trim($_POST['email']) : null,
            'class_applied' => $_POST['class_applied'] ?? 'Grade 1',
            'enquiry_date' => !empty($_POST['enquiry_date']) ? $_POST['enquiry_date'] : date('Y-m-d'),
            'enquiry_source' => $enquirySource,
            'assigned_to' => $assignedTo,
            'status' => $_POST['status'] ?? 'New',
            'priority' => $_POST['priority'] ?? 'Medium',
            'next_followup_date' => !empty($_POST['next_followup_date']) ? $_POST['next_followup_date'] : null,
            'address' => $_POST['address'] ?? null,
            'city' => $_POST['city'] ?? 'Purnea',
            'remarks' => $_POST['remarks'] ?? null,
        ]);

        if ($newId && $assignedTo && !empty($_POST['next_followup_date'])) {
            Task::create([
                'lead_id' => $newId,
                'session_id' => $sessionId,
                'title' => "Follow-up: {$studentName}",
                'task_type' => 'Follow-up Call',
                'description' => "Initial enquiry follow-up call with parent of {$studentName}",
                'assigned_to' => $assignedTo,
                'due_date' => $_POST['next_followup_date'],
                'priority' => $_POST['priority'] ?? 'Medium',
            ]);
        }

        $flashMsg = $autoAssignedName 
            ? "Lead created successfully with ID #{$newId} and auto-distributed to counselor {$autoAssignedName}." 
            : "Lead created successfully with ID #{$newId}!";
        View::flash('success', $flashMsg);
        View::redirect(View::url("leads/show&id={$newId}"));
    }

    public function show(): void
    {
        if (!Auth::check()) {
            View::redirect(View::url('login'));
        }

        $id = (int)($_GET['id'] ?? 0);
        $lead = Lead::find($id);

        if (!$lead) {
            View::flash('error', 'Lead record not found.');
            View::redirect(View::url('leads'));
        }

        if (!Auth::canAccessLead($lead)) {
            View::flash('error', 'Access denied. You do not have permission to view this lead.');
            View::redirect(View::url('leads'));
        }

        $timeline = Lead::getTimeline($id);
        $tasks = Task::all(['lead_id' => $id]);
        $checklist = LeadDocument::getChecklistForLead($id);
        $counselors = User::getActiveCounselors();
        $statuses = Setting::getLeadStatuses();

        View::render('leads/show', [
            'lead' => $lead,
            'timeline' => $timeline,
            'tasks' => $tasks,
            'checklist' => $checklist,
            'counselors' => $counselors,
            'statuses' => $statuses,
        ]);
    }

    public function edit(): void
    {
        if (!Auth::check()) {
            View::redirect(View::url('login'));
        }

        $id = (int)($_GET['id'] ?? 0);
        $lead = Lead::find($id);

        if (!$lead || !Auth::canAccessLead($lead)) {
            View::flash('error', 'Lead record not found or access denied.');
            View::redirect(View::url('leads'));
        }

        $counselors = User::getActiveCounselors();
        $admissionManagers = User::getAdmissionManagers();
        $sources = Setting::getLeadSources();
        $statuses = Setting::getLeadStatuses();
        $classes = Setting::getClassesOffered();

        View::render('leads/edit', [
            'lead' => $lead,
            'counselors' => $counselors,
            'admissionManagers' => $admissionManagers,
            'sources' => $sources,
            'statuses' => $statuses,
            'classes' => $classes,
        ]);
    }

    public function update(): void
    {
        if (!Csrf::validate($_POST['_csrf_token'] ?? null)) {
            View::flash('error', 'Security token invalid.');
            View::redirect(View::url('leads'));
        }

        $id = (int)($_POST['id'] ?? 0);
        $lead = Lead::find($id);

        if (!$lead || !Auth::canAccessLead($lead)) {
            View::flash('error', 'Access denied.');
            View::redirect(View::url('leads'));
        }

        $studentName = trim($_POST['student_name'] ?? '');
        $parentName = trim($_POST['parent_name'] ?? '');
        $mobile = Sanitizer::cleanPhone($_POST['mobile'] ?? '');

        if (empty($studentName) || empty($parentName) || empty($mobile)) {
            View::flash('error', 'Student Name, Parent Name, and Mobile Number are required.');
            View::redirect(View::url("leads/edit&id={$id}"));
        }

        $enquirySource = trim($_POST['enquiry_source'] ?? $lead['enquiry_source']);
        $managerName = trim($_POST['admission_manager_name'] ?? '');
        if ($enquirySource === 'Admission Manager' && !empty($managerName)) {
            $enquirySource = 'Admission Manager - ' . $managerName;
        }

        $newStatus = $_POST['status'] ?? $lead['status'];
        $newPriority = $_POST['priority'] ?? $lead['priority'];
        $authorId = Auth::id() ?? 1;

        if ($newStatus !== $lead['status']) {
            try {
                FollowupLog::create([
                    'lead_id' => $id,
                    'contact_method' => 'Status Change',
                    'outcome' => 'Status Update',
                    'lead_status' => $newStatus,
                    'remarks' => "Status changed from '{$lead['status']}' to '{$newStatus}' (via Edit Form)",
                    'created_by' => $authorId
                ]);
            } catch (\Throwable) {}
        }

        if ($newPriority !== $lead['priority']) {
            try {
                FollowupLog::create([
                    'lead_id' => $id,
                    'contact_method' => 'Priority Change',
                    'outcome' => 'Priority Update',
                    'lead_status' => $newStatus,
                    'remarks' => "Priority changed from '{$lead['priority']}' to '{$newPriority}' (via Edit Form)",
                    'created_by' => $authorId
                ]);
            } catch (\Throwable) {}
        }

        Lead::update($id, [
            'student_name' => $studentName,
            'parent_name' => $parentName,
            'mobile' => $mobile,
            'alternate_mobile' => Sanitizer::cleanPhone($_POST['alternate_mobile'] ?? null),
            'email' => !empty($_POST['email']) ? trim($_POST['email']) : null,
            'class_applied' => $_POST['class_applied'] ?? $lead['class_applied'],
            'enquiry_source' => $enquirySource,
            'status' => $newStatus,
            'priority' => $newPriority,
            'next_followup_date' => !empty($_POST['next_followup_date']) ? $_POST['next_followup_date'] : null,
            'address' => $_POST['address'] ?? null,
            'city' => $_POST['city'] ?? 'Purnea',
            'remarks' => $_POST['remarks'] ?? null,
        ]);

        View::flash('success', "Lead {$lead['lead_ref']} updated successfully.");
        View::redirect(View::url("leads/show&id={$id}"));
    }

    public function assign(): void
    {
        $returnUrl = $_POST['return_url'] ?? $_POST['redirect_to'] ?? View::url('leads/unassigned');

        if (!Auth::isAdmin() && !Auth::hasPermission('leads.assign')) {
            View::flash('error', 'You do not have permission to assign leads.');
            View::redirect($returnUrl);
        }

        if (!Csrf::validate($_POST['_csrf_token'] ?? null)) {
            View::flash('error', 'Invalid security token.');
            View::redirect($returnUrl);
        }

        $leadId = (int)($_POST['lead_id'] ?? 0);
        $userId = (int)($_POST['assigned_to'] ?? 0);
        $reason = trim($_POST['reason'] ?? 'Queue assignment');

        if ($leadId > 0 && $userId > 0) {
            $assigned = Lead::assign($leadId, $userId, $reason);
            if ($assigned) {
                $counselor = User::find($userId);
                $counselorName = $counselor['name'] ?? 'Staff';
                View::flash('success', "Lead assigned to {$counselorName} successfully.");
            } else {
                View::flash('error', 'Could not assign lead. Please try again.');
            }
        } else {
            View::flash('error', 'Please select a valid staff member to assign.');
        }

        header("Location: {$returnUrl}");
        exit;
    }

    public function bulkAssign(): void
    {
        $redirect = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : (!empty($_POST['return_url']) ? $_POST['return_url'] : View::url('leads/unassigned'));

        if (!Auth::isAdmin() && !Auth::hasPermission('leads.assign')) {
            View::flash('error', 'You do not have permission to perform assignments.');
            View::redirect($redirect);
        }

        if (!Csrf::validate($_POST['_csrf_token'] ?? null)) {
            View::flash('error', 'Security token invalid.');
            View::redirect($redirect);
        }

        $leadIds = $_POST['lead_ids'] ?? [];
        if (!is_array($leadIds)) {
            $leadIds = array_filter(explode(',', (string)$leadIds));
        }

        $rawAssigned = $_POST['assigned_to'] ?? '';
        $reason = trim($_POST['reason'] ?? 'Bulk assignment from unassigned queue');

        if ($rawAssigned === 'auto') {
            if (empty($leadIds)) {
                View::flash('error', 'Please select at least one lead from the queue.');
                View::redirect($redirect);
            }
            $distResult = Lead::bulkEqualDistribute($leadIds, $reason ?: 'Automated equal distribution', Auth::id());
            $counselors = User::getAdmissionCounselors();
            $names = implode(', ', array_map(fn($c) => $c['name'], $counselors));
            View::flash('success', "Successfully distributed {$distResult['total']} lead(s) equally among counselors ({$names}).");
            View::redirect($redirect);
        }

        $userId = (int)$rawAssigned;

        if (empty($leadIds) || $userId <= 0) {
            View::flash('error', 'Please select at least one lead from the list and an active staff counselor.');
            View::redirect($redirect);
        }

        $initialTask = null;
        if (!empty($_POST['create_task']) && !empty($_POST['task_title'])) {
            $initialTask = [
                'title' => trim($_POST['task_title']),
                'task_type' => $_POST['task_type'] ?? 'Follow-up Call',
                'due_date' => !empty($_POST['task_due_date']) ? $_POST['task_due_date'] : date('Y-m-d H:i:s', strtotime('+1 day')),
                'priority' => $_POST['task_priority'] ?? 'Medium',
            ];
        }

        $assignedCount = Lead::bulkAssign($leadIds, $userId, $reason, Auth::id(), $initialTask);
        $counselor = User::find($userId);
        $counselorName = $counselor['name'] ?? 'Counselor';

        View::flash('success', "Successfully allocated {$assignedCount} lead(s) to {$counselorName}.");
        View::redirect($redirect);
    }

    public function checkDuplicate(): void
    {
        $mobile = $_GET['mobile'] ?? '';
        $sessionId = (int)($_GET['session_id'] ?? AcademicSession::getActive()['id']);
        $excludeId = !empty($_GET['exclude_id']) ? (int)$_GET['exclude_id'] : null;

        $dups = Lead::findDuplicates($mobile, $sessionId, $excludeId);
        View::json([
            'is_duplicate' => !empty($dups),
            'count' => count($dups),
            'duplicates' => $dups
        ]);
    }

    public function quickUpdate(): void
    {
        header('Content-Type: application/json');
        if (!Auth::check()) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        $id = (int)($_POST['lead_id'] ?? 0);
        $field = trim($_POST['field'] ?? '');
        $value = trim($_POST['value'] ?? '');

        if ($id <= 0 || !in_array($field, ['status', 'priority'])) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid parameters']);
            exit;
        }

        $lead = Lead::find($id);
        if (!$lead || !Auth::canAccessLead($lead)) {
            echo json_encode(['status' => 'error', 'message' => 'Lead not found or permission denied']);
            exit;
        }

        if ($field === 'status') {
            $allowedStatuses = Setting::getLeadStatuses();
            if (!in_array($value, $allowedStatuses)) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid status selected']);
                exit;
            }
        } elseif ($field === 'priority') {
            if (!in_array($value, ['Low', 'Medium', 'High', 'Urgent'])) {
                echo json_encode(['status' => 'error', 'message' => 'Invalid priority selected']);
                exit;
            }
        }

        $oldValue = $lead[$field] ?? '';
        $updated = Lead::update($id, [$field => $value]);
        if ($updated) {
            $user = Auth::user();
            $authorName = $user['name'] ?? 'Staff';
            $remarks = trim($_POST['remarks'] ?? '');

            if ($field === 'status') {
                $statusRemark = ($oldValue !== $value && $oldValue !== '')
                    ? "Status changed from '{$oldValue}' to '{$value}'"
                    : "Status set to '{$value}'";
                if ($remarks !== '') {
                    $statusRemark .= " — Note: " . $remarks;
                }
                try {
                    FollowupLog::create([
                        'lead_id' => $id,
                        'contact_method' => 'Status Change',
                        'outcome' => 'Status Update',
                        'lead_status' => $value,
                        'remarks' => $statusRemark,
                        'created_by' => Auth::id() ?? 1
                    ]);
                } catch (\Throwable) {
                    // Non-critical log failure
                }
            } elseif ($field === 'priority') {
                $priorityRemark = ($oldValue !== $value && $oldValue !== '')
                    ? "Priority changed from '{$oldValue}' to '{$value}'"
                    : "Priority set to '{$value}'";
                if ($remarks !== '') {
                    $priorityRemark .= " — Note: " . $remarks;
                }
                try {
                    FollowupLog::create([
                        'lead_id' => $id,
                        'contact_method' => 'Priority Change',
                        'outcome' => 'Priority Update',
                        'lead_status' => $lead['status'],
                        'remarks' => $priorityRemark,
                        'created_by' => Auth::id() ?? 1
                    ]);
                } catch (\Throwable) {
                    // Non-critical log failure
                }
            }

            $slug = preg_replace('/[^a-zA-Z0-9]+/', '-', trim($value));
            $nowTime = date('h:i A');
            echo json_encode([
                'status' => 'success',
                'lead_id' => $id,
                'field' => $field,
                'value' => $value,
                'old_value' => $oldValue,
                'slug' => $slug,
                'author' => $authorName,
                'formatted_time' => 'Today at ' . $nowTime,
                'remarks' => ($field === 'status' ? "Status changed from '{$oldValue}' to '{$value}'" : "Priority changed from '{$oldValue}' to '{$value}'") . ($remarks ? " — Note: {$remarks}" : ""),
                'message' => ($field === 'status')
                    ? "Enquiry {$lead['lead_ref']} status updated to {$value} by {$authorName}."
                    : "Enquiry {$lead['lead_ref']} priority updated to {$value} by {$authorName}."
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update enquiry record.']);
        }
        exit;
    }

    public function quickDetails(): void
    {
        header('Content-Type: application/json');
        if (!Auth::check()) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        $id = (int)($_GET['id'] ?? 0);
        $lead = Lead::find($id);

        if (!$lead || !Auth::canAccessLead($lead)) {
            echo json_encode(['status' => 'error', 'message' => 'Lead not found or permission denied']);
            exit;
        }

        $followups = FollowupLog::all(['lead_id' => $id]);
        $tasks = Task::all(['lead_id' => $id]);

        $stagePercentages = [
            'New' => 15,
            'Contacted' => 30,
            'Follow-up Scheduled' => 50,
            'Campus Visit Scheduled' => 65,
            'Campus Visited' => 76,
            'Form Submitted' => 88,
            'Admission Confirmed' => 96,
            'Enrolled' => 100,
            'Fee Paid' => 100,
            'Lost / Unreachable' => 20,
            'Not Interested' => 10,
            'Rejected' => 10
        ];
        $lead['progress_percent'] = $stagePercentages[$lead['status']] ?? 40;
        $lead['status_slug'] = trim(preg_replace('/[^a-zA-Z0-9]+/', '-', $lead['status']), '-');
        $lead['student_initial'] = strtoupper(substr($lead['student_name'] ?? 'S', 0, 1));
        $lead['clean_mobile'] = preg_replace('/\D/', '', $lead['mobile'] ?? '');

        // Fetch assignment history for activities
        $db = \App\Config\Database::getConnection();
        $assignStmt = $db->prepare("SELECT lah.*, prev.name as prev_name, cur.name as cur_name, by_u.name as by_name
                                   FROM lead_assignment_history lah
                                   LEFT JOIN users prev ON lah.previous_user_id = prev.id
                                   LEFT JOIN users cur ON lah.new_user_id = cur.id
                                   LEFT JOIN users by_u ON lah.assigned_by = by_u.id
                                   WHERE lah.lead_id = ?
                                   ORDER BY lah.created_at DESC");
        $assignStmt->execute([$id]);
        $assignments = $assignStmt->fetchAll();

        $timeline = Lead::getTimeline($id);
        foreach ($timeline as &$item) {
            $time = strtotime($item['created_at']);
            $today = strtotime('today');
            $yesterday = strtotime('yesterday');
            if ($time >= $today) {
                $item['formatted_time'] = 'Today at ' . date('h:i A', $time);
            } elseif ($time >= $yesterday) {
                $item['formatted_time'] = 'Yesterday at ' . date('h:i A', $time);
            } else {
                $item['formatted_time'] = date('d M Y, h:i A', $time);
            }
        }
        unset($item);

        foreach ($followups as &$f) {
            $time = strtotime($f['created_at']);
            $today = strtotime('today');
            $yesterday = strtotime('yesterday');
            if ($time >= $today) {
                $f['formatted_time'] = 'Today at ' . date('h:i A', $time);
            } elseif ($time >= $yesterday) {
                $f['formatted_time'] = 'Yesterday at ' . date('h:i A', $time);
            } else {
                $f['formatted_time'] = date('d M Y, h:i A', $time);
            }
        }
        unset($f);

        echo json_encode([
            'status' => 'success',
            'lead' => $lead,
            'timeline' => $timeline,
            'followups' => array_slice($followups, 0, 8),
            'assignments' => array_slice($assignments, 0, 5),
            'tasks' => array_slice($tasks, 0, 5)
        ]);
        exit;
    }

    private function isAjax(): bool
    {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);
    }

    public function delete(): void
    {
        if (!Auth::check()) {
            View::redirect(View::url('login'));
        }

        // STRICTLY Super Admin only!
        if (!Auth::isSuperAdmin()) {
            $_SESSION['error'] = 'Access denied: Only Super Administrator can delete admission enquiries.';
            View::redirect(View::url('leads'));
        }

        if (!Csrf::validate($_POST['_csrf_token'] ?? null)) {
            $_SESSION['error'] = 'Invalid security token (CSRF). Please try again.';
            View::redirect(View::url('leads'));
        }

        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        $lead = Lead::find($id);

        if (!$lead) {
            $_SESSION['error'] = 'Enquiry not found.';
            View::redirect(View::url('leads'));
        }

        if (Lead::delete($id, Auth::id())) {
            $_SESSION['success'] = "Enquiry {$lead['student_name']} ({$lead['lead_ref']}) was successfully deleted.";
        } else {
            $_SESSION['error'] = 'Failed to delete enquiry.';
        }

        $redirect = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : View::url('leads');
        View::redirect($redirect);
    }

    public function bulkDelete(): void
    {
        if (!Auth::check()) {
            View::redirect(View::url('login'));
        }

        // STRICTLY Super Admin only!
        if (!Auth::isSuperAdmin()) {
            if ($this->isAjax()) {
                View::json(['status' => 'error', 'message' => 'Access denied: Only Super Administrator can delete admission enquiries.']);
                return;
            }
            $_SESSION['error'] = 'Access denied: Only Super Administrator can delete admission enquiries.';
            View::redirect(View::url('leads'));
        }

        if (!Csrf::validate($_POST['_csrf_token'] ?? null)) {
            if ($this->isAjax()) {
                View::json(['status' => 'error', 'message' => 'Security token invalid. Please refresh the page and try again.']);
                return;
            }
            $_SESSION['error'] = 'Security token invalid.';
            View::redirect(View::url('leads'));
        }

        $mode = $_POST['delete_mode'] ?? 'selected'; // 'selected' or 'filtered'
        $deletedCount = 0;

        if ($mode === 'filtered') {
            // Filter-wise deletion (deletes all leads matching current active filters)
            $filters = [
                'session_id' => !empty($_POST['session_id']) ? $_POST['session_id'] : null,
                'status' => !empty($_POST['status']) ? $_POST['status'] : null,
                'class_applied' => !empty($_POST['class_applied']) ? $_POST['class_applied'] : null,
                'enquiry_source' => !empty($_POST['enquiry_source']) ? $_POST['enquiry_source'] : null,
                'assigned_to' => !empty($_POST['assigned_to']) ? $_POST['assigned_to'] : null,
                'priority' => !empty($_POST['priority']) ? $_POST['priority'] : null,
                'search' => !empty($_POST['search']) ? $_POST['search'] : null,
                'date_from' => !empty($_POST['date_from']) ? $_POST['date_from'] : null,
                'date_to' => !empty($_POST['date_to']) ? $_POST['date_to'] : null,
                'followup_due' => !empty($_POST['followup_due']) ? $_POST['followup_due'] : null,
            ];
            $leadIds = Lead::getFilteredIds($filters);
            if (empty($leadIds)) {
                $msg = 'No enquiries found matching the selected filters to delete.';
                if ($this->isAjax()) {
                    View::json(['status' => 'error', 'message' => $msg]);
                    return;
                }
                $_SESSION['error'] = $msg;
                View::redirect(View::url('leads'));
            }
            $deletedCount = Lead::bulkDelete($leadIds, Auth::id());
        } else {
            // Selected leads deletion
            $leadIds = $_POST['lead_ids'] ?? [];
            if (!is_array($leadIds)) {
                $leadIds = array_filter(explode(',', (string)$leadIds));
            }
            $leadIds = array_map('intval', $leadIds);
            $leadIds = array_filter($leadIds, fn($v) => $v > 0);

            if (empty($leadIds)) {
                $msg = 'Please select at least one enquiry from the list to delete.';
                if ($this->isAjax()) {
                    View::json(['status' => 'error', 'message' => $msg]);
                    return;
                }
                $_SESSION['error'] = $msg;
                View::redirect(View::url('leads'));
            }
            $deletedCount = Lead::bulkDelete($leadIds, Auth::id());
        }

        $msg = "Successfully deleted {$deletedCount} enquiry(ies) permanently.";
        if ($this->isAjax()) {
            View::json(['status' => 'success', 'message' => $msg, 'deleted_count' => $deletedCount]);
            return;
        }

        $_SESSION['success'] = $msg;
        $redirect = !empty($_POST['redirect_to']) ? $_POST['redirect_to'] : View::url('leads');
        View::redirect($redirect);
    }
}
