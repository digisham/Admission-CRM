<?php
namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\View;
use App\Models\AcademicSession;
use App\Models\FollowupLog;
use App\Models\Lead;
use App\Models\Task;
use App\Models\User;

class DashboardController
{
    public function index(): void
    {
        Auth::startSession();
        if (!Auth::check()) {
            View::redirect(View::url('login'));
        }

        $activeSession = AcademicSession::getActive();
        $selectedSessionId = $_SESSION['selected_session_id'] ?? $activeSession['id'];

        if (Auth::isEmployee()) {
            // Admission Counselor Dashboard
            $userId = Auth::id();
            $metrics = Lead::getDashboardMetrics($selectedSessionId, $userId);

            // Fetch tasks for this counselor (assigned, created, or on their leads)
            $todayTasks = Task::all([
                'session_id' => $selectedSessionId,
                'due_filter' => 'today',
                'limit' => 10
            ]);

            $overdueTasks = Task::all([
                'session_id' => $selectedSessionId,
                'due_filter' => 'overdue',
                'limit' => 10
            ]);

            $upcomingTasks = Task::all([
                'session_id' => $selectedSessionId,
                'due_filter' => 'upcoming',
                'limit' => 10
            ]);

            // Recent leads assigned to this counselor
            $recentLeads = Lead::paginate(['assigned_to' => $userId, 'session_id' => $selectedSessionId], 1, 5)['data'];

            View::render('dashboard/employee', [
                'metrics' => $metrics,
                'todayTasks' => $todayTasks,
                'overdueTasks' => $overdueTasks,
                'upcomingTasks' => $upcomingTasks,
                'recentLeads' => $recentLeads,
                'selectedSessionId' => $selectedSessionId,
            ]);
        } else {
            // Admin / Super Admin Dashboard
            $metrics = Lead::getDashboardMetrics($selectedSessionId);
            $workload = User::getWorkload($selectedSessionId);

            // Recent leads
            $recentLeads = Lead::paginate(['session_id' => $selectedSessionId], 1, 6)['data'];

            // Recent activity call logs
            $recentActivities = FollowupLog::all([
                'session_id' => $selectedSessionId,
                'limit' => 6
            ]);

            // Overdue tasks
            $overdueTasks = Task::all([
                'session_id' => $selectedSessionId,
                'due_filter' => 'overdue',
                'limit' => 5
            ]);

            View::render('dashboard/admin', [
                'metrics' => $metrics,
                'workload' => $workload,
                'recentLeads' => $recentLeads,
                'recentActivities' => $recentActivities,
                'overdueTasks' => $overdueTasks,
                'selectedSessionId' => $selectedSessionId,
            ]);
        }
    }

    public function switchSession(): void
    {
        Auth::startSession();
        $sessionId = (int)($_GET['session_id'] ?? $_POST['session_id'] ?? 0);
        if ($sessionId > 0 && AcademicSession::find($sessionId)) {
            $_SESSION['selected_session_id'] = $sessionId;
            View::flash('success', "View session switched successfully.");
        }
        $referer = $_SERVER['HTTP_REFERER'] ?? View::url('dashboard');
        header("Location: {$referer}");
        exit;
    }
}
