<?php

namespace App\Services\Admin;

use App\Support\Exports\SimplePdfExporter;
use App\Support\Exports\SimpleXlsxExporter;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    public function __construct(private readonly ReportService $reportService) {}

    public function csv(string $type, array $meta, Collection $rows): StreamedResponse
    {
        $filename = $this->filename($type, 'csv');

        return response()->streamDownload(function () use ($type, $rows): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $this->reportService->exportHeaders($type));

            foreach ($this->reportService->exportRows($type, $rows) as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function excel(string $type, array $meta, Collection $rows): StreamedResponse
    {
        $headers = $this->reportService->exportHeaders($type);
        $data = $this->reportService->exportRows($type, $rows);

        if (class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            return $this->excelWithPhpSpreadsheet($type, $meta, $headers, $data);
        }

        $binary = (new SimpleXlsxExporter)->stream($headers, $data, $meta['label'] ?? 'Report');

        return response()->streamDownload(function () use ($binary): void {
            echo $binary;
        }, $this->filename($type, 'xlsx'), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function pdf(string $type, array $meta, array $report, string $from, string $to): StreamedResponse
    {
        $headers = $this->reportService->exportHeaders($type);
        $rows = $this->reportService->exportRows($type, $report['rows']);

        if (class_exists(\Dompdf\Dompdf::class)) {
            return $this->pdfWithDompdf($type, $meta, $report, $from, $to);
        }

        $subtitle = trim(($from && $to) ? "{$from} to {$to}" : '');
        $binary = (new SimplePdfExporter)->stream(
            $meta['label'] ?? 'Report',
            $headers,
            $rows,
            $subtitle !== '' ? $subtitle : null,
        );

        return response()->streamDownload(function () use ($binary): void {
            echo $binary;
        }, $this->filename($type, 'pdf'), [
            'Content-Type' => 'application/pdf',
        ]);
    }

    /** @param array<int, string> $headers @param array<int, array<int, mixed>> $rows */
    private function excelWithPhpSpreadsheet(string $type, array $meta, array $headers, array $rows): StreamedResponse
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(str($meta['label'] ?? 'Report')->limit(31)->value());

        foreach ($headers as $column => $header) {
            $sheet->setCellValue([$column + 1, 1], $header);
        }

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $column => $value) {
                $sheet->setCellValue([$column + 1, $rowIndex + 2], $value);
            }
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer): void {
            $writer->save('php://output');
        }, $this->filename($type, 'xlsx'), [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function pdfWithDompdf(string $type, array $meta, array $report, string $from, string $to): StreamedResponse
    {
        $html = view('admin.reports.exports.pdf', [
            'meta' => $meta,
            'report' => $report,
            'headers' => $this->reportService->exportHeaders($type),
            'rows' => $this->reportService->exportRows($type, $report['rows']),
            'from' => $from,
            'to' => $to,
        ])->render();

        $dompdf = new \Dompdf\Dompdf;

        if (class_exists(\Dompdf\Options::class)) {
            $options = new \Dompdf\Options;
            $options->set('isRemoteEnabled', false);
            $options->set('defaultFont', 'DejaVu Sans');
            $dompdf->setOptions($options);
        }

        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response()->streamDownload(function () use ($dompdf): void {
            echo $dompdf->output();
        }, $this->filename($type, 'pdf'), [
            'Content-Type' => 'application/pdf',
        ]);
    }

    private function filename(string $type, string $extension): string
    {
        return sprintf('%s-%s.%s', $type, now()->format('Y-m-d-His'), $extension);
    }
}
