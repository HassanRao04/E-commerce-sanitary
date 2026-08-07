<?php

namespace App\Mail;

use App\Models\Invoice;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class OrderConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param  array<string, mixed>  $presentation */
    public function __construct(
        public array $presentation,
        public Invoice $invoice,
    ) {}

    public function envelope(): Envelope
    {
        $orderNumber = $this->presentation['orderNumber'] ?? 'your order';

        return new Envelope(
            subject: 'Order Confirmation — '.$orderNumber,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.order-confirmation',
            with: [
                'data' => $this->presentation,
            ],
        );
    }

    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        if (blank($this->invoice->pdf_path) || ! Storage::disk('local')->exists($this->invoice->pdf_path)) {
            return [];
        }

        return [
            Attachment::fromStorageDisk('local', $this->invoice->pdf_path)
                ->as('Invoice-'.$this->presentation['orderNumber'].'.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
