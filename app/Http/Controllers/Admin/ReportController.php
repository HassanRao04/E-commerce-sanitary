<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\ReportExportService;
use App\Services\Admin\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reportService,
        private readonly ReportExportService $exportService,
    ) {}

    public function index(): View
    {
        abort_unless(auth()->user()?->can('reports.view'), 403);

        return view('admin.reports.index', [
            'types' => $this->reportService->types()->groupBy('group'),
            'widgets' => $this->reportService->dashboardWidgets(),
        ]);
    }

    public function show(Request $request, string $type): View
    {
        abort_unless(auth()->user()?->can('reports.view'), 403);

        $meta = $this->reportService->type($type);
        [$from, $to] = $this->resolveRange($request, $type);

        $report = $this->reportService->build($type, $from, $to);

        return view('admin.reports.show', [
            'meta' => $meta,
            'report' => $report,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'tableHeaders' => $this->tableHeaders($type),
        ]);
    }

    public function export(Request $request, string $type, string $format): StreamedResponse
    {
        abort_unless(auth()->user()?->can('reports.view'), 403);

        $meta = $this->reportService->type($type);
        [$from, $to] = $this->resolveRange($request, $type);
        $report = $this->reportService->build($type, $from, $to);

        return match ($format) {
            'csv' => $this->exportService->csv($type, $meta, $report['rows']),
            'excel' => $this->exportService->excel($type, $meta, $report['rows']),
            'pdf' => $this->exportService->pdf($type, $meta, $report, $from->toDateString(), $to->toDateString()),
            default => abort(404),
        };
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function resolveRange(Request $request, string $type): array
    {
        $defaults = $this->reportService->defaultRange($type);

        if ($type === 'inventory') {
            return [$defaults['from'], $defaults['to']];
        }

        $from = Carbon::parse($request->input('from', $defaults['from']->toDateString()))->startOfDay();
        $to = Carbon::parse($request->input('to', $defaults['to']->toDateString()))->endOfDay();

        if ($from->greaterThan($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    /** @return array<int, string> */
    private function tableHeaders(string $type): array
    {
        return $this->reportService->exportHeaders($type);
    }
}
