<?php

namespace App\Support\Exports;

use ZipArchive;

class SimpleXlsxExporter
{
    /** @param array<int, string> $headers @param array<int, array<int, mixed>> $rows */
    public function stream(array $headers, array $rows, string $sheetTitle = 'Report'): string
    {
        $sheetTitle = $this->sanitizeSheetTitle($sheetTitle);
        $sheetXml = $this->buildSheetXml($headers, $rows);
        $path = tempnam(sys_get_temp_dir(), 'report-xlsx-');
        $zipPath = $path.'.xlsx';
        rename($path, $zipPath);

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', $this->contentTypesXml());
        $zip->addFromString('_rels/.rels', $this->rootRelsXml());
        $zip->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelsXml());
        $zip->addFromString('xl/workbook.xml', $this->workbookXml($sheetTitle));
        $zip->addFromString('xl/styles.xml', $this->stylesXml());
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();

        $binary = file_get_contents($zipPath) ?: '';
        @unlink($zipPath);

        return $binary;
    }

    /** @param array<int, string> $headers @param array<int, array<int, mixed>> $rows */
    private function buildSheetXml(array $headers, array $rows): string
    {
        $lines = ['<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'];
        $lines[] = '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        $lines[] = '<sheetData>';

        $lines[] = $this->rowXml(1, $headers);

        foreach ($rows as $index => $row) {
            $lines[] = $this->rowXml($index + 2, $row);
        }

        $lines[] = '</sheetData>';
        $lines[] = '</worksheet>';

        return implode('', $lines);
    }

    /** @param array<int, mixed> $values */
    private function rowXml(int $rowNumber, array $values): string
    {
        $cells = [];

        foreach (array_values($values) as $index => $value) {
            $column = $this->columnLetter($index + 1);
            $cellRef = $column.$rowNumber;
            $cells[] = is_numeric($value)
                ? '<c r="'.$cellRef.'"><v>'.htmlspecialchars((string) $value, ENT_XML1).'</v></c>'
                : '<c r="'.$cellRef.'" t="inlineStr"><is><t>'.htmlspecialchars((string) $value, ENT_XML1).'</t></is></c>';
        }

        return '<row r="'.$rowNumber.'">'.implode('', $cells).'</row>';
    }

    private function columnLetter(int $index): string
    {
        $letter = '';

        while ($index > 0) {
            $mod = ($index - 1) % 26;
            $letter = chr(65 + $mod).$letter;
            $index = intdiv($index - 1, 26);
        }

        return $letter;
    }

    private function sanitizeSheetTitle(string $title): string
    {
        return substr(preg_replace('/[\\\\\\/?*\\[\\]:]/', '', $title) ?: 'Report', 0, 31);
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private function workbookXml(string $sheetTitle): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="'.htmlspecialchars($sheetTitle, ENT_XML1).'" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"/>';
    }
}
