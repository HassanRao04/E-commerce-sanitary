<?php

namespace App\Support\Exports;

class SimplePdfExporter
{
    /** @param array<int, string> $headers @param array<int, array<int, mixed>> $rows */
    public function stream(string $title, array $headers, array $rows, ?string $subtitle = null): string
    {
        $contentLines = [];
        $y = 780;

        $contentLines[] = $this->text(40, $y, 16, $title);
        $y -= 24;

        if ($subtitle) {
            $contentLines[] = $this->text(40, $y, 10, $subtitle);
            $y -= 28;
        }

        $xPositions = $this->xPositions(count($headers));

        foreach ($headers as $index => $header) {
            $contentLines[] = $this->text($xPositions[$index], $y, 10, (string) $header);
        }

        $y -= 18;

        foreach ($rows as $row) {
            if ($y < 40) {
                break;
            }

            foreach (array_values($row) as $index => $cell) {
                $contentLines[] = $this->text($xPositions[$index] ?? 40, $y, 9, $this->stringify($cell));
            }

            $y -= 14;
        }

        $stream = implode("\n", $contentLines);

        $objects = [];
        $objects[] = '1 0 obj<< /Type /Catalog /Pages 2 0 R >>endobj';
        $objects[] = '2 0 obj<< /Type /Pages /Kids [3 0 R] /Count 1 >>endobj';
        $objects[] = '3 0 obj<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>endobj';
        $objects[] = '4 0 obj<< /Length '.strlen($stream)." >>stream\n{$stream}\nendstream endobj";
        $objects[] = '5 0 obj<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>endobj';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object."\n";
        }

        $xrefPos = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= 'trailer<< /Size '.(count($objects) + 1).' /Root 1 0 R >>'."\n";
        $pdf .= 'startxref '.$xrefPos."\n%%EOF";

        return $pdf;
    }

    private function text(int $x, int $y, int $size, string $text): string
    {
        $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], mb_substr($text, 0, 80));

        return "BT /F1 {$size} Tf {$x} {$y} Td ({$escaped}) Tj ET";
    }

    private function stringify(mixed $value): string
    {
        if (is_float($value)) {
            return number_format($value, 2);
        }

        return (string) $value;
    }

    /** @return array<int, int> */
    private function xPositions(int $count): array
    {
        $width = (int) floor(760 / max(1, $count));
        $positions = [];
        $x = 40;

        for ($i = 0; $i < $count; $i++) {
            $positions[] = $x;
            $x += $width;
        }

        return $positions;
    }
}
