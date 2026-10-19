<?php
namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\View;
use App\Models\AcademicSession;

class SessionController
{
    public function index(): void
    {
        if (!Auth::isAdmin()) {
            View::flash('error', 'Only administrators can manage academic sessions.');
            View::redirect(View::url('dashboard'));
        }

        $sessions = AcademicSession::all(true);
        View::render('sessions/index', [
            'sessions' => $sessions
        ]);
    }

    public function store(): void
    {
        if (!Auth::isAdmin()) {
            View::flash('error', 'Unauthorized.');
            View::redirect(View::url('sessions'));
        }

        if (!Csrf::validate($_POST['_csrf_token'] ?? null)) {
            View::flash('error', 'Security token invalid.');
            View::redirect(View::url('sessions'));
        }

        $label = trim($_POST['session_label'] ?? '');
        $startDate = $_POST['start_date'] ?? '';
        $endDate = $_POST['end_date'] ?? '';

        if (empty($label) || empty($startDate) || empty($endDate)) {
            View::flash('error', 'Session label, start date, and end date are required.');
            View::redirect(View::url('sessions'));
        }

        try {
            AcademicSession::create([
                'session_label' => $label,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'admission_open_date' => $_POST['admission_open_date'] ?? null,
                'admission_close_date' => $_POST['admission_close_date'] ?? null,
                'is_active' => !empty($_POST['is_active']),
                'notes' => $_POST['notes'] ?? null,
                'created_by' => Auth::id()
            ]);
            View::flash('success', "Academic Session '{$label}' created successfully.");
        } catch (\Throwable $e) {
            View::flash('error', "Could not create session: " . $e->getMessage());
        }

        View::redirect(View::url('sessions'));
    }

    public function update(): void
    {
        if (!Auth::isAdmin()) {
            View::flash('error', 'Unauthorized.');
            View::redirect(View::url('sessions'));
        }

        if (!Csrf::validate($_POST['_csrf_token'] ?? null)) {
            View::flash('error', 'Security token invalid.');
            View::redirect(View::url('sessions'));
        }

        $id = (int)($_POST['id'] ?? 0);
        $label = trim($_POST['session_label'] ?? '');

        if ($id <= 0 || empty($label)) {
            View::flash('error', 'Invalid session details.');
            View::redirect(View::url('sessions'));
        }

        AcademicSession::update($id, [
            'session_label' => $label,
            'start_date' => $_POST['start_date'],
            'end_date' => $_POST['end_date'],
            'admission_open_date' => $_POST['admission_open_date'] ?? null,
            'admission_close_date' => $_POST['admission_close_date'] ?? null,
            'is_active' => !empty($_POST['is_active']),
            'notes' => $_POST['notes'] ?? null,
        ]);

        View::flash('success', "Session '{$label}' updated successfully.");
        View::redirect(View::url('sessions'));
    }

    public function activate(): void
    {
        if (!Auth::isAdmin()) {
            View::flash('error', 'Unauthorized.');
            View::redirect(View::url('sessions'));
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            AcademicSession::setActive($id);
            $_SESSION['selected_session_id'] = $id;
            View::flash('success', "Active academic session changed. Switching active sessions never changes historical lead sessions.");
        }

        View::redirect(View::url('sessions'));
    }

    public function archive(): void
    {
        if (!Auth::isAdmin()) {
            View::flash('error', 'Unauthorized.');
            View::redirect(View::url('sessions'));
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id > 0) {
            if (AcademicSession::archive($id)) {
                View::flash('success', 'Academic session archived successfully.');
            } else {
                View::flash('error', 'Cannot archive the currently active admission session.');
            }
        }

        View::redirect(View::url('sessions'));
    }
}
