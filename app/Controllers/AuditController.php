<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\Auth;
use App\Helpers\View;

class AuditController
{
    public function index(): void
    {
        if (!Auth::isSuperAdmin()) {
            View::flash('error', 'Audit logs are restricted to Super Administrators.');
            View::redirect(View::url('dashboard'));
        }

        $db = Database::getConnection();

        $page = (int)($_GET['page'] ?? 1);
        $perPage = 25;
        $offset = ($page - 1) * $perPage;

        $totalRecords = (int)$db->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn();
        $totalPages = max(1, (int)ceil($totalRecords / $perPage));

        $stmt = $db->prepare("SELECT al.*, u.name as user_name, u.email as user_email
                              FROM audit_logs al
                              LEFT JOIN users u ON al.user_id = u.id
                              ORDER BY al.created_at DESC
                              LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute();
        $logs = $stmt->fetchAll();

        View::render('audit/index', [
            'logs' => $logs,
            'page' => $page,
            'totalPages' => $totalPages,
            'totalRecords' => $totalRecords,
        ]);
    }
}
