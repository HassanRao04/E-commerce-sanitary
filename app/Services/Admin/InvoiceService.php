<?php

namespace App\Services\Admin;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Order;
use App\Repositories\Contracts\InvoiceRepositoryInterface;
use App\Services\ActivityLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class InvoiceService
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoices,
        private readonly ActivityLogService $activityLog,
        private readonly InvoicePdfService $invoicePdf,
    ) {}

    public function paginatedList(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->invoices->search($filters['q'] ?? null, $filters, $perPage);
    }

    public function generateFromOrder(Order $order): Invoice
    {
        if ($order->invoice) {
            return $order->invoice;
        }

        return DB::transaction(function () use ($order) {
            $order->load('items');

            $invoice = Invoice::create([
                'order_id' => $order->id,
                'invoice_number' => $this->nextInvoiceNumber(),
                'status' => InvoiceStatus::Draft,
                'subtotal' => $order->subtotal,
                'tax_total' => $order->tax_total,
                'discount_total' => $order->discount_total,
                'offer_discount_total' => $order->offer_discount_total,
                'shipping_total' => $order->shipping_total,
                'shipping_discount_total' => $order->shipping_discount_total,
                'total' => $order->grand_total,
                'billing_name' => $order->customer_name,
                'billing_email' => $order->customer_email,
                'billing_address' => $this->formatAddress($order),
            ]);

            foreach ($order->items as $item) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'product_name' => $item->product_name,
                    'variant_name' => $item->variant_name,
                    'selected_offer' => $item->selected_offer,
                    'option_title' => $item->option_title,
                    'sku' => $item->sku,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'original_unit_price' => $item->original_unit_price,
                    'discount_percent' => $item->discount_percent,
                    'discount_amount' => $item->discount_amount,
                    'pipe_length' => $item->pipe_length,
                    'pipe_extra_cost' => $item->pipe_extra_cost,
                    'total' => $item->total,
                ]);
            }

            $this->activityLog->log('invoice.generated', $invoice);

            return $invoice->fresh('items');
        });
    }

    public function issue(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            $invoice->update([
                'status' => InvoiceStatus::Issued,
                'issued_at' => now(),
                'due_at' => now()->addDays(14),
            ]);

            $this->activityLog->log('invoice.issued', $invoice);

            return $invoice->fresh();
        });
    }

    public function markPaid(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            $invoice->update([
                'status' => InvoiceStatus::Paid,
                'paid_at' => now(),
            ]);

            if ($invoice->order) {
                $invoice->order->update(['payment_status' => PaymentStatus::Paid]);
            }

            $this->activityLog->log('invoice.paid', $invoice);

            return $invoice->fresh();
        });
    }

    public function void(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice) {
            $invoice->update(['status' => InvoiceStatus::Void]);
            $this->activityLog->log('invoice.voided', $invoice);

            return $invoice->fresh();
        });
    }

    public function ensurePdf(Invoice $invoice): Invoice
    {
        $invoice->loadMissing(['items', 'order']);

        if (filled($invoice->pdf_path) && Storage::disk('local')->exists($invoice->pdf_path)) {
            return $invoice;
        }

        $path = $this->invoicePdf->store($invoice);

        if ($invoice->pdf_path !== $path) {
            $invoice->update(['pdf_path' => $path]);
        }

        return $invoice->fresh();
    }

    private function nextInvoiceNumber(): string
    {
        $latest = Invoice::query()->latest('id')->value('invoice_number');
        $sequence = $latest ? ((int) Str::afterLast($latest, '-')) + 1 : 1;

        return 'INV-'.now()->format('Ymd').'-'.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    private function formatAddress(Order $order): ?string
    {
        $address = $order->billingAddress;

        if (! $address) {
            return null;
        }

        return collect([
            $address->line1,
            $address->line2,
            $address->city,
            $address->state,
            $address->postal_code,
            $address->country,
        ])->filter()->implode(', ');
    }
}
