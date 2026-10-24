<?php
require_once __DIR__ . '/../app/autoload.php';
App\Config\App::init();
$db = App\Config\Database::getConnection();

echo "=== REAL ACTIVE LEADS (is_archived = 0) ===\n";
$leads = $db->query('SELECT id, lead_ref, student_name, parent_name, mobile, status, assigned_to FROM leads WHERE is_archived = 0')->fetchAll(PDO::FETCH_ASSOC);
foreach ($leads as $l) {
    echo "Lead #{$l['id']} - {$l['lead_ref']} - {$l['student_name']} (Parent: {$l['parent_name']}, Mobile: {$l['mobile']}, Status: {$l['status']}, Assigned ID: {$l['assigned_to']})\n";
}

echo "\n=== FALSE / ORPHANED TASKS (belonging to archived leads or missing leads) ===\n";
$falseTasks = $db->query('SELECT t.id, t.lead_id, t.title, l.student_name, l.is_archived 
                          FROM tasks t 
                          LEFT JOIN leads l ON t.lead_id = l.id 
                          WHERE l.id IS NULL OR l.is_archived = 1')->fetchAll(PDO::FETCH_ASSOC);
echo "Count: " . count($falseTasks) . "\n";
foreach ($falseTasks as $ft) {
    echo "Task ID {$ft['id']} for lead #{$ft['lead_id']} ({$ft['student_name']}, archived={$ft['is_archived']}): {$ft['title']}\n";
}

echo "\n=== FALSE / ORPHANED CALL LOGS ===\n";
$falseCalls = $db->query('SELECT f.id, f.lead_id, f.outcome, f.remarks, l.student_name, l.is_archived 
                          FROM follow_up_logs f 
                          LEFT JOIN leads l ON f.lead_id = l.id 
                          WHERE l.id IS NULL OR l.is_archived = 1')->fetchAll(PDO::FETCH_ASSOC);
echo "Count: " . count($falseCalls) . "\n";
foreach ($falseCalls as $fc) {
    echo "Call Log ID {$fc['id']} for lead #{$fc['lead_id']} ({$fc['student_name']}, archived={$fc['is_archived']}): {$fc['outcome']} - {$fc['remarks']}\n";
}

echo "\n=== REAL TASKS (for active leads) ===\n";
$realTasks = $db->query('SELECT t.id, t.lead_id, t.title, t.task_type, t.due_date, t.status, l.student_name, l.lead_ref 
                         FROM tasks t 
                         JOIN leads l ON t.lead_id = l.id 
                         WHERE l.is_archived = 0')->fetchAll(PDO::FETCH_ASSOC);
echo "Count: " . count($realTasks) . "\n";
foreach ($realTasks as $rt) {
    echo "Task ID {$rt['id']} - Lead #{$rt['lead_id']} ({$rt['student_name']}, {$rt['lead_ref']}): {$rt['title']} (Due: {$rt['due_date']}, Status: {$rt['status']})\n";
}

echo "\n=== REAL CALL LOGS (for active leads) ===\n";
$realCalls = $db->query('SELECT f.id, f.lead_id, f.outcome, f.remarks, f.created_at, l.student_name, l.lead_ref 
                         FROM follow_up_logs f 
                         JOIN leads l ON f.lead_id = l.id 
                         WHERE l.is_archived = 0')->fetchAll(PDO::FETCH_ASSOC);
echo "Count: " . count($realCalls) . "\n";
foreach ($realCalls as $rc) {
    echo "Call ID {$rc['id']} - Lead #{$rc['lead_id']} ({$rc['student_name']}, {$rc['lead_ref']}): {$rc['outcome']} - {$rc['remarks']} (Date: {$rc['created_at']})\n";
}
