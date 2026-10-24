<?php
namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\View;
use App\Models\AcademicSession;
use App\Models\Task;
use App\Models\User;

class TaskController
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
            'assigned_to' => $_GET['assigned_to'] ?? null,
            'status' => $_GET['status'] ?? null,
            'priority' => $_GET['priority'] ?? null,
            'due_filter' => $_GET['due_filter'] ?? null,
            'search' => $_GET['search'] ?? null,
        ];

        if (Auth::isEmployee()) {
            $filters['assigned_to'] = Auth::id();
        }

        $tasks = Task::all($filters);
        $counselors = User::getActiveCounselors();

        View::render('tasks/index', [
            'tasks' => $tasks,
            'filters' => $filters,
            'counselors' => $counselors,
        ]);
    }

    public function store(): void
    {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || (isset($_REQUEST['format']) && $_REQUEST['format'] === 'json')
               || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if (!Csrf::validate($_POST['_csrf_token'] ?? null)) {
            if ($isAjax) {
                View::json(['status' => 'error', 'message' => 'Security token invalid. Please refresh.'], 403);
            }
            View::flash('error', 'Security token invalid.');
            View::redirect(View::url('tasks'));
        }

        $leadId = (int)($_POST['lead_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $assignedTo = (int)($_POST['assigned_to'] ?? Auth::id());
        $dueDate = $_POST['due_date'] ?? date('Y-m-d H:i:s', strtotime('+1 day'));

        if (empty($title) || $leadId <= 0 || $assignedTo <= 0) {
            if ($isAjax) {
                View::json(['status' => 'error', 'message' => 'Task title, lead selection, and assignee are required.'], 422);
            }
            View::flash('error', 'Task title, lead selection, and assignee are required.');
            View::redirect($_SERVER['HTTP_REFERER'] ?? View::url('tasks'));
        }

        $taskId = Task::create([
            'lead_id' => $leadId,
            'title' => $title,
            'task_type' => $_POST['task_type'] ?? 'Follow-up Call',
            'description' => $_POST['description'] ?? null,
            'assigned_to' => $assignedTo,
            'due_date' => $dueDate,
            'priority' => $_POST['priority'] ?? 'Medium',
        ]);

        if ($isAjax) {
            $assignee = User::find($assignedTo);
            View::json([
                'status' => 'success',
                'message' => 'Task created successfully.',
                'task' => [
                    'id' => $taskId,
                    'title' => $title,
                    'task_type' => $_POST['task_type'] ?? 'Follow-up Call',
                    'assignee_name' => $assignee['name'] ?? 'Staff',
                    'due_date' => $dueDate,
                    'formatted_due' => date('d M Y, h:i A', strtotime($dueDate)),
                    'priority' => $_POST['priority'] ?? 'Medium',
                    'status' => 'Pending',
                    'description' => $_POST['description'] ?? null
                ]
            ]);
        }

        View::flash('success', 'Task created successfully.');
        $returnUrl = $_POST['return_url'] ?? View::url('tasks');
        header("Location: {$returnUrl}");
        exit;
    }

    public function complete(): void
    {
        $isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
               || (isset($_REQUEST['format']) && $_REQUEST['format'] === 'json')
               || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

        if (!Csrf::validate($_POST['_csrf_token'] ?? null)) {
            if ($isAjax) {
                View::json(['status' => 'error', 'message' => 'Security token invalid. Please refresh.'], 403);
            }
            View::flash('error', 'Security token invalid.');
            View::redirect(View::url('tasks'));
        }

        $taskId = (int)($_POST['task_id'] ?? 0);
        $remarks = trim($_POST['completion_remarks'] ?? 'Task marked as completed');

        $task = Task::find($taskId);
        if (!$task) {
            if ($isAjax) {
                View::json(['status' => 'error', 'message' => 'Task not found.'], 404);
            }
            View::flash('error', 'Task not found.');
            View::redirect(View::url('tasks'));
        }

        // Check permission: must be admin, assignee, creator, or assigned lead owner
        $isLeadOwner = false;
        if (!empty($task['lead_id'])) {
            $lead = \App\Models\Lead::find((int)$task['lead_id']);
            $isLeadOwner = ($lead && (int)$lead['assigned_to'] === Auth::id());
        }

        if (!Auth::isAdmin() && (int)$task['assigned_to'] !== Auth::id() && (int)$task['created_by'] !== Auth::id() && !$isLeadOwner) {
            if ($isAjax) {
                View::json(['status' => 'error', 'message' => 'You are not authorized to complete this task.'], 403);
            }
            View::flash('error', 'You are not authorized to complete this task.');
            View::redirect(View::url('tasks'));
        }

        Task::complete($taskId, $remarks, Auth::id());

        if ($isAjax) {
            View::json([
                'status' => 'success',
                'message' => 'Task completed successfully.',
                'task_id' => $taskId,
                'status_label' => 'Completed',
                'remarks' => $remarks
            ]);
        }

        View::flash('success', 'Task completed successfully.');
        $returnUrl = $_POST['return_url'] ?? View::url('tasks');
        header("Location: {$returnUrl}");
        exit;
    }
}
