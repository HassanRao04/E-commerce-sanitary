<?php

namespace App\Http\Controllers\Storefront;

use App\Enums\InquiryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\ContactRequest;
use App\Mail\ContactInquiryMail;
use App\Models\Inquiry;
use App\Models\Page;
use App\Models\SiteSetting;
use App\Services\Admin\InquiryNotificationService;
use App\Support\SocialLinks;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class PageController extends Controller
{
    public function __construct(
        private readonly InquiryNotificationService $inquiryNotifications,
    ) {}

    public function about(): View
    {
        $page = Page::query()
            ->where('slug', 'about')
            ->where('is_published', true)
            ->first();

        return view('storefront.pages.about', compact('page'));
    }

    public function contact(): View
    {
        return view('storefront.pages.contact', [
            'contactFormEnabled' => SiteSetting::current()->isContactFormEnabled(),
        ]);
    }

    public function contactSuccess(Request $request): View|RedirectResponse
    {
        $success = $request->session()->get('contact_success');

        if (! is_array($success) || blank($success['reference_id'] ?? null)) {
            return redirect()->route('shop.contact');
        }

        return view('storefront.pages.contact-success', [
            'referenceId' => $success['reference_id'],
            'whatsappUrl' => $success['whatsapp_url'] ?? null,
        ]);
    }

    public function storeContact(ContactRequest $request): RedirectResponse
    {
        $settings = SiteSetting::current();

        if (! $settings->isContactFormEnabled()) {
            return redirect()
                ->route('shop.contact')
                ->with('error', 'The contact form is currently unavailable. Please reach us by phone or email.');
        }

        $inquiry = Inquiry::query()->create([
            ...$request->validated(),
            'type' => 'contact',
            'source' => Inquiry::SOURCE_CONTACT_FORM,
            'ip_address' => $request->ip(),
            'status' => InquiryStatus::New,
        ]);

        $this->inquiryNotifications->notifyStaff($inquiry);

        if ($settings->areEmailNotificationsEnabled()) {
            $recipient = $settings->inquiryNotificationEmail();

            try {
                Mail::to($recipient)->send(new ContactInquiryMail($inquiry));
            } catch (\Throwable $exception) {
                Log::error('Failed to send contact inquiry email.', [
                    'inquiry_id' => $inquiry->id,
                    'recipient' => $recipient,
                    'message' => $exception->getMessage(),
                ]);
            }
        }

        $whatsappUrl = null;

        if ($settings->areWhatsappNotificationsEnabled()) {
            $whatsappUrl = SocialLinks::whatsappUrl($this->whatsappMessage($inquiry), $settings);
        }

        return redirect()
            ->route('shop.contact.success')
            ->with('contact_success', [
                'reference_id' => $inquiry->referenceId(),
                'whatsapp_url' => $whatsappUrl,
            ]);
    }

    private function whatsappMessage(Inquiry $inquiry): string
    {
        $phone = filled($inquiry->phone) ? $inquiry->phone : 'Not provided';

        return implode("\n", [
            'New Customer Inquiry',
            '',
            'Inquiry ID: '.$inquiry->referenceId(),
            'Customer Name: '.$inquiry->name,
            'Phone Number: '.$phone,
            'Email: '.$inquiry->email,
            'Subject: '.$inquiry->subject,
            '',
            'Message:',
            $inquiry->message,
        ]);
    }
}
