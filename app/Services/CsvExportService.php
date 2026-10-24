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

    public static function getExportableColumns(): array
    {
        return [
            'lead_ref' => [
                'label' => 'Lead Reference',
                'desc' => 'Enquiry code (GDG-XXXX)',
                'default' => true,
                'icon' => 'hash'
            ],
            'student_name' => [
                'label' => 'Student Name',
                'desc' => 'Full name of applicant',
                'default' => true,
                'icon' => 'user'
            ],
            'parent_name' => [
                'label' => 'Parent Name',
                'desc' => 'Father / Mother / Guardian',
                'default' => true,
                'icon' => 'users'
            ],
            'mobile' => [
                'label' => 'Mobile Number',
                'desc' => 'Primary contact number',
                'default' => true,
                'icon' => 'phone'
            ],
            'alternate_mobile' => [
                'label' => 'Alternate Mobile',
                'desc' => 'Secondary contact number',
                'default' => false,
                'icon' => 'phone-call'
            ],
            'email' => [
                'label' => 'Email Address',
                'desc' => 'Email contact',
                'default' => true,
                'icon' => 'mail'
            ],
            'session_label' => [
                'label' => 'Academic Session',
                'desc' => 'Admission intake year',
                'default' => true,
                'icon' => 'calendar'
            ],
            'class_applied' => [
                'label' => 'Class Applied',
                'desc' => 'Grade or standard applied',
                'default' => true,
                'icon' => 'graduation-cap'
            ],
            'status' => [
                'label' => 'Admission Status',
                'desc' => 'Current pipeline stage',
                'default' => true,
                'icon' => 'tag'
            ],
            'priority' => [
                'label' => 'Priority',
                'desc' => 'Urgent / High / Med / Low',
                'default' => true,
                'icon' => 'flag'
            ],
            'enquiry_source' => [
                'label' => 'Lead Source',
                'desc' => 'Walk-in, Website, Referral...',
                'default' => true,
                'icon' => 'share-2'
            ],
            'assigned_name' => [
                'label' => 'Assigned Counselor',
                'desc' => 'Counselor handling enquiry',
                'default' => true,
                'icon' => 'user-check'
            ],
            'next_followup_date' => [
                'label' => 'Next Follow-up',
                'desc' => 'Scheduled next date',
                'default' => true,
                'icon' => 'clock'
            ],
            'enquiry_date' => [
                'label' => 'Enquiry Date',
                'desc' => 'Initial inquiry date',
                'default' => false,
                'icon' => 'calendar-days'
            ],
            'city' => [
                'label' => 'City / Address',
                'desc' => 'Location / town',
                'default' => false,
                'icon' => 'map-pin'
            ],
            'remarks' => [
                'label' => 'Remarks / Notes',
                'desc' => 'Counselor internal notes',
                'default' => false,
                'icon' => 'file-text'
            ],
            'created_at' => [
                'label' => 'Registration Date',
                'desc' => 'Timestamp created in CRM',
                'default' => true,
                'icon' => 'calendar-check'
            ],
        ];
    }

    public static function exportLeads(array $leads, string $filename = 'gd_goenka_leads_export.csv', ?array $selectedColumns = null): void
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fputs($output, "\xEF\xBB\xBF");

        $allColumns = self::getExportableColumns();

        // If specific columns requested, filter to those; otherwise use all
        if (!empty($selectedColumns) && is_array($selectedColumns)) {
            $activeColumns = [];
            foreach ($selectedColumns as $colKey) {
                if (isset($allColumns[$colKey])) {
                    $activeColumns[$colKey] = $allColumns[$colKey];
                }
            }
            if (empty($activeColumns)) {
                $activeColumns = $allColumns;
            }
        } else {
            $activeColumns = $allColumns;
        }

        // Output Header Row
        $headers = [];
        foreach ($activeColumns as $key => $meta) {
            $headers[] = $meta['label'];
        }
        fputcsv($output, $headers);

        // Output Data Rows
        foreach ($leads as $lead) {
            $row = [];
            foreach ($activeColumns as $key => $meta) {
                $val = '';
                switch ($key) {
                    case 'lead_ref':
                    case 'session_label':
                    case 'student_name':
                    case 'parent_name':
                    case 'mobile':
                    case 'alternate_mobile':
                    case 'email':
                    case 'class_applied':
                    case 'enquiry_source':
                    case 'city':
                    case 'remarks':
                        $val = Sanitizer::cleanFormula($lead[$key] ?? '');
                        break;
                    case 'assigned_name':
                        $val = Sanitizer::cleanFormula($lead['assigned_name'] ?? 'Unassigned');
                        break;
                    case 'status':
                    case 'priority':
                    case 'enquiry_date':
                    case 'next_followup_date':
                    case 'created_at':
                        $val = $lead[$key] ?? '';
                        break;
                    default:
                        $val = Sanitizer::cleanFormula($lead[$key] ?? '');
                }
                $row[] = $val;
            }
            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    public static function exportLeadsExcel(array $leads, string $filename = 'gd_goenka_leads_export.xls', ?array $selectedColumns = null, array $meta = []): void
    {
        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $allColumns = self::getExportableColumns();

        // If specific columns requested, filter to those; otherwise use all
        if (!empty($selectedColumns) && is_array($selectedColumns)) {
            $activeColumns = [];
            foreach ($selectedColumns as $colKey) {
                if (isset($allColumns[$colKey])) {
                    $activeColumns[$colKey] = $allColumns[$colKey];
                }
            }
            if (empty($activeColumns)) {
                $activeColumns = $allColumns;
            }
        } else {
            $activeColumns = $allColumns;
        }

        $colSpan = count($activeColumns);
        $schoolName = "GD GOENKA PUBLIC SCHOOL, PURNEA";
        $reportTitle = "ADMISSION ENQUIRIES & LEADS REPORT";
        $sessionLabel = $meta['session_label'] ?? 'All Sessions';
        $exportDate = date('d M Y, h:i A');
        $totalRecords = count($leads);

        echo '<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">' . "\n";
        echo '<head>' . "\n";
        echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">' . "\n";
        echo '<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Admission Enquiries</x:Name><x:WorksheetOptions><x:DisplayGridlines/></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->' . "\n";
        echo '<style>' . "\n";
        echo 'body { font-family: Calibri, Arial, sans-serif; font-size: 10pt; }' . "\n";
        echo 'table { border-collapse: collapse; table-layout: auto; width: 100%; }' . "\n";
        echo '.main-title { background-color: #0F172A; color: #FFFFFF; font-size: 14pt; font-weight: bold; text-align: left; padding: 12px 14px; border: 1.5pt solid #0F172A; }' . "\n";
        echo '.sub-meta { background-color: #F8FAFC; color: #475569; font-size: 9.5pt; padding: 7px 12px; border-left: 1pt solid #CBD5E1; border-right: 1pt solid #CBD5E1; border-bottom: 2pt solid #1E3A8A; }' . "\n";
        echo '.th-header { background-color: #1E3A8A; color: #FFFFFF; font-weight: bold; font-size: 10.5pt; text-align: center; vertical-align: middle; padding: 9px 12px; border: 1pt solid #475569; }' . "\n";
        echo '.td-data { font-size: 9.5pt; padding: 7px 10px; border: 0.5pt solid #CBD5E1; vertical-align: middle; }' . "\n";
        echo '.text-val { mso-number-format: "\@"; text-align: left; }' . "\n";
        echo '.center-val { text-align: center; }' . "\n";
        echo '.date-val { mso-number-format: "yyyy-mm-dd"; text-align: center; }' . "\n";
        echo '.row-even { background-color: #FFFFFF; }' . "\n";
        echo '.row-odd { background-color: #F8FAFC; }' . "\n";
        echo '.badge-confirmed { color: #166534; font-weight: bold; }' . "\n";
        echo '.badge-contacted { color: #1E40AF; font-weight: bold; }' . "\n";
        echo '.badge-urgent { color: #991B1B; font-weight: bold; }' . "\n";
        echo '</style>' . "\n";
        echo '</head>' . "\n";
        echo '<body>' . "\n";
        echo '<table border="1" cellpadding="0" cellspacing="0">' . "\n";

        // 1. School & Report Main Title Banner
        echo '<tr>' . "\n";
        echo '  <td colspan="' . $colSpan . '" class="main-title">' . htmlspecialchars($schoolName . ' — ' . $reportTitle, ENT_QUOTES, 'UTF-8') . '</td>' . "\n";
        echo '</tr>' . "\n";

        // 2. Metadata Information Row
        echo '<tr>' . "\n";
        echo '  <td colspan="' . $colSpan . '" class="sub-meta">' . "\n";
        echo '    Academic Session: <b>' . htmlspecialchars($sessionLabel, ENT_QUOTES, 'UTF-8') . '</b> &nbsp;|&nbsp; ' . "\n";
        echo '    Generated Date: <b>' . $exportDate . '</b> &nbsp;|&nbsp; ' . "\n";
        echo '    Total Enquiries: <b>' . $totalRecords . '</b>' . "\n";
        echo '  </td>' . "\n";
        echo '</tr>' . "\n";

        // Empty spacing row
        echo '<tr><td colspan="' . $colSpan . '" style="border: none; height: 8px; background-color: #FFFFFF;"></td></tr>' . "\n";

        // 3. Proper Formatted Bold Column Headings
        echo '<tr>' . "\n";
        foreach ($activeColumns as $key => $meta) {
            echo '  <th class="th-header" style="background-color: #1E3A8A; color: #FFFFFF; font-weight: bold; font-size: 10.5pt; text-align: center; border: 1pt solid #334155; padding: 9px 12px;">' . "\n";
            echo '    ' . htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8') . "\n";
            echo '  </th>' . "\n";
        }
        echo '</tr>' . "\n";

        // 4. Data Rows
        $rowIndex = 0;
        foreach ($leads as $lead) {
            $rowClass = ($rowIndex++ % 2 === 0) ? 'row-even' : 'row-odd';
            echo '<tr class="' . $rowClass . '">' . "\n";
            foreach ($activeColumns as $key => $meta) {
                $val = '';
                $cellClass = 'td-data';

                switch ($key) {
                    case 'lead_ref':
                        $val = $lead['lead_ref'] ?? '';
                        $cellClass .= ' text-val center-val';
                        break;
                    case 'mobile':
                    case 'alternate_mobile':
                        $val = $lead[$key] ?? '';
                        $cellClass .= ' text-val center-val'; // mso-number-format: "\@" ensures Excel keeps leading zeros
                        break;
                    case 'student_name':
                    case 'parent_name':
                    case 'city':
                    case 'remarks':
                        $val = $lead[$key] ?? '';
                        $cellClass .= ' text-val';
                        break;
                    case 'assigned_name':
                        $val = $lead['assigned_name'] ?? 'Unassigned';
                        $cellClass .= ' text-val';
                        break;
                    case 'session_label':
                    case 'class_applied':
                    case 'enquiry_source':
                        $val = $lead[$key] ?? '';
                        $cellClass .= ' text-val center-val';
                        break;
                    case 'status':
                        $val = $lead['status'] ?? '';
                        $cellClass .= ' center-val';
                        if ($val === 'Confirmed') $cellClass .= ' badge-confirmed';
                        elseif ($val === 'Contacted') $cellClass .= ' badge-contacted';
                        break;
                    case 'priority':
                        $val = $lead['priority'] ?? '';
                        $cellClass .= ' center-val';
                        if ($val === 'Urgent') $cellClass .= ' badge-urgent';
                        break;
                    case 'enquiry_date':
                    case 'next_followup_date':
                    case 'created_at':
                        $val = $lead[$key] ?? '';
                        $cellClass .= ' date-val';
                        break;
                    default:
                        $val = $lead[$key] ?? '';
                        $cellClass .= ' text-val';
                }

                $cleanVal = Sanitizer::cleanFormula((string)$val);
                echo '  <td class="' . $cellClass . '">' . htmlspecialchars($cleanVal, ENT_QUOTES, 'UTF-8') . '</td>' . "\n";
            }
            echo '</tr>' . "\n";
        }

        echo '</table>' . "\n";
        echo '</body>' . "\n";
        echo '</html>' . "\n";
        exit;
    }
}
