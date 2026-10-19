<?php
namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Logger;
use App\Helpers\View;
use App\Models\AcademicSession;
use App\Models\Lead;
use App\Services\CsvExportService;

class ExportController
{
    public function exportLeads(): void
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
        ];

        // Retrieve all records matching filters (no pagination limit, up to 10000)
        $paginated = Lead::paginate($filters, 1, 10000);
        $leads = $paginated['data'];

        Logger::audit('leads_export', 'lead', null, null, ['count' => count($leads), 'filters' => $filters]);

        $filename = "gd_goenka_leads_" . date('Ymd_His') . ".csv";
        CsvExportService::exportLeads($leads, $filename);
    }
}
