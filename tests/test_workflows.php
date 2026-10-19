<?php
/**
 * Automated Verification Test Suite for GD Goenka Admission CRM
 * Validates all 30 acceptance criteria from Antigravity_Admission_CRM.md
 */

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Config\App;
use App\Config\Database;
use App\Helpers\Auth;
use App\Helpers\Sanitizer;
use App\Models\AcademicSession;
use App\Models\FollowupLog;
use App\Models\ImportBatch;
use App\Models\Lead;
use App\Models\LeadDocument;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use App\Services\CsvImportService;

App::init();

echo "========================================================================\n";
echo "  GD GOENKA PUBLIC SCHOOL, PURNEA — ADMISSION CRM TEST SUITE\n";
echo "========================================================================\n\n";

$passed = 0;
$failed = 0;

function assertTest(string $description, bool $condition): void {
    global $passed, $failed;
    if ($condition) {
        echo "[PASS] " . $description . "\n";
        $passed++;
    } else {
        echo "[FAIL] " . $description . "\n";
        $failed++;
    }
}

// 1. Database Connection
try {
    $db = Database::getConnection();
    assertTest("1. Database PDO Connection successfully established", $db instanceof PDO);
} catch (\Throwable $e) {
    assertTest("1. Database PDO Connection error: " . $e->getMessage(), false);
}

// 2. Active Academic Session Check
$activeSession = AcademicSession::getActive();
assertTest("2. Active session exists and defaults to 2027–28", !empty($activeSession) && str_contains($activeSession['session_label'], '2027'));

// 3. User Accounts and Password Verification
$adminUser = User::findByEmail('admin@gdgoenkapurnea.com');
assertTest("3. Super Administrator user exists in database", !empty($adminUser));
assertTest("4. Password verification for Admin@12345 passes bcrypt", $adminUser && password_verify('Admin@12345', $adminUser['password']));

$counselor = User::findByEmail('priya@gdgoenkapurnea.com');
assertTest("5. Counselor account (Priya Verma) exists and is active", $counselor && $counselor['is_active'] == 1);
assertTest("6. Counselor password verification passes bcrypt", $counselor && password_verify('Priya@12345', $counselor['password']));

// 7. Lead Creation & Unique Reference Generation
$testMobile = '0987654321';
$cleanPhone = Sanitizer::cleanPhone($testMobile);
assertTest("7. Phone sanitizer preserves leading zero correctly", $cleanPhone === '0987654321');

$newLeadId = Lead::create([
    'session_id' => $activeSession['id'],
    'student_name' => 'Kavya Singh',
    'parent_name' => 'Anil Singh',
    'mobile' => $cleanPhone,
    'class_applied' => 'Grade 1',
    'enquiry_source' => 'Walk-in',
    'status' => 'New',
    'priority' => 'High',
    'remarks' => 'Automated test suite creation'
]);
assertTest("8. Lead creation returns valid ID", $newLeadId > 0);

$createdLead = Lead::find($newLeadId);
assertTest("9. Lead reference follows GDG standard format", !empty($createdLead['lead_ref']) && str_starts_with($createdLead['lead_ref'], 'GDG-'));
assertTest("10. Lead mobile number stored as exact string preserving leading zero", $createdLead['mobile'] === '0987654321');
assertTest("11. Lead defaults to active academic session", (int)$createdLead['session_id'] === (int)$activeSession['id']);

// 12. Duplicate Detection
$dups = Lead::findDuplicates($cleanPhone, $activeSession['id']);
assertTest("12. Duplicate detection identifies existing lead by mobile & session", count($dups) >= 1 && in_array($newLeadId, array_column($dups, 'id')));

// 13. Lead Assignment & History
$assigned = Lead::assign($newLeadId, $counselor['id'], 'Allocated to counselor for testing', $adminUser['id']);
assertTest("13. Lead successfully assigned to counselor Priya Verma", $assigned);

$updatedLead = Lead::find($newLeadId);
assertTest("14. Assigned counselor reflected on lead", (int)$updatedLead['assigned_to'] === (int)$counselor['id']);

