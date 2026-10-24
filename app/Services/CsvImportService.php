<?php
namespace App\Services;

use App\Config\Database;
use App\Helpers\Auth;
use App\Helpers\Sanitizer;
use App\Models\AcademicSession;
use App\Models\ImportBatch;
use App\Models\Lead;
use App\Models\Notification;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;

class CsvImportService
{
    public static function parsePreview(string $filePath, int $maxRows = 10): array
    {
        if (!file_exists($filePath) || !is_readable($filePath)) {
            return ['error' => 'Uploaded file could not be read.'];
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ['error' => 'Unable to open file.'];
        }

        $headers = [];
        $rows = [];
        $rowCount = 0;

        while (($data = fgetcsv($handle, 4096, ",")) !== false) {
            // Trim BOM if present in first column
            if ($rowCount === 0) {
                $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', $data[0]);
                $headers = array_map('trim', $data);
                $rowCount++;
                continue;
            }

            if (empty(array_filter($data))) {
                continue; // Skip empty rows
            }

            if (count($rows) < $maxRows) {
                $rowAssoc = [];
                foreach ($headers as $index => $header) {
                    $rowAssoc[$header] = isset($data[$index]) ? trim($data[$index]) : '';
                }
                $rows[] = $rowAssoc;
            }

            $rowCount++;
        }

        fclose($handle);

        return [
            'headers' => $headers,
            'preview_rows' => $rows,
            'total_rows' => max(0, $rowCount - 1),
        ];
    }

