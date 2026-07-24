<?php

namespace App\Mail;

use App\Models\Inquiry;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactInquiryMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Inquiry $inquiry,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'New Customer Inquiry',
            replyTo: [
                new Address($this->inquiry->email, $this->inquiry->name),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.contact-inquiry',
        );
    }
}
