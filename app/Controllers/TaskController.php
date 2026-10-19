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
        ];

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
        if (!Csrf::validate($_POST['_csrf_token'] ?? null)) {
            View::flash('error', 'Security token invalid.');
            View::redirect(View::url('tasks'));
        }

        $leadId = (int)($_POST['lead_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $assignedTo = (int)($_POST['assigned_to'] ?? Auth::id());
        $dueDate = $_POST['due_date'] ?? date('Y-m-d H:i:s', strtotime('+1 day'));

        if (empty($title) || $leadId <= 0 || $assignedTo <= 0) {
            View::flash('error', 'Task title, lead selection, and assignee are required.');
            View::redirect($_SERVER['HTTP_REFERER'] ?? View::url('tasks'));
        }

        Task::create([
            'lead_id' => $leadId,
            'title' => $title,
            'task_type' => $_POST['task_type'] ?? 'Follow-up Call',
            'description' => $_POST['description'] ?? null,
            'assigned_to' => $assignedTo,
            'due_date' => $dueDate,
            'priority' => $_POST['priority'] ?? 'Medium',
        ]);

        View::flash('success', 'Task created successfully.');
        $returnUrl = $_POST['return_url'] ?? View::url('tasks');
        header("Location: {$returnUrl}");
        exit;
    }

    public function complete(): void
    {
        if (!Csrf::validate($_POST['_csrf_token'] ?? null)) {
            View::flash('error', 'Security token invalid.');
            View::redirect(View::url('tasks'));
        }

        $taskId = (int)($_POST['task_id'] ?? 0);
        $remarks = trim($_POST['completion_remarks'] ?? 'Task marked as completed');

        $task = Task::find($taskId);
        if (!$task) {
            View::flash('error', 'Task not found.');
            View::redirect(View::url('tasks'));
        }

        // Check permission: must be assigned to this user or admin
        if (!Auth::isAdmin() && (int)$task['assigned_to'] !== Auth::id()) {
            View::flash('error', 'You are not authorized to complete this task.');
            View::redirect(View::url('tasks'));
        }

        Task::complete($taskId, $remarks);
        View::flash('success', 'Task completed successfully.');
        $returnUrl = $_POST['return_url'] ?? View::url('tasks');
        header("Location: {$returnUrl}");
        exit;
    }
}