    public static function validateAndReview(string $filePath, array $mapping, int $sessionId): array
    {
        if (!file_exists($filePath)) {
            return ['error' => 'File not found.'];
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ['error' => 'Failed to open file.'];
        }

        $headers = fgetcsv($handle, 4096, ",");
        if (!$headers) {
            fclose($handle);
            return ['error' => 'Empty CSV file.'];
        }
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        $headers = array_map('trim', $headers);

        $validStatuses = Setting::getLeadStatuses();
        $counselors = User::getActiveCounselors();
        $counselorMap = [];
        foreach ($counselors as $c) {
            $counselorMap[strtolower($c['name'])] = $c['id'];
            $counselorMap[strtolower($c['email'])] = $c['id'];
        }

        $results = [
            'total' => 0,
            'valid' => 0,
            'duplicates' => 0,
            'errors' => 0,
            'rows' => [],
            'error_details' => []
        ];

        $rowNum = 1;
        while (($row = fgetcsv($handle, 4096, ",")) !== false) {
            $rowNum++;
            if (empty(array_filter($row))) {
                continue;
            }

            $results['total']++;

            // Map data
            $mappedData = [];
            foreach ($mapping as $targetField => $sourceHeader) {
                if (empty($sourceHeader)) continue;
                $idx = array_search($sourceHeader, $headers, true);
                $mappedData[$targetField] = ($idx !== false && isset($row[$idx])) ? trim($row[$idx]) : '';
            }

            $rowErrors = [];
            $rowWarnings = [];

            // 1. Student Name (Required)
            $studentName = trim($mappedData['student_name'] ?? '');
            if (empty($studentName)) {
                $rowErrors[] = "Student Name is required.";
            }

            // 2. Mobile validation & alternate contact fallback
            $rawMobile = $mappedData['mobile'] ?? '';
            $cleanMobile = Sanitizer::cleanPhone($rawMobile);
            $digits = preg_replace('/\D/', '', $cleanMobile);
            if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
                $digits = substr($digits, 1);
            } elseif (strlen($digits) === 12 && str_starts_with($digits, '91')) {
                $digits = substr($digits, 2);
            }

            if (empty($digits)) {
                $rawAlt = $mappedData['alternate_mobile'] ?? '';
                $altDigits = preg_replace('/\D/', '', Sanitizer::cleanPhone($rawAlt));
                if (strlen($altDigits) === 11 && str_starts_with($altDigits, '0')) {
                    $altDigits = substr($altDigits, 1);
                } elseif (strlen($altDigits) === 12 && str_starts_with($altDigits, '91')) {
                    $altDigits = substr($altDigits, 2);
                }
                if (!empty($altDigits) && strlen($altDigits) >= 10) {
                    $digits = $altDigits;
                    $cleanMobile = $altDigits;
                    $rowWarnings[] = "Mobile was empty; used alternate number.";
                } else {
                    $digits = 'N/A';
                    $cleanMobile = 'N/A';
                    $rowWarnings[] = "Mobile was empty; defaulted to 'N/A'.";
                }
            } elseif (strlen($digits) < 10) {
                $rowWarnings[] = "Mobile number has fewer than 10 digits; imported as provided.";
            }

            // 3. Graceful defaults for all optional columns if left empty
            if (empty($mappedData['parent_name'])) {
                $mappedData['parent_name'] = 'Parent / Guardian';
            }

            if (empty($mappedData['class_applied'])) {
                $mappedData['class_applied'] = 'Grade 1';
            }

            if (empty($mappedData['enquiry_source'])) {
                $mappedData['enquiry_source'] = 'Direct Walk-in';
            }

            $rawPriority = ucfirst(strtolower(trim($mappedData['priority'] ?? '')));
            $mappedData['priority'] = in_array($rawPriority, ['Low', 'Medium', 'High', 'Urgent']) ? $rawPriority : 'Medium';

            if (empty($mappedData['city'])) {
                $mappedData['city'] = 'Purnea';
            }

            // All imported records strictly show status 'New'
            $mappedData['status'] = 'New';

            // 4. Duplicate check
            $isDuplicate = false;
            if (!empty($digits) && $digits !== 'N/A' && strlen($digits) >= 10) {
                $dups = Lead::findDuplicates($digits, $sessionId);
                if (!empty($dups)) {
                    $isDuplicate = true;
                    $results['duplicates']++;
                    $rowWarnings[] = "Duplicate phone detected: matches existing lead {$dups[0]['lead_ref']} ({$dups[0]['student_name']}).";
                }
            }

            // 5. Assigned Employee check
            $assignedId = null;
            if (!empty($mappedData['assigned_to'])) {
                $val = strtolower(trim($mappedData['assigned_to']));
                if (isset($counselorMap[$val])) {
                    $assignedId = $counselorMap[$val];
                } elseif (is_numeric($val) && User::find((int)$val)) {
                    $assignedId = (int)$val;
                } else {
                    $rowWarnings[] = "Counselor '{$mappedData['assigned_to']}' will be assigned via equal distribution.";
                }
            }

            if (!empty($rowErrors)) {
                $results['errors']++;
                $results['error_details'][] = [
                    'row' => $rowNum,
                    'errors' => $rowErrors,
                    'data' => $mappedData
                ];
            } else {
                $results['valid']++;
            }

            if (count($results['rows']) < 25) {
                $results['rows'][] = [
                    'row' => $rowNum,
                    'data' => $mappedData,
                    'errors' => $rowErrors,
                    'warnings' => $rowWarnings,
                    'is_duplicate' => $isDuplicate,
                    'assigned_id' => $assignedId
                ];
            }
        }

