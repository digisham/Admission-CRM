<?php
namespace App\Controllers;

use App\Config\App;
use App\Helpers\Auth;
use App\Helpers\Csrf;
use App\Helpers\View;
use App\Models\Lead;
use App\Models\LeadDocument;

class DocumentController
{
    public function index(): void
    {
        if (!Auth::check()) {
            View::redirect(View::url('login'));
        }

        // Show leads with admission confirmed or application in progress
        $filters = [
            'status' => 'Admission Confirmed',
        ];
        $leads = Lead::paginate($filters, 1, 50)['data'];
        $docTypes = LeadDocument::getDocumentTypes();

        View::render('documents/index', [
            'leads' => $leads,
            'docTypes' => $docTypes,
        ]);
    }

    public function updateStatus(): void
    {
        if (!Csrf::validate($_POST['_csrf_token'] ?? null)) {
            View::flash('error', 'Security token invalid.');
            View::redirect(View::url('leads'));
        }

        $leadId = (int)($_POST['lead_id'] ?? 0);
        $docTypeId = (int)($_POST['document_type_id'] ?? 0);
        $status = $_POST['status'] ?? 'Pending';
        $remarks = trim($_POST['remarks'] ?? '');
        $verStatus = $_POST['verification_status'] ?? 'Not Reviewed';
        $verRemarks = trim($_POST['verification_remarks'] ?? '');

        $lead = Lead::find($leadId);
        if (!$lead || !Auth::canAccessLead($lead)) {
            View::flash('error', 'Unauthorized access.');
            View::redirect(View::url('leads'));
        }

        LeadDocument::updateStatus($leadId, $docTypeId, $status, $remarks, $verStatus, $verRemarks);
        View::flash('success', 'Document status updated successfully.');
        View::redirect(View::url("leads/show&id={$leadId}#documents"));
    }

