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
            'session_id' => $_REQUEST['session_id'] ?? $selectedSessionId,
            'status' => $_REQUEST['status'] ?? null,
            'class_applied' => $_REQUEST['class_applied'] ?? null,
            'enquiry_source' => $_REQUEST['enquiry_source'] ?? null,
            'assigned_to' => $_REQUEST['assigned_to'] ?? null,
            'priority' => $_REQUEST['priority'] ?? null,
            'search' => $_REQUEST['search'] ?? null,
            'date_from' => $_REQUEST['date_from'] ?? null,
            'date_to' => $_REQUEST['date_to'] ?? null,
        ];

        // If user chose to export all leads in session rather than filtered
        if (($_REQUEST['export_scope'] ?? 'filtered') === 'all_session') {
            $filters = ['session_id' => $filters['session_id']];
        }

        $selectedColumns = $_REQUEST['columns'] ?? null;
        if (is_array($selectedColumns)) {
            $selectedColumns = array_values(array_filter(array_map('trim', $selectedColumns)));
        } else {
            $selectedColumns = null;
        }

        // Retrieve all records matching filters (no pagination limit, up to 10000)
        $paginated = Lead::paginate($filters, 1, 10000);
        $leads = $paginated['data'];

        Logger::audit('leads_export', 'lead', null, null, [
            'count' => count($leads),
            'filters' => $filters,
            'columns_count' => $selectedColumns ? count($selectedColumns) : 'all'
        ]);

        $format = strtolower($_REQUEST['export_format'] ?? $_REQUEST['format'] ?? 'excel');

        if ($format === 'csv') {
            $filename = "gd_goenka_enquiries_" . date('Ymd_His') . ".csv";
            CsvExportService::exportLeads($leads, $filename, $selectedColumns);
        } else {
            $currentSession = AcademicSession::find((int)($filters['session_id'] ?? $selectedSessionId)) ?: $activeSession;
            $filename = "gd_goenka_enquiries_" . date('Ymd_His') . ".xls";
            CsvExportService::exportLeadsExcel($leads, $filename, $selectedColumns, [
                'session_label' => $currentSession['session_label'] ?? 'Active Session'
            ]);
        }
    }
}
