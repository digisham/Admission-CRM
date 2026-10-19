<?php
namespace App\Models;

use App\Config\Database;
use App\Helpers\Auth;
use App\Helpers\Logger;
use PDO;

class LeadDocument
{
    public static function getDocumentTypes(): array
    {
        $db = Database::getConnection();
        return $db->query("SELECT * FROM document_types ORDER BY sort_order ASC, id ASC")->fetchAll();
    }

    public static function getChecklistForLead(int $leadId): array
    {
        $db = Database::getConnection();
        $docTypes = self::getDocumentTypes();

        $stmt = $db->prepare("SELECT ld.*, u.name as uploader_name 
                              FROM lead_documents ld
                              LEFT JOIN users u ON ld.uploaded_by = u.id
                              WHERE ld.lead_id = ?");
        $stmt->execute([$leadId]);
        $existing = [];
        foreach ($stmt->fetchAll() as $row) {
            $existing[$row['document_type_id']] = $row;
        }

        $checklist = [];
        foreach ($docTypes as $type) {
            $record = $existing[$type['id']] ?? null;
            $checklist[] = [
                'document_type_id' => $type['id'],
                'name' => $type['name'],
                'code' => $type['code'],
                'description' => $type['description'],
                'is_required' => (bool)$type['is_required'],
                'status' => $record['status'] ?? 'Pending',
                'file_path' => $record['file_path'] ?? null,
                'file_name' => $record['file_name'] ?? null,
                'file_size' => $record['file_size'] ?? null,
                'remarks' => $record['remarks'] ?? '',
                'verification_status' => $record['verification_status'] ?? 'Not Reviewed',
                'verification_remarks' => $record['verification_remarks'] ?? '',
                'uploaded_by' => $record['uploaded_by'] ?? null,
                'uploader_name' => $record['uploader_name'] ?? null,
                'uploaded_at' => $record['uploaded_at'] ?? null,
                'updated_at' => $record['updated_at'] ?? null,
            ];
        }

        return $checklist;
    }

    public static function updateStatus(int $leadId, int $docTypeId, string $status, ?string $remarks = null, ?string $verStatus = null, ?string $verRemarks = null): bool
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("INSERT INTO lead_documents (lead_id, document_type_id, status, remarks, verification_status, verification_remarks)
                              VALUES (?, ?, ?, ?, ?, ?)
                              ON DUPLICATE KEY UPDATE 
                                status = VALUES(status),
                                remarks = VALUES(remarks),
                                verification_status = COALESCE(VALUES(verification_status), verification_status),
                                verification_remarks = COALESCE(VALUES(verification_remarks), verification_remarks)");
        $res = $stmt->execute([
            $leadId,
            $docTypeId,
            $status,
            $remarks,
            $verStatus ?: 'Not Reviewed',
            $verRemarks
        ]);

        Logger::audit('document_status_update', 'lead_document', $leadId, null, [
            'type_id' => $docTypeId,
            'status' => $status
        ]);

        return $res;
    }

    public static function attachFile(int $leadId, int $docTypeId, array $fileInfo): bool
    {
        $db = Database::getConnection();

        $stmt = $db->prepare("INSERT INTO lead_documents (lead_id, document_type_id, status, file_path, file_name, file_size, mime_type, uploaded_by, uploaded_at)
                              VALUES (?, ?, 'Available', ?, ?, ?, ?, ?, NOW())
                              ON DUPLICATE KEY UPDATE 
                                status = 'Available',
                                file_path = VALUES(file_path),
                                file_name = VALUES(file_name),
                                file_size = VALUES(file_size),
                                mime_type = VALUES(mime_type),
                                uploaded_by = VALUES(uploaded_by),
                                uploaded_at = NOW()");
        $res = $stmt->execute([
            $leadId,
            $docTypeId,
            $fileInfo['file_path'],
            $fileInfo['file_name'],
            $fileInfo['file_size'],
            $fileInfo['mime_type'],
            Auth::id()
        ]);

        Logger::audit('document_file_upload', 'lead_document', $leadId, null, [
            'type_id' => $docTypeId,
            'filename' => $fileInfo['file_name']
        ]);

        return $res;
    }

    public static function getDocumentForDownload(int $leadId, int $docTypeId): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT ld.*, l.assigned_to 
                              FROM lead_documents ld
                              JOIN leads l ON ld.lead_id = l.id
                              WHERE ld.lead_id = ? AND ld.document_type_id = ? LIMIT 1");
        $stmt->execute([$leadId, $docTypeId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }
}