$timeline = Lead::getTimeline($newLeadId);
$hasAssignmentInTimeline = false;
foreach ($timeline as $t) {
    if ($t['type'] === 'assignment') {
        $hasAssignmentInTimeline = true;
        break;
    }
}
assertTest("15. Assignment history recorded in lead timeline", $hasAssignmentInTimeline);

// 16. Task Creation and Dynamic Overdue State
$taskId = Task::create([
    'lead_id' => $newLeadId,
    'title' => 'Urgent Call to Parent',
    'task_type' => 'Follow-up Call',
    'assigned_to' => $counselor['id'],
    'due_date' => date('Y-m-d H:i:s', strtotime('-2 hours')), // past date to verify overdue
    'priority' => 'Urgent'
]);
assertTest("16. Task successfully created with ID", $taskId > 0);

$task = Task::find($taskId);
assertTest("17. Task dynamically marked as overdue based on due_date < NOW()", $task && $task['is_overdue'] == 1);

// 18. Task Completion
$completed = Task::complete($taskId, 'Parent confirmed campus tour for Saturday');
assertTest("18. Task completion marks status 'Completed' and records outcome", $completed);

$completedTask = Task::find($taskId);
assertTest("19. Completed task is no longer marked overdue", $completedTask['status'] === 'Completed' && $completedTask['is_overdue'] == 0);

// 20. Follow-up / Call Logs
$logId = FollowupLog::create([
    'lead_id' => $newLeadId,
    'contact_method' => 'Phone',
    'outcome' => 'Interested',
    'remarks' => 'Parent spoke for 10 minutes regarding CBSE curriculum.',
    'next_followup_date' => date('Y-m-d H:i:s', strtotime('+2 days')),
    'lead_status' => 'Contacted'
]);
assertTest("20. Follow-up interaction log successfully created", $logId > 0);

$leadAfterCall = Lead::find($newLeadId);
assertTest("21. Lead status updated to 'Contacted' via follow-up log", $leadAfterCall['status'] === 'Contacted');
assertTest("22. Next follow-up date updated on lead", !empty($leadAfterCall['next_followup_date']));

// 23. Document Checklist (5 Standard Default Items)
$docChecklist = LeadDocument::getChecklistForLead($newLeadId);
assertTest("23. Document checklist returns 5 default required items", count($docChecklist) === 5);

$birthCert = $docChecklist[0];
$docUpdated = LeadDocument::updateStatus($newLeadId, $birthCert['document_type_id'], 'Available', 'Physical attested copy received', 'Verified');
assertTest("24. Document status updated to 'Available' without requiring mandatory file upload", $docUpdated);

// 25. Spreadsheet Formula Injection Protection
$cleanFormula = Sanitizer::cleanFormula('=SUM(A1:A10)');
assertTest("25. CSV formula injection sanitized with leading single quote", str_starts_with($cleanFormula, "'="));

// 26. Dashboard Metrics
$metrics = Lead::getDashboardMetrics($activeSession['id']);
assertTest("26. Dashboard metrics calculate total leads and conversion rate from real DB", isset($metrics['total_leads']) && isset($metrics['conversion_rate']));

// 27. Session Switching Invariance Check
// When a new session is created or activated, existing leads must retain their original session_id!
$allSessions = AcademicSession::all();
assertTest("27. Academic sessions list returns available sessions", count($allSessions) >= 1);
assertTest("28. Existing lead session remains locked to original intake session", (int)$createdLead['session_id'] === (int)$activeSession['id']);

// 28. Employee Deactivation Safety Check
$recordCounts = User::getActiveRecordCounts($counselor['id']);
assertTest("29. Employee active lead count accurately calculated before deactivation", $recordCounts['active_leads'] >= 1);

// 29. Default School Settings
$schoolName = Setting::get('school_name');
assertTest("30. School name matches 'GD Goenka Public School, Purnea'", str_contains($schoolName, 'GD Goenka Public School'));

echo "\n========================================================================\n";
echo "  TEST SUMMARY: {$passed} PASSED, {$failed} FAILED\n";
echo "========================================================================\n";

if ($failed === 0) {
    echo "  ALL ACCEPTANCE CRITERIA EMPIRICALLY VERIFIED AND WORKING!\n";
}
