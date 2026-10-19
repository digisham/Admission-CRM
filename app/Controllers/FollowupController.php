<?php
namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\View;
use App\Models\AcademicSession;
use App\Models\FollowupLog;
use App\Models\Lead;
use App\Models\Setting;
use App\Models\User;

class FollowupController
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
            'outcome' => $_GET['outcome'] ?? null,
        ];

        $logs = FollowupLog::all($filters);
        $counselors = User::getActiveCounselors();

        View::render('followups/index', [
            'logs' => $logs,
            'filters' => $filters,
            'counselors' => $counselors,
        ]);
    }

    public function store(): void
    {
        if (!Csrf::validate($_POST['_csrf_token'] ?? null)) {
            View::flash('error', 'Security token invalid.');
            View::redirect(View::url('followups'));
        }

        $leadId = (int)($_POST['lead_id'] ?? 0);
        $outcome = trim($_POST['outcome'] ?? '');
        $remarks = trim($_POST['remarks'] ?? '');

        if ($leadId <= 0 || empty($outcome) || empty($remarks)) {
            View::flash('error', 'Lead ID, contact outcome, and discussion remarks are required.');
            View::redirect($_SERVER['HTTP_REFERER'] ?? View::url('leads'));
        }

        $lead = Lead::find($leadId);
        if (!$lead || !Auth::canAccessLead($lead)) {
            View::flash('error', 'Unauthorized access to lead.');
            View::redirect(View::url('leads'));
        }

        FollowupLog::create([
            'lead_id' => $leadId,
            'contact_method' => $_POST['contact_method'] ?? 'Phone',
            'outcome' => $outcome,
            'remarks' => $remarks,
            'next_followup_date' => !empty($_POST['next_followup_date']) ? $_POST['next_followup_date'] : null,
            'lead_status' => !empty($_POST['lead_status']) ? $_POST['lead_status'] : null,
        ]);

        View::flash('success', 'Call log and follow-up entry saved successfully.');
        $returnUrl = $_POST['return_url'] ?? View::url("leads/show&id={$leadId}");
        header("Location: {$returnUrl}");
        exit;
    }
}
