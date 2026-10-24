<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\View;
use App\Models\Lead;
use App\Models\User;

class UserController
{
    public function index(): void
    {
        if (!Auth::isSuperAdmin()) {
            View::flash('error', 'Only administrators can manage users & staff.');
            View::redirect(View::url('dashboard'));
        }

        $users = User::all();
        $db = Database::getConnection();
        $roles = $db->query("SELECT * FROM roles ORDER BY id ASC")->fetchAll();

        // Calculate active records for each user in 2 fast bulk queries
        $leadsMap = $db->query("SELECT assigned_to, COUNT(*) as cnt FROM leads WHERE is_archived = 0 AND status NOT IN ('Admission Confirmed', 'Lost / Unreachable') AND assigned_to IS NOT NULL GROUP BY assigned_to")->fetchAll(\PDO::FETCH_KEY_PAIR);
        $tasksMap = $db->query("SELECT t.assigned_to, COUNT(*) as cnt FROM tasks t JOIN leads l ON t.lead_id = l.id WHERE l.is_archived = 0 AND t.status IN ('Pending', 'In Progress') GROUP BY t.assigned_to")->fetchAll(\PDO::FETCH_KEY_PAIR);

        $userStats = [];
        foreach ($users as $u) {
            $uid = (int)$u['id'];
            $userStats[$uid] = [
                'active_leads' => (int)($leadsMap[$uid] ?? 0),
                'pending_tasks' => (int)($tasksMap[$uid] ?? 0),
            ];
        }

        View::render('users/index', [
            'users' => $users,
            'roles' => $roles,
            'userStats' => $userStats,
        ]);
    }

    public function store(): void
    {
        if (!Auth::isSuperAdmin()) {
            View::flash('error', 'Unauthorized.');
            View::redirect(View::url('users'));
        }

        if (!Csrf::validate($_POST['_csrf_token'] ?? null)) {
            View::flash('error', 'Security token invalid.');
            View::redirect(View::url('users'));
        }

        $name = trim($_POST['name'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));
        $password = $_POST['password'] ?? '';
        $roleId = (int)($_POST['role_id'] ?? 3);

        if (empty($name) || empty($email) || empty($password)) {
            View::flash('error', 'Full name, email address, and initial password are required.');
            View::redirect(View::url('users'));
        }

        if (User::findByEmail($email)) {
            View::flash('error', "An account with email '{$email}' already exists.");
            View::redirect(View::url('users'));
        }

        User::create([
            'role_id' => $roleId,
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'phone' => $_POST['phone'] ?? null,
            'designation' => $_POST['designation'] ?? 'Admission Counselor',
            'is_active' => !empty($_POST['is_active']) ? 1 : 0,
            'created_by' => Auth::id()
        ]);

        View::flash('success', "Staff account for '{$name}' created successfully.");
        View::redirect(View::url('users'));
    }

    public function update(): void
    {
        if (!Auth::isSuperAdmin()) {
            View::flash('error', 'Unauthorized.');
            View::redirect(View::url('users'));
        }

        if (!Csrf::validate($_POST['_csrf_token'] ?? null)) {
            View::flash('error', 'Security token invalid.');
            View::redirect(View::url('users'));
        }

        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $email = trim(strtolower($_POST['email'] ?? ''));

        if ($id <= 0 || empty($name) || empty($email)) {
            View::flash('error', 'Invalid user details.');
            View::redirect(View::url('users'));
        }

        // Check if email taken by someone else
        $existing = User::findByEmail($email);
        if ($existing && (int)$existing['id'] !== $id) {
            View::flash('error', "The email '{$email}' is already in use by another user.");
            View::redirect(View::url('users'));
        }

        User::update($id, [
            'role_id' => (int)$_POST['role_id'],
            'name' => $name,
            'email' => $email,
            'password' => $_POST['password'] ?? null,
            'phone' => $_POST['phone'] ?? null,
            'designation' => $_POST['designation'] ?? null,
            'is_active' => !empty($_POST['is_active']) ? 1 : 0,
        ]);

        View::flash('success', "User details for '{$name}' updated successfully.");
        View::redirect(View::url('users'));
    }

    public function toggleStatus(): void
    {
        if (!Auth::isSuperAdmin()) {
            View::flash('error', 'Unauthorized.');
            View::redirect(View::url('users'));
        }

        $id = (int)($_GET['id'] ?? 0);
        $user = User::find($id);

        if (!$user) {
            View::flash('error', 'User not found.');
            View::redirect(View::url('users'));
        }

        if ((int)$user['id'] === Auth::id()) {
            View::flash('error', 'You cannot deactivate your own administrative account.');
            View::redirect(View::url('users'));
        }

        // If deactivating, check active leads and pending tasks!
        if ($user['is_active']) {
            $stats = User::getActiveRecordCounts($id);
            if ($stats['active_leads'] > 0 || $stats['pending_tasks'] > 0) {
                // If not explicitly confirmed with reassign_to
                $reassignTo = !empty($_GET['reassign_to']) ? (int)$_GET['reassign_to'] : 0;
                if ($reassignTo <= 0) {
                    View::flash('warning', "Warning: {$user['name']} has {$stats['active_leads']} active lead(s) and {$stats['pending_tasks']} pending task(s). Please reassign them before deactivation.");
                    View::redirect(View::url('users'));
                } else {
                    // Reassign all active leads
                    $db = Database::getConnection();
                    $db->prepare("UPDATE leads SET assigned_to = ? WHERE assigned_to = ? AND is_archived = 0")->execute([$reassignTo, $id]);
                    $db->prepare("UPDATE tasks SET assigned_to = ? WHERE assigned_to = ? AND status IN ('Pending', 'In Progress')")->execute([$reassignTo, $id]);
                }
            }

            User::update($id, [
                'role_id' => $user['role_id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'designation' => $user['designation'],
                'is_active' => 0
            ]);
            View::flash('info', "Account for '{$user['name']}' has been deactivated. Historical records have been preserved.");
        } else {
            User::update($id, [
                'role_id' => $user['role_id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'phone' => $user['phone'],
                'designation' => $user['designation'],
                'is_active' => 1
            ]);
            View::flash('success', "Account for '{$user['name']}' has been reactivated.");
        }

        View::redirect(View::url('users'));
    }
}
