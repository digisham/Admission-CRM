<?php
namespace App\Models;

use App\Config\Database;
use App\Helpers\Logger;
use PDO;

class AcademicSession
{
    public static function all(bool $includeArchived = false): array
    {
        $db = Database::getConnection();
        $sql = "SELECT * FROM academic_sessions";
        if (!$includeArchived) {
            $sql .= " WHERE is_archived = 0";
        }
        $sql .= " ORDER BY is_active DESC, start_date DESC";
        return $db->query($sql)->fetchAll();
    }

    public static function find(int $id): ?array
    {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM academic_sessions WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function getActive(): array
    {
        $db = Database::getConnection();
        $stmt = $db->query("SELECT * FROM academic_sessions WHERE is_active = 1 AND is_archived = 0 LIMIT 1");
        $row = $stmt->fetch();
        if ($row) {
            return $row;
        }

        // Fallback: create default 2027–28 session if missing
        $stmt = $db->prepare("INSERT INTO academic_sessions (session_label, start_date, end_date, admission_open_date, admission_close_date, is_active, notes) 
                              VALUES ('2027–28', '2027-04-01', '2028-03-31', '2026-10-01', '2027-07-31', 1, 'Initial active admission session')");
        $stmt->execute();
        return self::find((int)$db->lastInsertId());
    }

    public static function setActive(int $id): bool
    {
        $db = Database::getConnection();
        $db->beginTransaction();
        try {
            // Deactivate all
            $db->exec("UPDATE academic_sessions SET is_active = 0");
            // Set specified session as active
            $stmt = $db->prepare("UPDATE academic_sessions SET is_active = 1 WHERE id = ?");
            $stmt->execute([$id]);
            $db->commit();

            Logger::audit('session_activate', 'academic_session', $id);
            return true;
        } catch (\Throwable $e) {
            $db->rollBack();
            return false;
        }
    }

    public static function create(array $data): int
    {
        $db = Database::getConnection();
        $isActive = !empty($data['is_active']) ? 1 : 0;

        if ($isActive) {
            $db->exec("UPDATE academic_sessions SET is_active = 0");
        }

        $stmt = $db->prepare("INSERT INTO academic_sessions (session_label, start_date, end_date, admission_open_date, admission_close_date, is_active, notes, created_by)
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $data['session_label'],
            $data['start_date'],
            $data['end_date'],
            $data['admission_open_date'] ?: null,
            $data['admission_close_date'] ?: null,
            $isActive,
            $data['notes'] ?: null,
            $data['created_by'] ?? null
        ]);

        $newId = (int)$db->lastInsertId();
        Logger::audit('session_create', 'academic_session', $newId, null, $data);
        return $newId;
    }

    public static function update(int $id, array $data): bool
    {
        $db = Database::getConnection();
        $old = self::find($id);

        $isActive = !empty($data['is_active']) ? 1 : 0;
        if ($isActive && empty($old['is_active'])) {
            $db->exec("UPDATE academic_sessions SET is_active = 0");
        }

        $stmt = $db->prepare("UPDATE academic_sessions 
                              SET session_label = ?, start_date = ?, end_date = ?, admission_open_date = ?, admission_close_date = ?, is_active = ?, notes = ?
                              WHERE id = ?");
        $success = $stmt->execute([
            $data['session_label'],
            $data['start_date'],
            $data['end_date'],
            $data['admission_open_date'] ?: null,
            $data['admission_close_date'] ?: null,
            $isActive,
            $data['notes'] ?: null,
            $id
        ]);

        if ($success) {
            Logger::audit('session_update', 'academic_session', $id, $old, $data);
        }
        return $success;
    }

    public static function archive(int $id): bool
    {
        $db = Database::getConnection();
        $session = self::find($id);
        if ($session && $session['is_active']) {
            return false; // Cannot archive active session
        }
        $stmt = $db->prepare("UPDATE academic_sessions SET is_archived = 1 WHERE id = ?");
        $res = $stmt->execute([$id]);
        Logger::audit('session_archive', 'academic_session', $id);
        return $res;
    }
}
