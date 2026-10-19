<?php
namespace App\Controllers;

use App\Config\Database;
use App\Helpers\Auth;
use App\Helpers\View;
use App\Models\AcademicSession;
use App\Models\Lead;
use App\Models\User;

class ReportController
{
    public function index(): void
    {
        if (!Auth::check()) {
            View::redirect(View::url('login'));
        }

        $activeSession = AcademicSession::getActive();
        $selectedSessionId = $_GET['session_id'] ?? ($_SESSION['selected_session_id'] ?? $activeSession['id']);
        $selectedSessionId = (int)$selectedSessionId;

        $db = Database::getConnection();

        // 1. Session overview metrics
        $metrics = Lead::getDashboardMetrics($selectedSessionId);

        // 2. Leads by status
        $statusStmt = $db->prepare("SELECT status, COUNT(*) as total FROM leads WHERE session_id = ? AND is_archived = 0 GROUP BY status ORDER BY total DESC");
        $statusStmt->execute([$selectedSessionId]);
        $statusReport = $statusStmt->fetchAll();

        // 3. Leads by Class
        $classStmt = $db->prepare("SELECT class_applied, COUNT(*) as total, 
                                          COUNT(CASE WHEN status = 'Admission Confirmed' THEN 1 END) as confirmed
                                   FROM leads 
                                   WHERE session_id = ? AND is_archived = 0 
                                   GROUP BY class_applied 
                                   ORDER BY total DESC");
        $classStmt->execute([$selectedSessionId]);
        $classReport = $classStmt->fetchAll();

        // 4. Leads by Source
        $sourceStmt = $db->prepare("SELECT enquiry_source, COUNT(*) as total,
                                           COUNT(CASE WHEN status = 'Admission Confirmed' THEN 1 END) as confirmed
                                    FROM leads 
                                    WHERE session_id = ? AND is_archived = 0 
                                    GROUP BY enquiry_source 
                                    ORDER BY total DESC");
        $sourceStmt->execute([$selectedSessionId]);
        $sourceReport = $sourceStmt->fetchAll();

        // 5. Counselor Activity Report
        $workload = User::getWorkload($selectedSessionId);

        // 6. Document Checklist Completion Report (authorized roles)
        $docReport = [];
        if (Auth::isAdmin()) {
            $docStmt = $db->prepare("SELECT dt.name as doc_name,
                                            COUNT(CASE WHEN ld.status = 'Available' THEN 1 END) as available_count,
                                            COUNT(CASE WHEN ld.status = 'Pending' THEN 1 END) as pending_count,
                                            COUNT(CASE WHEN ld.status = 'Not Applicable' THEN 1 END) as na_count,
                                            COUNT(CASE WHEN ld.verification_status = 'Verified' THEN 1 END) as verified_count
                                     FROM document_types dt
                                     LEFT JOIN lead_documents ld ON ld.document_type_id = dt.id
                                     LEFT JOIN leads l ON ld.lead_id = l.id AND l.session_id = ? AND l.is_archived = 0
                                     GROUP BY dt.id
                                     ORDER BY dt.sort_order ASC");
            $docStmt->execute([$selectedSessionId]);
            $docReport = $docStmt->fetchAll();
        }

        View::render('reports/index', [
            'selectedSessionId' => $selectedSessionId,
            'metrics' => $metrics,
            'statusReport' => $statusReport,
            'classReport' => $classReport,
            'sourceReport' => $sourceReport,
            'workload' => $workload,
            'docReport' => $docReport,
        ]);
    }
}
