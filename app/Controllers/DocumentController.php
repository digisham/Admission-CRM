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

        $filename = $doc['file_name'] ?: basename($filePath);
        $mime = $doc['mime_type'] ?: 'application/octet-stream';

        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . addslashes($filename) . '"');
        header('Content-Length: ' . filesize($filePath));
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');

        readfile($filePath);
        exit;
    }
}
