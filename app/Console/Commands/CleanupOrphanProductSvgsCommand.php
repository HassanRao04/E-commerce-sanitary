<?php

namespace App\Console\Commands;

use App\Services\Storage\CleanupExecuteResult;
use App\Services\Storage\CleanupScanResult;
use App\Services\Storage\OrphanProductSvgCleanupService;
use Illuminate\Console\Command;

class CleanupOrphanProductSvgsCommand extends Command
{
    protected $signature = 'storage:cleanup-orphan-product-svgs
                            {--dry-run : Report only, do not delete files (default behaviour)}
                            {--execute : Delete confirmed orphan SVG files}';

    protected $description = 'Safely remove unreferenced historical orphan product SVG placeholders (dry-run by default)';

    public function __construct(
        private readonly OrphanProductSvgCleanupService $cleanup,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('execute') && $this->option('dry-run')) {
            $this->error('Cannot use --execute together with --dry-run.');

            return self::FAILURE;
        }

        $execute = (bool) $this->option('execute');
        $reportDir = storage_path('app/private/orphan-cleanup-'.now()->format('Y-m-d-His-u'));
        if (! is_dir($reportDir)) {
            mkdir($reportDir, 0755, true);
        }

        $this->info($execute ? 'Executing orphan SVG cleanup...' : 'Dry-run orphan SVG cleanup (no files will be deleted).');
        $this->line('Database: '.$this->cleanup->databaseName());
        $this->line('Products root: '.$this->cleanup->productsRoot());
        $this->line('Report dir: '.$reportDir);
        $this->newLine();

        $scan = $this->cleanup->scan(
            candidateListPath: $reportDir.'/deletion-candidates.txt',
            skippedReferencedPath: $reportDir.'/skipped-referenced.txt',
        );

        $this->renderDryRunReport($scan);

        $executeResult = null;
        if ($execute) {
            $this->newLine();
            $this->warn('Re-querying database and re-scanning before deletion...');
            $executeResult = $this->cleanup->execute(
                deletedListPath: $reportDir.'/deleted-files.txt',
                failedListPath: $reportDir.'/failed-files.txt',
            );
            $this->renderExecuteReport($executeResult);
        } else {
            touch($reportDir.'/failed-files.txt');
        }

        $summary = $scan->toSummaryArray($execute, $executeResult);
        $summary['report_dir'] = $reportDir;
        $summary['started_at'] = now()->toIso8601String();
        $summary['ended_at'] = now()->toIso8601String();

        file_put_contents(
            $reportDir.'/summary.json',
            json_encode($summary, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES),
        );

        $this->newLine();
        $this->info('Report written to: '.$reportDir);

        if (! $execute) {
            $this->comment('Dry-run complete. No files were deleted.');
            $this->comment('To delete, run: php artisan storage:cleanup-orphan-product-svgs --execute');
        }

        return self::SUCCESS;
    }

    private function renderDryRunReport(CleanupScanResult $scan): void
    {
        $this->table(
            ['Metric', 'Value'],
            [
                ['DB allow-list paths', number_format($scan->allowListCount)],
                ['SVG files scanned (under products/)', number_format($scan->svgCandidates)],
                ['SVG candidate bytes', $this->formatBytes($scan->svgCandidateBytes)],
                ['Skipped (non-SVG)', number_format($scan->skippedNonSvg)],
                ['Skipped (unsafe path)', number_format($scan->skippedUnsafePath)],
                ['Skipped (referenced by DB)', number_format($scan->skippedReferencedCount)],
                ['Skipped (deterministic, unreferenced)', number_format($scan->skippedDeterministicCount)],
                ['Skipped (non-matching orphan pattern)', number_format($scan->skippedNonMatchingPatternCount)],
                ['Will delete', number_format($scan->toDeleteCount)],
                ['Will delete bytes', $this->formatBytes($scan->toDeleteBytes)],
            ],
        );

        $this->newLine();
        $this->line('<fg=yellow>Sample deletion candidates (first 20):</>');
        foreach ($scan->sampleToDelete as $path) {
            $this->line('  - '.$path);
        }

        $this->newLine();
        $this->line('<fg=green>Sample preserved files (first 20):</>');
        foreach ($scan->samplePreserved as $path) {
            $this->line('  - '.$path);
        }
    }

    private function renderExecuteReport(CleanupExecuteResult $result): void
    {
        $this->table(
            ['Execute metric', 'Value'],
            [
                ['Candidates processed', number_format($result->candidateCount)],
                ['Deleted', number_format($result->deletedCount)],
                ['Deleted bytes', $this->formatBytes($result->deletedBytes)],
                ['Skipped (became referenced)', number_format($result->skippedReferencedDuringExecute)],
                ['Failed', number_format($result->failedCount)],
            ],
        );
    }

    private function formatBytes(int $bytes): string
    {
        return number_format($bytes).' bytes (~'.number_format($bytes / 1024 / 1024, 2).' MB)';
    }
}
