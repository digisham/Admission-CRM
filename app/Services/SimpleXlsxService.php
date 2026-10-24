<?php
namespace App\Services;

use ZipArchive;
use SimpleXMLElement;

class SimpleXlsxService
{
    /**
     * Converts an Excel .xlsx spreadsheet file into a UTF-8 CSV file.
     * Uses PHP's built-in ZipArchive and SimpleXML without any third-party dependencies.
     *
     * @param string $xlsxPath Full path to source .xlsx file
     * @param string $csvPath Full path to target .csv destination
     * @return bool True on success, False on failure
     */
    public static function convertToCsv(string $xlsxPath, string $csvPath): bool
    {
        if (!file_exists($xlsxPath) || !is_readable($xlsxPath)) {
            return false;
        }

        $zip = new ZipArchive();
        if ($zip->open($xlsxPath) !== true) {
            return false;
        }

        // 1. Read shared strings if present
        $sharedStrings = [];
        $stringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($stringsXml !== false) {
            $xml = @simplexml_load_string($stringsXml);
            if ($xml && isset($xml->si)) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string)$si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $r) {
                            $text .= (string)$r->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Locate first worksheet (sheet1.xml or first matching sheet in workbook)
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (preg_match('#xl/worksheets/sheet\d+\.xml#i', $name)) {
                    $sheetXml = $zip->getFromIndex($i);
                    break;
                }
            }
        }

        $zip->close();

        if ($sheetXml === false) {
            return false;
        }

        $xml = @simplexml_load_string($sheetXml);
        if (!$xml || !isset($xml->sheetData)) {
            return false;
        }

        $csvHandle = fopen($csvPath, 'w');
        if (!$csvHandle) {
            return false;
        }

        // Output UTF-8 BOM
        fputs($csvHandle, "\xEF\xBB\xBF");

        // Helper to convert cell column letters (A, B... Z, AA, AB) to 0-based column index
        $colToIdx = function (string $col): int {
            $len = strlen($col);
            $num = 0;
            for ($i = 0; $i < $len; $i++) {
                $num = $num * 26 + (ord($col[$i]) - 64);
            }
            return $num - 1;
        };

        foreach ($xml->sheetData->row as $row) {
            $rowData = [];
            $currentCol = 0;

            foreach ($row->c as $cell) {
                $r = (string)$cell['r']; // e.g. "C2"
                preg_match('/^([A-Z]+)(\d+)$/', $r, $m);
                $colLetters = $m[1] ?? '';
                $targetCol = !empty($colLetters) ? $colToIdx($colLetters) : $currentCol;

                // Pad missing/empty columns before this cell to preserve exact column alignment
                while ($currentCol < $targetCol) {
                    $rowData[] = '';
                    $currentCol++;
                }

                $type = (string)$cell['t'];
                $val = (string)$cell->v;

                if ($type === 's') {
                    // Shared string lookup
                    $idx = (int)$val;
                    $cellValue = $sharedStrings[$idx] ?? '';
                } elseif ($type === 'inlineStr' && isset($cell->is->t)) {
                    $cellValue = (string)$cell->is->t;
                } elseif ($type === 'b') {
                    // Boolean
                    $cellValue = ($val === '1') ? 'TRUE' : 'FALSE';
                } else {
                    $cellValue = $val;
                }

                $rowData[] = trim($cellValue);
                $currentCol++;
            }

            // Only output rows that have at least one non-empty value
            $hasContent = false;
            foreach ($rowData as $val) {
                if ($val !== '') {
                    $hasContent = true;
                    break;
                }
            }

            if ($hasContent) {
                fputcsv($csvHandle, $rowData);
            }
        }

        fclose($csvHandle);
        return true;
    }
}
