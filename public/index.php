<?php
/**
 * GD Goenka Public School, Purnea
 * School Admission CRM & Lead Management System
 * Front Controller (Core PHP 8.2+)
 */

// Error reporting settings based on environment
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Autoload application classes
require_once dirname(__DIR__) . '/app/autoload.php';

use App\Config\App;
use App\Helpers\Auth;
use App\Helpers\View;

App::init();
Auth::startSession();

// Determine requested route
$route = $_GET['route'] ?? '';
$route = trim($route, '/');

// Default route
if (empty($route)) {
    $route = Auth::check() ? 'dashboard' : 'login';
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    switch ($route) {
        // --- AUTHENTICATION ---
        case 'login':
            $controller = new \App\Controllers\AuthController();
            $method === 'POST' ? $controller->login() : $controller->showLogin();
            break;

        case 'logout':
            (new \App\Controllers\AuthController())->logout();
            break;

        case 'forgot-password':
            $controller = new \App\Controllers\AuthController();
            $method === 'POST' ? $controller->forgotPassword() : $controller->showForgotPassword();
            break;

        // --- DASHBOARD ---
        case 'dashboard':
            (new \App\Controllers\DashboardController())->index();
            break;

        case 'dashboard/switch-session':
            (new \App\Controllers\DashboardController())->switchSession();
            break;

        // --- LEADS MANAGEMENT ---
        case 'leads':
            (new \App\Controllers\LeadController())->index();
            break;

        case 'leads/unassigned':
            (new \App\Controllers\LeadController())->unassigned();
            break;

        case 'leads/create':
            (new \App\Controllers\LeadController())->create();
            break;

        case 'leads/store':
            (new \App\Controllers\LeadController())->store();
            break;

        case 'leads/show':
            (new \App\Controllers\LeadController())->show();
            break;

        case 'leads/edit':
            (new \App\Controllers\LeadController())->edit();
            break;

        case 'leads/update':
            (new \App\Controllers\LeadController())->update();
            break;

        case 'leads/assign':
            (new \App\Controllers\LeadController())->assign();
            break;

        case 'leads/bulk-assign':
            (new \App\Controllers\LeadController())->bulkAssign();
            break;

        case 'leads/check-duplicate':
            (new \App\Controllers\LeadController())->checkDuplicate();
            break;

        // --- TASKS ---
        case 'tasks':
            (new \App\Controllers\TaskController())->index();
            break;

        case 'tasks/store':
            (new \App\Controllers\TaskController())->store();
            break;

        case 'tasks/complete':
            (new \App\Controllers\TaskController())->complete();
            break;

        // --- FOLLOW-UPS ---
        case 'followups':
            (new \App\Controllers\FollowupController())->index();
            break;

        case 'followups/store':
            (new \App\Controllers\FollowupController())->store();
            break;

        // --- DOCUMENTS ---
        case 'documents':
            (new \App\Controllers\DocumentController())->index();
            break;

        case 'documents/update-status':
            (new \App\Controllers\DocumentController())->updateStatus();
            break;

        case 'documents/upload':
            (new \App\Controllers\DocumentController())->upload();
            break;

        case 'documents/download':
            (new \App\Controllers\DocumentController())->download();
            break;

        // --- SESSIONS ---
        case 'sessions':
            (new \App\Controllers\SessionController())->index();
            break;

        case 'sessions/store':
            (new \App\Controllers\SessionController())->store();
            break;

        case 'sessions/update':
            (new \App\Controllers\SessionController())->update();
            break;

        case 'sessions/activate':
            (new \App\Controllers\SessionController())->activate();
            break;

        case 'sessions/archive':
            (new \App\Controllers\SessionController())->archive();
            break;

        // --- USERS & COUNSELORS ---
        case 'users':
            (new \App\Controllers\UserController())->index();
            break;

        case 'users/store':
            (new \App\Controllers\UserController())->store();
            break;

        case 'users/update':
            (new \App\Controllers\UserController())->update();
            break;

        case 'users/toggle-status':
            (new \App\Controllers\UserController())->toggleStatus();
            break;

        // --- REPORTS ---
        case 'reports':
            (new \App\Controllers\ReportController())->index();
            break;

        // --- SETTINGS ---
        case 'settings':
            $controller = new \App\Controllers\SettingsController();
            $method === 'POST' ? $controller->update() : $controller->index();
            break;

        // --- AUDIT LOGS ---
        case 'audit':
            (new \App\Controllers\AuditController())->index();
            break;

        // --- SPREADSHEET IMPORTS ---
        case 'import':
            (new \App\Controllers\ImportController())->index();
            break;

        case 'import/preview':
            (new \App\Controllers\ImportController())->preview();
            break;

        case 'import/commit':
            (new \App\Controllers\ImportController())->commit();
            break;

        case 'import/history':
            (new \App\Controllers\ImportController())->history();
            break;

        case 'import/template':
            (new \App\Controllers\ImportController())->template();
            break;

        // --- EXPORTS ---
        case 'export/leads':
            (new \App\Controllers\ExportController())->exportLeads();
            break;

        // 404 Not Found
        default:
            http_response_code(404);
            View::render('errors/404', ['requestedRoute' => $route]);
            break;
    }
} catch (\Throwable $e) {
    if (App::isDebug()) {
        echo "<div style='font-family: monospace; padding: 20px; background: #fff0f0; border: 1px solid #ffcccc; margin: 20px;'>";
        echo "<h3 style='color: #c00;'>Application Runtime Exception</h3>";
        echo "<p><strong>Message:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . " on line " . $e->getLine() . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        echo "</div>";
    } else {
        http_response_code(500);
        View::render('errors/500', []);
    }
}
