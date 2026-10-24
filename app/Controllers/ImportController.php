<?php
namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\View;
use App\Models\AcademicSession;
use App\Models\ImportBatch;
use App\Services\CsvExportService;
use App\Services\CsvImportService;
use App\Services\SimpleXlsxService;

class ImportController
{
    public function index(): void
    {
        if (!Auth::isAdmin()) {
            View::flash('error', 'Only administrators can import leads.');
            View::redirect(View::url('leads'));
        }

        $activeSession = AcademicSession::getActive();
        $batches = ImportBatch::all();

        View::render('imports/index', [
            'activeSession' => $activeSession,
            'batches' => $batches,
            'step' => 1
        ]);
    }

    public function template(): void
    {
        CsvExportService::streamTemplate();
    }

    public function preview(): void
    {
        if (!Auth::isAdmin()) {
            View::flash('error', 'Unauthorized.');
            View::redirect(View::url('leads'));
        }

        if (!Csrf::validate($_POST['_csrf_token'] ?? null)) {
            View::flash('error', 'Security token expired.');
            View::redirect(View::url('import'));
        }

        if (empty($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
            View::flash('error', 'Please select a valid CSV (.csv) or Excel (.xlsx) file to upload.');
            View::redirect(View::url('import'));
        }

        $file = $_FILES['csv_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['csv', 'xlsx'])) {
            View::flash('error', 'Please upload a CSV format file (.csv) or Excel spreadsheet (.xlsx).');
            View::redirect(View::url('import'));
        }

        // Store temporary file in storage/imports/
        $importDir = dirname(__DIR__, 2) . '/storage/imports/';
        if (!is_dir($importDir)) {
            @mkdir($importDir, 0755, true);
        }

        $tempFileName = 'import_' . time() . '_' . bin2hex(random_bytes(6)) . '.csv';
        $destination = $importDir . $tempFileName;

        if ($ext === 'xlsx') {
            $rawXlsx = $importDir . 'raw_' . time() . '_' . bin2hex(random_bytes(4)) . '.xlsx';
            if (!move_uploaded_file($file['tmp_name'], $rawXlsx)) {
                View::flash('error', 'Failed to store uploaded Excel file on server.');
                View::redirect(View::url('import'));
            }
            $converted = SimpleXlsxService::convertToCsv($rawXlsx, $destination);
            @unlink($rawXlsx);
            if (!$converted || !file_exists($destination)) {
                View::flash('error', 'Unable to parse Excel (.xlsx) file. Please ensure it is a valid Excel workbook.');
                View::redirect(View::url('import'));
            }
        } else {
            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                View::flash('error', 'Failed to store uploaded file on server.');
                View::redirect(View::url('import'));
            }
        }

        // Check if identical file was previously imported
        $fileHash = hash_file('sha256', $destination);
        $duplicateBatch = ImportBatch::checkDuplicateFile($fileHash);

        $parsed = CsvImportService::parsePreview($destination);
        if (isset($parsed['error'])) {
            @unlink($destination);
            View::flash('error', $parsed['error']);
            View::redirect(View::url('import'));
        }

        $sessionId = (int)($_POST['session_id'] ?? AcademicSession::getActive()['id']);
        $session = AcademicSession::find($sessionId);

        View::render('imports/preview', [
            'tempFile' => $tempFileName,
            'originalName' => $file['name'],
            'headers' => $parsed['headers'],
            'previewRows' => $parsed['preview_rows'],
            'totalRows' => $parsed['total_rows'],
            'session' => $session,
            'duplicateBatch' => $duplicateBatch,
            'step' => 2
        ]);
    }

    public function validateStep(): void
    {
        if (!Auth::isAdmin()) {
            View::json(['error' => 'Unauthorized'], 403);
        }

        $tempFile = basename($_POST['temp_file'] ?? '');
        $filePath = dirname(__DIR__, 2) . '/storage/imports/' . $tempFile;
        $sessionId = (int)($_POST['session_id'] ?? AcademicSession::getActive()['id']);
        $mapping = $_POST['mapping'] ?? [];

        if (!file_exists($filePath)) {
            View::json(['error' => 'Uploaded file session has expired. Please re-upload.'], 400);
        }

        $validation = CsvImportService::validateAndReview($filePath, $mapping, $sessionId);
        View::json($validation);
    }

