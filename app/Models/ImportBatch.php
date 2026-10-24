<?php
namespace App\Models;

use App\Config\Database;
use App\Helpers\Auth;
use App\Helpers\Logger;
use PDO;

class ImportBatch
{
    public static function all(): array
    {
        $db = Database::getConnection();
        $sql = "SELECT ib.*, u.name as imported_by_name 
                FROM import_batches ib
                JOIN users u ON ib.imported_by = u.id
                ORDER BY ib.created_at DESC";
        return $db->query($sql)->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT ib.*, u.name as imported_by_name 
                              FROM import_batches ib
                              JOIN users u ON ib.imported_by = u.id
                              WHERE ib.id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function getErrors(int $batchId): array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM import_row_errors WHERE batch_id = ? ORDER BY row_number ASC");
        $stmt->execute([$batchId]);
        return $stmt->fetchAll();
    }

    public static function checkDuplicateFile(string $fileHash): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM import_batches WHERE file_hash = ? AND status = 'Completed' LIMIT 1");
        $stmt->execute([$fileHash]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function create(string $fileName, ?string $fileHash, int $totalRows): int
    {
        $db = Database::getConnection();
        $importedBy = Auth::id() ?: 1;
        $stmt = $db->prepare("INSERT INTO import_batches (file_name, file_hash, imported_by, total_rows, status) VALUES (?, ?, ?, ?, 'Processing')");
        $stmt->execute([$fileName, $fileHash, $importedBy, $totalRows]);
        return (int)$db->lastInsertId();
    }

    public static function logRowError(int $batchId, int $rowNumber, string $errorMessage, ?array $rawData = null): void
    {
        $db = Database::getConnection();
        $rawJson = $rawData ? json_encode($rawData, JSON_UNESCAPED_UNICODE) : null;
        $stmt = $db->prepare("INSERT INTO import_row_errors (batch_id, row_number, error_message, raw_data) VALUES (?, ?, ?, ?)");
        $stmt->execute([$batchId, $rowNumber, $errorMessage, $rawJson]);
    }

    public static function complete(int $batchId, int $imported, int $skipped, int $failed, ?string $notes = null): void
    {
        $db = Database::getConnection();
        $status = $failed > 0 && $imported === 0 ? 'Failed' : 'Completed';
        $stmt = $db->prepare("UPDATE import_batches SET imported_rows = ?, skipped_rows = ?, failed_rows = ?, status = ?, notes = ? WHERE id = ?");
        $stmt->execute([$imported, $skipped, $failed, $status, $notes, $batchId]);

        Logger::audit('import_completed', 'import_batch', $batchId, null, [
            'imported' => $imported,
            'skipped' => $skipped,
            'failed' => $failed
        ]);
    }

    public static function deleteError(int $id, int $batchId = 0): bool
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM import_row_errors WHERE id = ?");
        $res = $stmt->execute([$id]);
        if ($batchId > 0) {
            self::updateErrorCount($batchId);
        }
        return $res;
    }

    public static function deleteErrors(array $ids, int $batchId = 0): int
    {
        $cleanIds = array_filter(array_map('intval', $ids));
        if (empty($cleanIds)) {
            return 0;
        }

        $db = Database::getConnection();
        $placeholders = implode(',', array_fill(0, count($cleanIds), '?'));
        $stmt = $db->prepare("DELETE FROM import_row_errors WHERE id IN ({$placeholders})");
        $stmt->execute(array_values($cleanIds));
        $count = $stmt->rowCount();

        if ($batchId > 0) {
            self::updateErrorCount($batchId);
        }
        return $count;
    }

    public static function clearAllErrors(int $batchId): int
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("DELETE FROM import_row_errors WHERE batch_id = ?");
        $stmt->execute([$batchId]);
        $count = $stmt->rowCount();

        self::updateErrorCount($batchId);
        return $count;
    }

    public static function updateErrorCount(int $batchId): void
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT COUNT(*) FROM import_row_errors WHERE batch_id = ?");
        $stmt->execute([$batchId]);
        $remaining = (int)$stmt->fetchColumn();

        $update = $db->prepare("UPDATE import_batches SET failed_rows = ? WHERE id = ?");
        $update->execute([$remaining, $batchId]);
    }
}
