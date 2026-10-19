<?php
namespace App\Services;

use App\Config\Database;
use App\Helpers\Auth;
use App\Helpers\Sanitizer;
use App\Models\AcademicSession;
use App\Models\ImportBatch;
use App\Models\Lead;
use App\Models\Setting;
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
                $idx = array_search($sourceHeader, $headers, true);
                $mappedData[$targetField] = ($idx !== false && isset($row[$idx])) ? trim($row[$idx]) : '';
            }

            $rowErrors = [];
            $rowWarnings = [];

            // 1. Student Name required
            if (empty($mappedData['student_name'])) {
                $rowErrors[] = "Student Name is required.";
            }

            // 2. Mobile required & valid
            $cleanMobile = Sanitizer::cleanPhone($mappedData['mobile'] ?? '');
            if (empty($cleanMobile)) {
                $rowErrors[] = "Mobile Number is required.";
            } elseif (strlen($cleanMobile) < 10) {
                $rowErrors[] = "Mobile Number must have at least 10 digits.";
            }

            // 3. Class Applied
            if (empty($mappedData['class_applied'])) {
                $mappedData['class_applied'] = 'Grade 1';
                $rowWarnings[] = "Defaulted Class Applied to Grade 1.";
            }

            // 4. Duplicate check
            $isDuplicate = false;
            if (!empty($cleanMobile)) {
                $dups = Lead::findDuplicates($cleanMobile, $sessionId);
                if (!empty($dups)) {
                    $isDuplicate = true;
                    $results['duplicates']++;
                    $rowWarnings[] = "Duplicate phone detected: matches existing lead {$dups[0]['lead_ref']} ({$dups[0]['student_name']}).";
                }
            }

            // 5. Assigned Employee check
            $assignedId = null;
            if (!empty($mappedData['assigned_to'])) {
                $val = strtolower($mappedData['assigned_to']);
                if (isset($counselorMap[$val])) {
                    $assignedId = $counselorMap[$val];
                } elseif (is_numeric($val) && User::find((int)$val)) {
                    $assignedId = (int)$val;
                } else {
                    $rowWarnings[] = "Unknown employee '{$mappedData['assigned_to']}'; will leave unassigned.";
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

    public static function commitImport(string $filePath, array $mapping, int $sessionId, string $duplicateAction = 'skip', ?string $originalFilename = null): array
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
                    $idx = array_search($sourceHeader, $headers, true);
                    $mapped[$targetField] = ($idx !== false && isset($row[$idx])) ? trim($row[$idx]) : '';
                }

                // Required field check
                $cleanMobile = Sanitizer::cleanPhone($mapped['mobile'] ?? '');
                if (empty($mapped['student_name']) || empty($cleanMobile) || strlen($cleanMobile) < 10) {
                    $failed++;
                    ImportBatch::logRowError($batchId, $rowNum, "Missing student name or valid 10-digit mobile number.", $mapped);
                    continue;
                }

                // Check duplicate
                $dups = Lead::findDuplicates($cleanMobile, $sessionId);
                if (!empty($dups)) {
                    if ($duplicateAction === 'skip') {
                        $skipped++;
                        ImportBatch::logRowError($batchId, $rowNum, "Skipped: Duplicate mobile {$cleanMobile} already exists ({$dups[0]['lead_ref']}).", $mapped);
                        continue;
                    } elseif ($duplicateAction === 'update') {
                        // Update existing record
                        $dupId = $dups[0]['id'];
                        Lead::update($dupId, [
                            'student_name' => $mapped['student_name'],
                            'parent_name' => $mapped['parent_name'] ?? $dups[0]['parent_name'],
                            'class_applied' => $mapped['class_applied'] ?: $dups[0]['class_applied'],
                            'remarks' => trim(($dups[0]['remarks'] ?? '') . "\n[Import Update]: " . ($mapped['remarks'] ?? 'Updated via Excel import'))
                        ]);
                        $imported++;
                        continue;
                    }
                    // If duplicateAction is 'allow', proceed with creation
                }

                // Assigned employee
                $assignedId = null;
                if (!empty($mapped['assigned_to'])) {
                    $val = strtolower($mapped['assigned_to']);
                    if (isset($counselorMap[$val])) {
                        $assignedId = $counselorMap[$val];
                    } elseif (is_numeric($val) && User::find((int)$val)) {
                        $assignedId = (int)$val;
                    }
                }

                Lead::create([
                    'session_id' => $sessionId,
                    'student_name' => $mapped['student_name'],
                    'parent_name' => $mapped['parent_name'] ?? 'Parent / Guardian',
                    'mobile' => $cleanMobile,
                    'alternate_mobile' => Sanitizer::cleanPhone($mapped['alternate_mobile'] ?? null),
                    'email' => !empty($mapped['email']) ? $mapped['email'] : null,
                    'class_applied' => $mapped['class_applied'] ?: 'Grade 1',
                    'enquiry_date' => !empty($mapped['enquiry_date']) ? date('Y-m-d', strtotime($mapped['enquiry_date'])) : date('Y-m-d'),
                    'enquiry_source' => $mapped['enquiry_source'] ?: 'Excel Import',
                    'assigned_to' => $assignedId,
                    'status' => $mapped['status'] ?: 'New',
                    'priority' => in_array($mapped['priority'] ?? '', ['Low', 'Medium', 'High', 'Urgent']) ? $mapped['priority'] : 'Medium',
                    'address' => $mapped['address'] ?? null,
                    'city' => $mapped['city'] ?? 'Purnea',
                    'remarks' => $mapped['remarks'] ?? null,
                ]);

                $imported++;
            }

            $db->commit();
            fclose($handle);

            ImportBatch::complete($batchId, $imported, $skipped, $failed, "Import finished: {$imported} imported, {$skipped} skipped, {$failed} failed.");

            return [
                'batch_id' => $batchId,
                'total' => $total,
                'imported' => $imported,
                'skipped' => $skipped,
                'failed' => $failed,
            ];
        } catch (\Throwable $e) {
            $db->rollBack();
            fclose($handle);
            ImportBatch::complete($batchId, $imported, $skipped, $total - $imported, "Error: " . $e->getMessage());
            return ['error' => 'Database transaction error during import: ' . $e->getMessage()];
        }
    }
}