    public function commit(): void
    {
        if (!Auth::isAdmin()) {
            View::flash('error', 'Unauthorized.');
            View::redirect(View::url('leads'));
        }

        if (!Csrf::validate($_POST['_csrf_token'] ?? null)) {
            View::flash('error', 'Security token expired.');
            View::redirect(View::url('import'));
        }

        $tempFile = basename($_POST['temp_file'] ?? '');
        $originalName = $_POST['original_name'] ?? 'leads_import.csv';
        $filePath = dirname(__DIR__, 2) . '/storage/imports/' . $tempFile;
        $sessionId = (int)($_POST['session_id'] ?? AcademicSession::getActive()['id']);
        $duplicateAction = $_POST['duplicate_action'] ?? 'skip';
        $assignStrategy = $_POST['assign_strategy'] ?? 'round_robin';
        $mapping = $_POST['mapping'] ?? [];

        if (!file_exists($filePath)) {
            View::flash('error', 'Import session file expired. Please upload again.');
            View::redirect(View::url('import'));
        }

        $result = CsvImportService::commitImport($filePath, $mapping, $sessionId, $duplicateAction, $originalName, $assignStrategy);

        // Clean up temp file
        @unlink($filePath);

        if (isset($result['error'])) {
            View::flash('error', $result['error']);
            View::redirect(View::url('import'));
        }

        View::flash('success', "Import Complete! {$result['imported']} records imported successfully, {$result['skipped']} duplicates skipped, {$result['failed']} failed.");
        View::redirect(View::url("import/history&id={$result['batch_id']}"));
    }

    public function history(): void
    {
        if (!Auth::isAdmin()) {
            View::redirect(View::url('leads'));
        }

        $batchId = (int)($_GET['id'] ?? 0);
        $batch = null;
        $errors = [];

        if ($batchId > 0) {
            $batch = ImportBatch::find($batchId);
            $errors = ImportBatch::getErrors($batchId);
        }

        $allBatches = ImportBatch::all();

        View::render('imports/history', [
            'batch' => $batch,
            'errors' => $errors,
            'allBatches' => $allBatches,
        ]);
    }

    public function deleteError(): void
    {
        if (!Auth::isAdmin()) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                View::json(['error' => 'Unauthorized'], 403);
            }
            View::flash('error', 'Unauthorized.');
            View::redirect(View::url('import'));
        }

        $id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
        $batchId = (int)($_POST['batch_id'] ?? $_GET['batch_id'] ?? 0);

        if ($id <= 0) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                View::json(['error' => 'Invalid error log ID.'], 400);
            }
            View::flash('error', 'Invalid error record.');
            View::redirect(View::url('import/history&id=' . $batchId));
        }

        ImportBatch::deleteError($id, $batchId);

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            View::json([
                'success' => true,
                'message' => 'Error log deleted successfully.',
                'deleted_id' => $id,
                'batch_id' => $batchId
            ]);
        }

        View::flash('success', 'Error log item deleted.');
        View::redirect(View::url('import/history&id=' . $batchId));
    }

    public function deleteErrors(): void
    {
        if (!Auth::isAdmin()) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                View::json(['error' => 'Unauthorized'], 403);
            }
            View::flash('error', 'Unauthorized.');
            View::redirect(View::url('import'));
        }

        $rawIds = $_POST['ids'] ?? [];
        if (is_string($rawIds)) {
            $rawIds = explode(',', $rawIds);
        }
        $ids = array_filter(array_map('intval', (array)$rawIds));
        $batchId = (int)($_POST['batch_id'] ?? 0);

        if (empty($ids)) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                View::json(['error' => 'No error logs selected for deletion.'], 400);
            }
            View::flash('error', 'No records selected.');
            View::redirect(View::url('import/history&id=' . $batchId));
        }

        $deletedCount = ImportBatch::deleteErrors($ids, $batchId);

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            View::json([
                'success' => true,
                'message' => "{$deletedCount} error logs deleted successfully.",
                'deleted_count' => $deletedCount,
                'deleted_ids' => $ids,
                'batch_id' => $batchId
            ]);
        }

        View::flash('success', "{$deletedCount} error logs deleted successfully.");
        View::redirect(View::url('import/history&id=' . $batchId));
    }

    public function clearErrors(): void
    {
        if (!Auth::isAdmin()) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                View::json(['error' => 'Unauthorized'], 403);
            }
            View::flash('error', 'Unauthorized.');
            View::redirect(View::url('import'));
        }

        $batchId = (int)($_POST['batch_id'] ?? 0);

        if ($batchId <= 0) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                View::json(['error' => 'Invalid batch ID.'], 400);
            }
            View::flash('error', 'Invalid batch ID.');
            View::redirect(View::url('import'));
        }

        $deletedCount = ImportBatch::clearAllErrors($batchId);

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            View::json([
                'success' => true,
                'message' => "All {$deletedCount} error logs cleared.",
                'deleted_count' => $deletedCount,
                'batch_id' => $batchId
            ]);
        }

        View::flash('success', "All {$deletedCount} error logs cleared.");
        View::redirect(View::url('import/history&id=' . $batchId));
    }
}
