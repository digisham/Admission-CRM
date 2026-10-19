<?php
namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\Sanitizer;
use App\Helpers\View;
use App\Models\AcademicSession;
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

        $page = (int)($_GET['page'] ?? 1);
        $paginated = Lead::paginate($filters, $page, 15);

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
        $sources = Setting::getLeadSources();
        $statuses = Setting::getLeadStatuses();
        $classes = Setting::getClassesOffered();

        View::render('leads/create', [
            'activeSession' => $activeSession,
            'counselors' => $counselors,
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

        $newId = Lead::create([
            'session_id' => $sessionId,
            'student_name' => $studentName,
            'parent_name' => $parentName,
            'mobile' => $mobile,
            'alternate_mobile' => Sanitizer::cleanPhone($_POST['alternate_mobile'] ?? null),
            'email' => !empty($_POST['email']) ? trim($_POST['email']) : null,
            'class_applied' => $_POST['class_applied'] ?? 'Grade 1',
            'enquiry_date' => !empty($_POST['enquiry_date']) ? $_POST['enquiry_date'] : date('Y-m-d'),
            'enquiry_source' => $_POST['enquiry_source'] ?? 'Direct Enquiry',
            'assigned_to' => !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null,
            'status' => $_POST['status'] ?? 'New',
            'priority' => $_POST['priority'] ?? 'Medium',
            'next_followup_date' => !empty($_POST['next_followup_date']) ? $_POST['next_followup_date'] : null,
            'address' => $_POST['address'] ?? null,
            'city' => $_POST['city'] ?? 'Purnea',
            'remarks' => $_POST['remarks'] ?? null,
        ]);

        View::flash('success', "Lead created successfully with ID #{$newId}!");
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
        $sources = Setting::getLeadSources();
        $statuses = Setting::getLeadStatuses();
        $classes = Setting::getClassesOffered();

        View::render('leads/edit', [
            'lead' => $lead,
            'counselors' => $counselors,
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

        Lead::update($id, [
            'student_name' => $studentName,
            'parent_name' => $parentName,
            'mobile' => $mobile,
            'alternate_mobile' => Sanitizer::cleanPhone($_POST['alternate_mobile'] ?? null),
            'email' => !empty($_POST['email']) ? trim($_POST['email']) : null,
            'class_applied' => $_POST['class_applied'] ?? $lead['class_applied'],
            'enquiry_source' => $_POST['enquiry_source'] ?? $lead['enquiry_source'],
            'status' => $_POST['status'] ?? $lead['status'],
            'priority' => $_POST['priority'] ?? $lead['priority'],
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
        if (!Auth::isAdmin()) {
            View::flash('error', 'Only administrators can assign leads.');
            View::redirect(View::url('leads'));
        }

        if (!Csrf::validate($_POST['_csrf_token'] ?? null)) {
            View::flash('error', 'Invalid security token.');
            View::redirect(View::url('leads'));
        }

        $leadId = (int)($_POST['lead_id'] ?? 0);
        $userId = (int)($_POST['assigned_to'] ?? 0);
        $reason = trim($_POST['reason'] ?? 'Manual lead assignment');

        if ($leadId > 0 && $userId > 0) {
            Lead::assign($leadId, $userId, $reason);
            View::flash('success', 'Lead assigned successfully.');
        } else {
            View::flash('error', 'Please select a valid counselor to assign.');
        }

        $returnUrl = $_POST['return_url'] ?? View::url("leads/show&id={$leadId}");
        header("Location: {$returnUrl}");
        exit;
    }

    public function bulkAssign(): void
    {
        if (!Auth::isAdmin()) {
            View::flash('error', 'Only administrators can perform bulk assignments.');
            View::redirect(View::url('leads'));
        }

        if (!Csrf::validate($_POST['_csrf_token'] ?? null)) {
            View::flash('error', 'Security token invalid.');
            View::redirect(View::url('leads'));
        }

        $leadIds = $_POST['lead_ids'] ?? [];
        $userId = (int)($_POST['assigned_to'] ?? 0);
        $reason = trim($_POST['reason'] ?? 'Bulk assignment by admin');

        if (empty($leadIds) || $userId <= 0) {
            View::flash('error', 'Please select at least one lead and an active counselor.');
            View::redirect(View::url('leads'));
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
        View::flash('success', "Successfully assigned {$assignedCount} lead(s) to counselor.");
        View::redirect(View::url('leads'));
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
}
