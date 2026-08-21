<?php

namespace App\Services\Storage;

use App\Models\ProductImage;
use App\Support\Storage\ProductImagePathNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class OrphanProductSvgCleanupService
{
    public function publicRoot(): string
    {
        return Storage::disk('public')->path('');
    }

    public function productsRoot(): string
    {
        return Storage::disk('public')->path('products');
    }

    /**
     * @return array<string, true>
     */
    public function buildAllowList(): array
    {
        $allowList = [];

        ProductImage::query()
            ->pluck('image_path')
            ->each(function (mixed $raw) use (&$allowList): void {
                $normalized = ProductImagePathNormalizer::normalize((string) $raw);
                if ($normalized !== null) {
                    $allowList[$normalized] = true;
                }
            });

        return $allowList;
    }

    public function databaseName(): string
    {
        return (string) DB::connection()->getDatabaseName();
    }

    public function scan(?string $candidateListPath = null, ?string $skippedReferencedPath = null): CleanupScanResult
    {
        $allowList = $this->buildAllowList();

        $result = new CleanupScanResult(
            databaseName: $this->databaseName(),
            allowListCount: count($allowList),
        );

        $candidateHandle = $candidateListPath ? fopen($candidateListPath, 'wb') : null;
        $skippedReferencedHandle = $skippedReferencedPath ? fopen($skippedReferencedPath, 'wb') : null;

        try {
            if (! is_dir($this->productsRoot())) {
                return $result;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->productsRoot(), \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS)
            );

            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $absolutePath = $file->getPathname();
                $extension = strtolower($file->getExtension());

                if ($extension !== 'svg') {
                    $result->skippedNonSvg++;
                    continue;
                }

                $result->svgCandidates++;

                $relativePath = ProductImagePathNormalizer::relativePublicDiskPath($absolutePath, $this->publicRoot());
                $normalized = ProductImagePathNormalizer::normalize($relativePath ?? '');
                if ($normalized === null || ! ProductImagePathNormalizer::isUnderProductsDirectory($normalized)) {
                    $result->skippedUnsafePath++;
                    continue;
                }

                if (! $this->isSafeAbsolutePath($absolutePath, $this->productsRoot())) {
                    $result->skippedUnsafePath++;
                    continue;
                }

                $bytes = $file->getSize();
                $result->svgCandidateBytes += $bytes;

                if (isset($allowList[$normalized])) {
                    $result->skippedReferencedCount++;
                    if ($skippedReferencedHandle) {
                        fwrite($skippedReferencedHandle, $normalized.PHP_EOL);
                    }
                    if (count($result->samplePreserved) < 20) {
                        $result->samplePreserved[] = $normalized;
                    }
                    continue;
                }

                if (ProductImagePathNormalizer::isDeterministicPattern($normalized)) {
                    $result->skippedDeterministicCount++;
                    if (count($result->samplePreserved) < 20) {
                        $result->samplePreserved[] = $normalized;
                    }
                    continue;
                }

                if (! ProductImagePathNormalizer::isHistoricalOrphanPattern($normalized)) {
                    $result->skippedNonMatchingPatternCount++;
                    continue;
                }

                $result->toDeleteCount++;
                $result->toDeleteBytes += $bytes;

                if (count($result->sampleToDelete) < 20) {
                    $result->sampleToDelete[] = $normalized;
                }

                if ($candidateHandle) {
                    fwrite($candidateHandle, $normalized.PHP_EOL);
                }
            }
        } finally {
            if (is_resource($candidateHandle)) {
                fclose($candidateHandle);
            }
            if (is_resource($skippedReferencedHandle)) {
                fclose($skippedReferencedHandle);
            }
        }

        return $result;
    }

    public function execute(?string $deletedListPath = null, ?string $failedListPath = null): CleanupExecuteResult
    {
        $startedAt = now();
        $allowList = $this->buildAllowList();
        $disk = Storage::disk('public');

        $executeResult = new CleanupExecuteResult(
            databaseName: $this->databaseName(),
            startedAt: $startedAt,
            allowListCount: count($allowList),
        );

        $deletedHandle = $deletedListPath ? fopen($deletedListPath, 'wb') : null;
        $failedHandle = $failedListPath ? fopen($failedListPath, 'wb') : null;

        try {
            if (! is_dir($this->productsRoot())) {
                $executeResult->endedAt = now();

                return $executeResult;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($this->productsRoot(), \FilesystemIterator::SKIP_DOTS | \FilesystemIterator::FOLLOW_SYMLINKS)
            );

            foreach ($iterator as $file) {
                if (! $file->isFile() || strtolower($file->getExtension()) !== 'svg') {
                    continue;
                }

                $absolutePath = $file->getPathname();
                $relativePath = ProductImagePathNormalizer::relativePublicDiskPath($absolutePath, $this->publicRoot());
                $normalized = ProductImagePathNormalizer::normalize($relativePath ?? '');

                if ($normalized === null
                    || ! ProductImagePathNormalizer::isUnderProductsDirectory($normalized)
                    || ! $this->isSafeAbsolutePath($absolutePath, $this->productsRoot())
                    || isset($allowList[$normalized])
                    || ProductImagePathNormalizer::isDeterministicPattern($normalized)
                    || ! ProductImagePathNormalizer::isHistoricalOrphanPattern($normalized)) {
                    continue;
                }

                $executeResult->candidateCount++;

                if (isset($allowList[$normalized])) {
                    $executeResult->skippedReferencedDuringExecute++;
                    continue;
                }

                if (! $disk->exists($normalized) || ! $this->isSafeAbsolutePath($absolutePath, $this->productsRoot())) {
                    $executeResult->failedCount++;
                    $reason = 'File missing or unsafe path at deletion time.';
                    if ($failedHandle) {
                        fwrite($failedHandle, $normalized.' | '.$reason.PHP_EOL);
                    }
                    continue;
                }

                $bytes = $file->getSize();

                if (! $disk->delete($normalized)) {
                    $executeResult->failedCount++;
                    $reason = 'delete failed.';
                    if ($failedHandle) {
                        fwrite($failedHandle, $normalized.' | '.$reason.PHP_EOL);
                    }
                    continue;
                }

                $executeResult->deletedCount++;
                $executeResult->deletedBytes += $bytes;

                if ($deletedHandle) {
                    fwrite($deletedHandle, $normalized.PHP_EOL);
                }
            }
        } finally {
            if (is_resource($deletedHandle)) {
                fclose($deletedHandle);
            }
            if (is_resource($failedHandle)) {
                fclose($failedHandle);
            }
        }

        $executeResult->endedAt = now();

        return $executeResult;
    }

    public function isSafeAbsolutePath(string $absolutePath, string $productsRoot): bool
    {
        $realProducts = realpath($productsRoot);
        $realFile = realpath($absolutePath);

        if ($realProducts === false || $realFile === false || ! is_file($realFile)) {
            return false;
        }

        $prefix = $realProducts.DIRECTORY_SEPARATOR;

        return str_starts_with($realFile, $prefix);
    }
}

