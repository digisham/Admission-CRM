<?php
namespace App\Helpers;

class Sanitizer
{
    /**
     * Escape HTML output against XSS
     */
    public static function e(?string $string): string
    {
        return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Store phone numbers as strings without losing leading zeros
     */
    public static function cleanPhone(?string $phone): string
    {
        if ($phone === null) {
            return '';
        }
        // Remove spaces, hyphens, and brackets but preserve leading +, zeros, and digits
        return preg_replace('/[^\d+]/', '', trim($phone));
    }

    /**
     * Protect against CSV / Spreadsheet formula injection (=, +, -, @)
     */
    public static function cleanFormula(?string $value): string
    {
        if ($value === null) {
            return '';
        }
        $str = (string)$value;
        if (preg_match('/^[=+\-@\t\r]/', $str)) {
            return "'" . $str;
        }
        return $str;
    }
}
