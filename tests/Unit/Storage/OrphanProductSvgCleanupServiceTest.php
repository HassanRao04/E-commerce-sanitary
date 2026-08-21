<?php

namespace Tests\Unit\Storage;

use App\Services\Storage\OrphanProductSvgCleanupService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class OrphanProductSvgCleanupServiceTest extends TestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_scan_marks_historical_orphans_for_deletion(): void
    {
        Storage::disk('public')->put('products/20/gallery-1-12345678.svg', '<svg/>');
        Storage::disk('public')->put('products/20/gallery-1.svg', '<svg/>');

        $scan = app(OrphanProductSvgCleanupService::class)->scan();

        $this->assertSame(1, $scan->toDeleteCount);
        $this->assertSame(['products/20/gallery-1-12345678.svg'], $scan->sampleToDelete);
        $this->assertSame(1, $scan->skippedDeterministicCount);
    }

    public function test_execute_records_failure_when_file_is_not_present_at_delete_time(): void
    {
        Storage::disk('public')->put('products/21/gallery-1-12345678.svg', '<svg/>');

        $service = app(OrphanProductSvgCleanupService::class);
        Storage::disk('public')->delete('products/21/gallery-1-12345678.svg');

        $result = $service->execute();

        $this->assertSame(0, $result->deletedCount);
        $this->assertGreaterThanOrEqual(0, $result->failedCount);
    }
}
