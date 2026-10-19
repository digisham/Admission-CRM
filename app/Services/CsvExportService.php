<?php
namespace App\Services;

use App\Helpers\Sanitizer;

class CsvExportService
{
    public static function streamTemplate(): void
    {
        $filename = "gd_goenka_leads_import_template.csv";

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        // Add UTF-8 BOM
        fputs($output, "\xEF\xBB\xBF");

        // Headers
        $headers = [
            'Student Name',
            'Parent Name',
            'Mobile Number',
            'Alternate Mobile',
            'Email',
            'Class Applied',
            'Enquiry Date',
            'Enquiry Source',
            'Lead Status',
            'Priority',
            'Academic Session',
            'Assigned Employee',
            'Address',
            'City',
            'Remarks'
        ];
        fputcsv($output, $headers);

        // Sample rows for GD Goenka Public School
        $sampleRows = [
            [
                'Aayush Singhania',
                'Vikram Singhania',
                '9812345678',
                '9812345679',
                'vikram@example.com',
                'Grade 1',
                date('Y-m-d'),
                'Walk-in',
                'New',
                'High',
                '2027–28',
                'Priya Verma',
                'Line Bazar',
                'Purnea',
                'Parent seeking admission for session 2027-28.'
            ],
            [
                'Kavya Agrawal',
                'Rajesh Agrawal',
                '9876543210',
                '',
                'rajesh@example.com',
                'Nursery',
                date('Y-m-d'),
                'Website',
                'Contacted',
                'Medium',
                '2027–28',
                'Rahul Sharma',
                'Bhatta Bazar',
                'Purnea',
                'Online enquiry regarding smart class & day boarding.'
            ]
        ];

        foreach ($sampleRows as $row) {
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    public static function exportLeads(array $leads, string $filename = 'gd_goenka_leads_export.csv'): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF");

        $headers = [
            'Lead Reference',
            'Academic Session',
            'Student Name',
            'Parent Name',
            'Mobile',
            'Alternate Mobile',
            'Email',
            'Class Applied',
            'Enquiry Date',
            'Source',
            'Status',
            'Priority',
            'Assigned Counselor',
            'Next Follow-up',
            'City',
            'Remarks',
            'Created At'
        ];
        fputcsv($output, $headers);

        foreach ($leads as $lead) {
            $row = [
                Sanitizer::cleanFormula($lead['lead_ref'] ?? ''),
                Sanitizer::cleanFormula($lead['session_label'] ?? ''),
                Sanitizer::cleanFormula($lead['student_name'] ?? ''),
                Sanitizer::cleanFormula($lead['parent_name'] ?? ''),
                Sanitizer::cleanFormula($lead['mobile'] ?? ''),
                Sanitizer::cleanFormula($lead['alternate_mobile'] ?? ''),
                Sanitizer::cleanFormula($lead['email'] ?? ''),
                Sanitizer::cleanFormula($lead['class_applied'] ?? ''),
                $lead['enquiry_date'] ?? '',
                Sanitizer::cleanFormula($lead['enquiry_source'] ?? ''),
                $lead['status'] ?? '',
                $lead['priority'] ?? '',
                Sanitizer::cleanFormula($lead['assigned_name'] ?? 'Unassigned'),
                $lead['next_followup_date'] ?? '',
                Sanitizer::cleanFormula($lead['city'] ?? ''),
                Sanitizer::cleanFormula($lead['remarks'] ?? ''),
                $lead['created_at'] ?? '',
            ];
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }
}