    public function upload(): void
    {
        if (!Csrf::validate($_POST['_csrf_token'] ?? null)) {
            View::flash('error', 'Security token invalid.');
            View::redirect(View::url('leads'));
        }

        $leadId = (int)($_POST['lead_id'] ?? 0);
        $docTypeId = (int)($_POST['document_type_id'] ?? 0);

        $lead = Lead::find($leadId);
        if (!$lead || !Auth::canAccessLead($lead)) {
            View::flash('error', 'Unauthorized access.');
            View::redirect(View::url('leads'));
        }

        if (empty($_FILES['document_file']) || $_FILES['document_file']['error'] !== UPLOAD_ERR_OK) {
            View::flash('error', 'Please select a valid file to upload.');
            View::redirect(View::url("leads/show&id={$leadId}#documents"));
        }

        $file = $_FILES['document_file'];
        $maxBytes = (int)App::get('MAX_UPLOAD_SIZE_MB', 10) * 1024 * 1024;

        if ($file['size'] > $maxBytes) {
            View::flash('error', 'File size exceeds maximum allowed limit of 10MB.');
            View::redirect(View::url("leads/show&id={$leadId}#documents"));
        }

        $allowedExts = explode(',', strtolower(App::get('ALLOWED_EXTENSIONS', 'pdf,jpg,jpeg,png,webp')));
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowedExts, true)) {
            View::flash('error', "Invalid file type ({$ext}). Allowed formats: " . implode(', ', $allowedExts));
            View::redirect(View::url("leads/show&id={$leadId}#documents"));
        }

        // Store privately in storage/private_uploads/
        $uploadDir = dirname(__DIR__, 2) . '/storage/private_uploads/';
        if (!is_dir($uploadDir)) {
            @mkdir($uploadDir, 0755, true);
        }

        $safeName = sprintf('doc_%d_%d_%s.%s', $leadId, $docTypeId, bin2hex(random_bytes(8)), $ext);
        $destination = $uploadDir . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            View::flash('error', 'Failed to save uploaded file to private storage.');
            View::redirect(View::url("leads/show&id={$leadId}#documents"));
        }

        LeadDocument::attachFile($leadId, $docTypeId, [
            'file_path' => $safeName,
            'file_name' => $file['name'],
            'file_size' => $file['size'],
            'mime_type' => $file['type'] ?? 'application/octet-stream',
        ]);

        View::flash('success', "File '{$file['name']}' uploaded and verified successfully.");
        View::redirect(View::url("leads/show&id={$leadId}#documents"));
    }

    public function delete(): void
    {
        if (!Csrf::validate($_POST['_csrf_token'] ?? null)) {
            View::flash('error', 'Security token invalid.');
            View::redirect(View::url('leads'));
        }

        $leadId = (int)($_POST['lead_id'] ?? 0);
        $docTypeId = (int)($_POST['document_type_id'] ?? 0);

        $lead = Lead::find($leadId);
        if (!$lead || !Auth::canAccessLead($lead)) {
            View::flash('error', 'Unauthorized access.');
            View::redirect(View::url('leads'));
        }

        $deleted = LeadDocument::deleteUploadedFile($leadId, $docTypeId);
        if ($deleted) {
            View::flash('success', 'Uploaded document file deleted successfully.');
        } else {
            View::flash('error', 'Document record not found or already deleted.');
        }

        View::redirect(View::url("leads/show&id={$leadId}#documents"));
    }

    public function download(): void
    {
        if (!Auth::check()) {
            http_response_code(403);
            die("Access Denied: Please log in to access admission documents.");
        }

        $leadId = (int)($_GET['lead_id'] ?? 0);
        $docTypeId = (int)($_GET['doc_type_id'] ?? 0);

        $doc = LeadDocument::getDocumentForDownload($leadId, $docTypeId);
        if (!$doc || empty($doc['file_path'])) {
            http_response_code(404);
            die("Requested document file not found.");
        }

        // Check permission
        $lead = Lead::find($leadId);
        if (!$lead || !Auth::canAccessLead($lead)) {
            http_response_code(403);
            die("Access Denied: You do not have permission to view documents for this lead.");
        }

        $filePath = dirname(__DIR__, 2) . '/storage/private_uploads/' . $doc['file_path'];
        if (!file_exists($filePath)) {
            http_response_code(404);
            die("Document file does not exist on disk.");
        }

        // Generate standardized clean filename: [StudentFirstName]_[LeadRef]_[DocumentName].[ext]
        $rawFirstName = explode(' ', trim($lead['student_name'] ?? 'Student'))[0];
        $firstName = preg_replace('/[^a-zA-Z0-9]/', '', $rawFirstName) ?: 'Student';
        $cleanRef = preg_replace('/[^a-zA-Z0-9\-]/', '_', $lead['lead_ref'] ?? 'Lead');
        $cleanDocName = preg_replace('/[^a-zA-Z0-9]/', '_', trim($doc['doc_name'] ?? 'Document'));
        $ext = strtolower(pathinfo($doc['file_name'] ?: $doc['file_path'], PATHINFO_EXTENSION)) ?: 'bin';

        $customDownloadName = "{$firstName}_{$cleanRef}_{$cleanDocName}.{$ext}";
        $mime = $doc['mime_type'] ?: 'application/octet-stream';
        $disposition = ($_GET['mode'] ?? '') === 'download' ? 'attachment' : 'inline';

        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . $disposition . '; filename="' . addslashes($customDownloadName) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        readfile($filePath);
        exit;
    }

    public function downloadAll(): void
    {
        if (!Auth::check()) {
            http_response_code(403);
            die("Access Denied: Please log in to access admission documents.");
        }

        $leadId = (int)($_GET['lead_id'] ?? 0);
        $lead = Lead::find($leadId);

        if (!$lead || !Auth::canAccessLead($lead)) {
            View::flash('error', 'Access denied.');
            View::redirect(View::url('leads'));
        }

        $docs = LeadDocument::getUploadedDocumentsForLead($leadId);
        if (empty($docs)) {
            View::flash('error', 'No uploaded digital files found for this lead to download.');
            View::redirect(View::url("leads/show&id={$leadId}#documents"));
        }

        $storageDir = dirname(__DIR__, 2) . '/storage/private_uploads/';
        $tempDir = dirname(__DIR__, 2) . '/storage/temp/';
        if (!is_dir($tempDir)) {
            @mkdir($tempDir, 0755, true);
        }

        $rawFirstName = explode(' ', trim($lead['student_name'] ?? 'Student'))[0];
        $firstName = preg_replace('/[^a-zA-Z0-9]/', '', $rawFirstName) ?: 'Student';
        $cleanRef = preg_replace('/[^a-zA-Z0-9\-]/', '_', $lead['lead_ref'] ?? 'Lead');
        $zipFilename = "{$firstName}_{$cleanRef}_Admission_Documents.zip";
        $zipPath = $tempDir . uniqid('zip_', true) . '.zip';

        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            View::flash('error', 'Unable to create ZIP archive.');
            View::redirect(View::url("leads/show&id={$leadId}#documents"));
        }

        $addedCount = 0;
        $usedEntryNames = [];
        foreach ($docs as $d) {
            $realFile = $storageDir . $d['file_path'];
            if (!file_exists($realFile)) continue;

            $cleanDocName = preg_replace('/[^a-zA-Z0-9]/', '_', trim($d['doc_name'] ?? 'Document'));
            $ext = strtolower(pathinfo($d['file_name'] ?: $d['file_path'], PATHINFO_EXTENSION)) ?: 'bin';
            $entryBase = "{$firstName}_{$cleanRef}_{$cleanDocName}";
            $entryName = "{$entryBase}.{$ext}";

            // Avoid collisions if duplicate names
            $c = 1;
            while (in_array($entryName, $usedEntryNames, true)) {
                $entryName = "{$entryBase}_" . ($c++) . ".{$ext}";
            }
            $usedEntryNames[] = $entryName;

            $zip->addFile($realFile, $entryName);
            $addedCount++;
        }

        $zip->close();

        if ($addedCount === 0 || !file_exists($zipPath)) {
            @unlink($zipPath);
            View::flash('error', 'Uploaded files could not be located on disk.');
            View::redirect(View::url("leads/show&id={$leadId}#documents"));
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . addslashes($zipFilename) . '"');
        header('Content-Length: ' . filesize($zipPath));
        header('Pragma: no-cache');
        header('Expires: 0');

        readfile($zipPath);
        @unlink($zipPath);
        exit;
    }
}
