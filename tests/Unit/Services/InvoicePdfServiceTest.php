<?php

namespace Tests\Unit\Services;

use App\Models\Invoice;
use App\Models\Order;
use App\Services\Admin\InvoicePdfService;
use App\Services\Admin\InvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class InvoicePdfServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('local');
    }

    public function test_it_renders_a_valid_pdf_from_existing_invoice_print_view(): void
    {
        $order = Order::query()->with('items')->first();
        $this->assertNotNull($order);

        $invoice = app(InvoiceService::class)->generateFromOrder($order);
        $pdf = app(InvoicePdfService::class)->render($invoice);

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertGreaterThan(1000, strlen($pdf));
    }

    public function test_it_stores_pdf_and_is_idempotent(): void
    {
        $order = Order::query()->with('items')->first();
        $this->assertNotNull($order);

        $invoice = app(InvoiceService::class)->generateFromOrder($order);
        $service = app(InvoicePdfService::class);

        $path = $service->store($invoice);
        $this->assertSame('invoices/'.$invoice->invoice_number.'.pdf', $path);
        Storage::disk('local')->assertExists($path);

        $storedContents = Storage::disk('local')->get($path);
        $pathAgain = $service->store($invoice->fresh());

        $this->assertSame($path, $pathAgain);
        $this->assertSame($storedContents, Storage::disk('local')->get($path));
    }

    public function test_ensure_pdf_populates_invoice_pdf_path(): void
    {
        $order = Order::query()->with('items')->first();
        $this->assertNotNull($order);

        $invoice = app(InvoiceService::class)->generateFromOrder($order);
        $this->assertNull($invoice->pdf_path);

        $invoice = app(InvoiceService::class)->ensurePdf($invoice);

        $this->assertNotNull($invoice->pdf_path);
        Storage::disk('local')->assertExists($invoice->pdf_path);
    }

    public function test_attachment_filename_uses_order_number(): void
    {
        $order = Order::query()->with('items')->first();
        $this->assertNotNull($order);

        $invoice = app(InvoiceService::class)->generateFromOrder($order);

        $filename = app(InvoicePdfService::class)->attachmentFilename($invoice);

        $this->assertSame('Invoice-'.$order->order_number.'.pdf', $filename);
    }
}