        fclose($handle);
        return $results;
    }

    public static function commitImport(string $filePath, array $mapping, int $sessionId, string $duplicateAction = 'skip', ?string $originalFilename = null, string $assignStrategy = 'round_robin'): array
    {
        if (!file_exists($filePath)) {
            return ['error' => 'File not found.'];
        }

        $fileHash = hash_file('sha256', $filePath);
        $fileName = $originalFilename ?: basename($filePath);

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            return ['error' => 'Failed to open file.'];
        }

        $headers = fgetcsv($handle, 4096, ",");
        $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]);
        $headers = array_map('trim', $headers);

        $counselors = User::getActiveCounselors();
        $counselorMap = [];
        foreach ($counselors as $c) {
            $counselorMap[strtolower($c['name'])] = $c['id'];
            $counselorMap[strtolower($c['email'])] = $c['id'];
        }

        $admissionCounselors = User::getAdmissionCounselors();
        $counselorCount = count($admissionCounselors);
        $counselorIndex = 0;
        $counselorCounts = [];

        $batchId = ImportBatch::create($fileName, $fileHash, 0);

        $total = 0;
        $imported = 0;
        $skipped = 0;
        $failed = 0;
        $rowNum = 1;

        $db = Database::getConnection();
        $db->beginTransaction();

        try {
            while (($row = fgetcsv($handle, 4096, ",")) !== false) {
                $rowNum++;
                if (empty(array_filter($row))) {
                    continue;
                }
                $total++;

                $mapped = [];
                foreach ($mapping as $targetField => $sourceHeader) {
                    if (empty($sourceHeader)) continue;
                    $idx = array_search($sourceHeader, $headers, true);
                    $mapped[$targetField] = ($idx !== false && isset($row[$idx])) ? trim($row[$idx]) : '';
                }

                // 1. Required: Student Name
                $studentName = trim($mapped['student_name'] ?? '');
                if (empty($studentName)) {
                    $failed++;
                    ImportBatch::logRowError($batchId, $rowNum, "Missing student name in row {$rowNum}.", $mapped);
                    continue;
                }

                // 2. Mobile validation with normalization
                $cleanMobile = Sanitizer::cleanPhone($mapped['mobile'] ?? '');
                $digits = preg_replace('/\D/', '', $cleanMobile);
                if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
                    $digits = substr($digits, 1);
                } elseif (strlen($digits) === 12 && str_starts_with($digits, '91')) {
                    $digits = substr($digits, 2);
                }

                // Check alternate mobile fallback if mobile column was empty or invalid
                if (empty($digits) || strlen($digits) < 10) {
                    $rawAlt = $mapped['alternate_mobile'] ?? '';
                    $altDigits = preg_replace('/\D/', '', Sanitizer::cleanPhone($rawAlt));
                    if (strlen($altDigits) === 11 && str_starts_with($altDigits, '0')) {
                        $altDigits = substr($altDigits, 1);
                    } elseif (strlen($altDigits) === 12 && str_starts_with($altDigits, '91')) {
                        $altDigits = substr($altDigits, 2);
                    }
                    if (!empty($altDigits) && strlen($altDigits) >= 10) {
                        $digits = $altDigits;
                    } else {
                        // Graceful fallback to 'N/A' so records without mobile are safely imported
                        $digits = (!empty($cleanMobile) && $cleanMobile !== '') ? $cleanMobile : 'N/A';
                    }
                }
                $finalMobile = $digits;

                // 3. Graceful defaults for all optional columns left empty
                $parentName = !empty($mapped['parent_name']) ? trim($mapped['parent_name']) : 'Parent / Guardian';
                $classApplied = !empty($mapped['class_applied']) ? trim($mapped['class_applied']) : 'Grade 1';
                $enquirySource = !empty($mapped['enquiry_source']) ? trim($mapped['enquiry_source']) : 'Direct Walk-in';
                
                $rawPriority = ucfirst(strtolower(trim($mapped['priority'] ?? '')));
                $priority = in_array($rawPriority, ['Low', 'Medium', 'High', 'Urgent']) ? $rawPriority : 'Medium';
                
                $city = !empty($mapped['city']) ? trim($mapped['city']) : 'Purnea';
                $address = !empty($mapped['address']) ? trim($mapped['address']) : null;
                $remarks = !empty($mapped['remarks']) ? trim($mapped['remarks']) : 'Imported via CSV/Excel spreadsheet';
                
                $email = null;
                if (!empty($mapped['email'])) {
                    $cleanEmail = trim(strtolower($mapped['email']));
                    if (!in_array($cleanEmail, ['na', 'n/a', 'none', 'null', '-']) && filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
                        $email = $cleanEmail;
                    }
                }

                $altMobile = null;
                if (!empty($mapped['alternate_mobile'])) {
                    $cleanedAlt = Sanitizer::cleanPhone($mapped['alternate_mobile']);
                    $cleanedAltDigits = preg_replace('/\D/', '', $cleanedAlt);
                    if (strlen($cleanedAltDigits) >= 10) {
                        $altMobile = $cleanedAlt;
                    }
                }

                $enquiryDate = date('Y-m-d');
                if (!empty($mapped['enquiry_date'])) {
                    $rawDate = trim($mapped['enquiry_date']);
                    if (is_numeric($rawDate) && (float)$rawDate > 20000 && (float)$rawDate < 70000) {
                        $enquiryDate = date('Y-m-d', ((int)$rawDate - 25569) * 86400);
                    } else {
                        $parsedTime = strtotime($rawDate);
                        if ($parsedTime !== false && $parsedTime > 0) {
                            $enquiryDate = date('Y-m-d', $parsedTime);
                        }
                    }
                }

                // Check duplicate (only for valid phone numbers)
                $dups = [];
                if (!empty($finalMobile) && $finalMobile !== 'N/A' && strlen($finalMobile) >= 10) {
                    $dups = Lead::findDuplicates($finalMobile, $sessionId);
                }
                if (!empty($dups)) {
                    if ($duplicateAction === 'skip') {
                        $skipped++;
                        ImportBatch::logRowError($batchId, $rowNum, "Skipped: Duplicate mobile {$finalMobile} already exists ({$dups[0]['lead_ref']}).", $mapped);
                        continue;
                    } elseif ($duplicateAction === 'update') {
                        // Update existing record
                        $dupId = $dups[0]['id'];
                        Lead::update($dupId, [
                            'student_name' => $studentName,
                            'parent_name' => !empty($mapped['parent_name']) ? trim($mapped['parent_name']) : $dups[0]['parent_name'],
                            'class_applied' => !empty($mapped['class_applied']) ? trim($mapped['class_applied']) : $dups[0]['class_applied'],
                            'remarks' => trim(($dups[0]['remarks'] ?? '') . "\n[Import Update]: " . $remarks)
                        ]);
                        $imported++;
                        continue;
                    }
                    // If duplicateAction is 'allow', proceed with creation
                }

                // Assigned employee determination
                $assignedId = null;
                if (!empty($mapped['assigned_to'])) {
                    $val = strtolower(trim($mapped['assigned_to']));
                    if (isset($counselorMap[$val])) {
                        $assignedId = $counselorMap[$val];
                    } elseif (is_numeric($val) && User::find((int)$val)) {
                        $assignedId = (int)$val;
                    }
                }

                // If strategy is round_robin (default), distribute equally among active Admission Counselors
                if ($assignStrategy === 'round_robin' && $counselorCount > 0) {
                    $assignedId = (int)$admissionCounselors[$counselorIndex % $counselorCount]['id'];
                    $counselorIndex++;
                    $counselorCounts[$assignedId] = ($counselorCounts[$assignedId] ?? 0) + 1;
                }

                // ALL imported records strictly set to status 'New'
                $newLeadId = Lead::create([
                    'session_id' => $sessionId,
                    'student_name' => $studentName,
                    'parent_name' => $parentName,
                    'mobile' => $finalMobile,
                    'alternate_mobile' => $altMobile,
                    'email' => $email,
                    'class_applied' => $classApplied,
                    'enquiry_date' => $enquiryDate,
                    'enquiry_source' => $enquirySource,
                    'assigned_to' => $assignedId,
                    'status' => 'New',
                    'priority' => $priority,
                    'address' => $address,
                    'city' => $city,
                    'remarks' => $remarks,
                ]);

                $imported++;
            }

            $db->commit();
            fclose($handle);

            // Send notification to each counselor who received leads
            $assignerName = Auth::user()['name'] ?? 'Admin';
            foreach ($counselorCounts as $cId => $cnt) {
                Notification::create([
                    'user_id' => $cId,
                    'sender_id' => Auth::id() ?: 1,
                    'type' => 'lead_assigned',
                    'title' => "{$cnt} New Leads Assigned",
                    'message' => "{$assignerName} imported {$cnt} new admission leads and distributed them to you from file '{$fileName}'.",
                    'link' => 'leads'
                ]);
            }

            ImportBatch::complete($batchId, $imported, $skipped, $failed, "Import finished: {$imported} imported, {$skipped} skipped, {$failed} failed.");

            return [
                'batch_id' => $batchId,
                'total' => $total,
                'imported' => $imported,
                'skipped' => $skipped,
                'failed' => $failed,
                'distributed' => $counselorCounts,
            ];
        } catch (\Throwable $e) {
            $db->rollBack();
            fclose($handle);
            ImportBatch::complete($batchId, $imported, $skipped, $total - $imported, "Error: " . $e->getMessage());
            return ['error' => 'Database transaction error during import: ' . $e->getMessage()];
        }
    }
}
