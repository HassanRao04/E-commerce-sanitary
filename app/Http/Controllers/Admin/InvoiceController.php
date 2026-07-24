<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\Admin\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(private readonly InvoiceService $invoiceService) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Invoice::class);

        return view('admin.invoices.index', [
            'invoices' => $this->invoiceService->paginatedList($request->only('q', 'status', 'overdue')),
        ]);
    }

    public function show(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        $invoice->loadMissing(['order', 'items']);

        return view('admin.invoices.show', compact('invoice'));
    }

    public function issue(Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $this->invoiceService->issue($invoice);

        return back()->with('success', 'Invoice issued.');
    }

    public function markPaid(Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $this->invoiceService->markPaid($invoice);

        return back()->with('success', 'Invoice marked as paid.');
    }

    public function void(Invoice $invoice): RedirectResponse
    {
        $this->authorize('update', $invoice);

        $this->invoiceService->void($invoice);

        return back()->with('success', 'Invoice voided.');
    }
}
