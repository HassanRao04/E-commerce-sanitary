<?php

namespace App\Services\Admin;

use App\Models\Invoice;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Storage;

class InvoicePdfService
{
    public function render(Invoice $invoice): string
    {
        $invoice->loadMissing(['items', 'order']);

        $html = view('admin.invoices.print', [
            'invoice' => $invoice,
            'order' => $invoice->order,
        ])->render();

        $html = str_replace(
            '</head>',
            '<style>.no-print{display:none!important;}</style></head>',
            $html,
        );

        $dompdf = new Dompdf($this->dompdfOptions());
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }

    public function store(Invoice $invoice): string
    {
        $relativePath = $this->storagePath($invoice);

        if (! Storage::disk('local')->exists($relativePath)) {
            Storage::disk('local')->put($relativePath, $this->render($invoice));
        }

        return $relativePath;
    }

    public function attachmentFilename(Invoice $invoice): string
    {
        $invoice->loadMissing('order');

        $orderNumber = $invoice->order?->order_number ?? $invoice->invoice_number;

        return 'Invoice-'.$orderNumber.'.pdf';
    }

    private function storagePath(Invoice $invoice): string
    {
        return 'invoices/'.$invoice->invoice_number.'.pdf';
    }

    private function dompdfOptions(): Options
    {
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        return $options;
    }
}
