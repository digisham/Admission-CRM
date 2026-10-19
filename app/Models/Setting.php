<?php
namespace App\Models;

use App\Config\Database;
use App\Helpers\Logger;
use PDO;

class Setting
{
    private static array $cache = [];

    public static function get(string $key, mixed $default = null): mixed
    {
        if (isset(self::$cache[$key])) {
            return self::$cache[$key];
        }

        try {
            $db = Database::getConnection();
            $stmt = $db->prepare("SELECT setting_value FROM school_settings WHERE setting_key = ? LIMIT 1");
            $stmt->execute([$key]);
            $val = $stmt->fetchColumn();
            if ($val !== false) {
                // Try JSON decode
                $json = json_decode($val, true);
                $decoded = (json_last_error() === JSON_ERROR_NONE) ? $json : $val;
                self::$cache[$key] = $decoded;
                return $decoded;
            }
        } catch (\Throwable) {
            // DB fallback
        }

        return $default;
    }

    public static function set(string $key, mixed $value, string $group = 'general'): bool
    {
        $db = Database::getConnection();
        $strValue = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (string)$value;

        $stmt = $db->prepare("INSERT INTO school_settings (setting_key, setting_value, setting_group) 
                              VALUES (?, ?, ?) 
                              ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), setting_group = VALUES(setting_group)");
        $res = $stmt->execute([$key, $strValue, $group]);
        self::$cache[$key] = $value;

        Logger::audit('setting_update', 'school_setting', null, null, ['key' => $key]);
        return $res;
    }

    public static function all(): array
    {
        $db = Database::getConnection();
        $rows = $db->query("SELECT * FROM school_settings ORDER BY setting_group ASC, setting_key ASC")->fetchAll();
        $result = [];
        foreach ($rows as $r) {
            $json = json_decode($r['setting_value'], true);
            $result[$r['setting_key']] = (json_last_error() === JSON_ERROR_NONE) ? $json : $r['setting_value'];
        }
        return $result;
    }

    public static function getLeadSources(): array
    {
        return self::get('lead_sources', [
            'Website', 'Phone Call', 'Walk-in', 'Referral', 'Social Media',
            'Advertisement', 'School Event', 'Existing Parent', 'Other'
        ]);
    }

    public static function getLeadStatuses(): array
    {
        return self::get('lead_statuses', [
            'New', 'Assigned', 'Contact Attempted', 'Contacted', 'Interested',
            'Follow-up Required', 'School Visit Scheduled', 'School Visit Completed',
            'Application In Progress', 'Admission Confirmed', 'Not Interested', 'Lost / Unreachable'
        ]);
    }

    public static function getClassesOffered(): array
    {
        return self::get('classes_offered', [
            'Pre-Nursery', 'Nursery', 'LKG', 'UKG', 'Grade 1', 'Grade 2', 'Grade 3',
            'Grade 4', 'Grade 5', 'Grade 6', 'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10',
            'Grade 11 - Science', 'Grade 11 - Commerce', 'Grade 11 - Humanities',
            'Grade 12 - Science', 'Grade 12 - Commerce', 'Grade 12 - Humanities'
        ]);
    }
}