final class CleanupScanResult
{
    public int $svgCandidates = 0;

    public int $svgCandidateBytes = 0;

    public int $skippedNonSvg = 0;

    public int $skippedUnsafePath = 0;

    public int $skippedReferencedCount = 0;

    public int $skippedDeterministicCount = 0;

    public int $skippedNonMatchingPatternCount = 0;

    public int $toDeleteCount = 0;

    public int $toDeleteBytes = 0;

    /** @var list<string> */
    public array $sampleToDelete = [];

    /** @var list<string> */
    public array $samplePreserved = [];

    public function __construct(
        public readonly string $databaseName,
        public readonly int $allowListCount,
    ) {}

    public function toSummaryArray(bool $executeMode, ?CleanupExecuteResult $executeResult = null): array
    {
        $summary = [
            'mode' => $executeMode ? 'execute' : 'dry-run',
            'database' => $this->databaseName,
            'allow_list_count' => $this->allowListCount,
            'svg_candidates_scanned' => $this->svgCandidates,
            'svg_candidate_bytes' => $this->svgCandidateBytes,
            'skipped_non_svg' => $this->skippedNonSvg,
            'skipped_unsafe_path' => $this->skippedUnsafePath,
            'skipped_referenced_by_db' => $this->skippedReferencedCount,
            'skipped_deterministic_unreferenced' => $this->skippedDeterministicCount,
            'skipped_non_matching_pattern' => $this->skippedNonMatchingPatternCount,
            'to_delete_count' => $this->toDeleteCount,
            'to_delete_bytes' => $this->toDeleteBytes,
            'sample_deletion_candidates' => $this->sampleToDelete,
            'sample_preserved' => $this->samplePreserved,
        ];

        if ($executeResult !== null) {
            $summary['execute'] = $executeResult->toSummaryArray();
        }

        return $summary;
    }
}

final class CleanupExecuteResult
{
    public int $candidateCount = 0;

    public int $deletedCount = 0;

    public int $deletedBytes = 0;

    public int $skippedReferencedDuringExecute = 0;

    public int $failedCount = 0;

    public function __construct(
        public readonly string $databaseName,
        public readonly \Illuminate\Support\Carbon $startedAt,
        public readonly int $allowListCount,
        public ?\Illuminate\Support\Carbon $endedAt = null,
    ) {}

    public function toSummaryArray(): array
    {
        return [
            'started_at' => $this->startedAt->toIso8601String(),
            'ended_at' => $this->endedAt?->toIso8601String(),
            'database' => $this->databaseName,
            'allow_list_count' => $this->allowListCount,
            'candidate_count' => $this->candidateCount,
            'deleted_count' => $this->deletedCount,
            'deleted_bytes' => $this->deletedBytes,
            'skipped_referenced_during_execute' => $this->skippedReferencedDuringExecute,
            'failed_count' => $this->failedCount,
        ];
    }
}
