<?php

namespace Tests\Feature\Storefront;

use App\Enums\InquiryStatus;
use App\Mail\ContactInquiryMail;
use App\Models\Inquiry;
use App\Models\Notification;
use App\Models\SiteSetting;
use App\Models\User;
use App\Support\SocialLinks;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactWorkflowVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        SiteSetting::current()->update([
            'email' => 'inayatsanitaryhouse@gmail.com',
            'support_email' => 'inayatsanitaryhouse@gmail.com',
            'whatsapp' => '+92-331-4324807',
            'contact_form_enabled' => true,
            'email_notifications_enabled' => true,
            'whatsapp_notifications_enabled' => true,
        ]);
    }

    public function test_contact_form_validation_requires_core_fields(): void
    {
        Mail::fake();

        $this->from(route('shop.contact'))
            ->post(route('shop.contact.store'), [])
            ->assertRedirect(route('shop.contact'))
            ->assertSessionHasErrors(['name', 'email', 'subject', 'message']);

        $this->assertDatabaseCount('inquiries', 0);
        Mail::assertNothingSent();
    }

    public function test_contact_form_rejects_invalid_email(): void
    {
        Mail::fake();

        $this->from(route('shop.contact'))
            ->post(route('shop.contact.store'), [
                'name' => 'Jane Doe',
                'email' => 'not-an-email',
                'subject' => 'Help',
                'message' => 'Need assistance',
            ])
            ->assertSessionHasErrors(['email']);

        Mail::assertNothingSent();
    }

    public function test_complete_contact_workflow_is_production_ready(): void
    {
        Mail::fake();

        $payload = [
            'name' => 'Hassan Ali',
            'email' => 'customer@example.com',
            'phone' => '03001234567',
            'subject' => 'Basin mixer quote',
            'message' => 'Please share price for chrome basin mixer.',
        ];

        $response = $this->post(route('shop.contact.store'), $payload);

        $response->assertRedirect(route('shop.contact.success'))
            ->assertSessionHas('contact_success');

        $inquiry = Inquiry::query()->first();
        $this->assertNotNull($inquiry);
        $this->assertSame('contact', $inquiry->type);
        $this->assertSame(Inquiry::SOURCE_CONTACT_FORM, $inquiry->source);
        $this->assertNotNull($inquiry->ip_address);
        $this->assertSame(InquiryStatus::New, $inquiry->status);
        $this->assertSame($payload['name'], $inquiry->name);
        $this->assertSame($payload['email'], $inquiry->email);
        $this->assertSame($payload['phone'], $inquiry->phone);
        $this->assertSame($payload['subject'], $inquiry->subject);
        $this->assertSame($payload['message'], $inquiry->message);

        Mail::assertSent(ContactInquiryMail::class, function (ContactInquiryMail $mail) use ($inquiry): bool {
            $html = $mail->render();

            return $mail->hasTo('inayatsanitaryhouse@gmail.com')
                && $mail->envelope()->subject === 'New Customer Inquiry'
                && $mail->hasReplyTo($inquiry->email)
                && str_contains($html, 'Inquiry ID:')
                && str_contains($html, $inquiry->referenceId())
                && str_contains($html, 'customer@example.com')
                && str_contains($html, '03001234567')
                && str_contains($html, 'Basin mixer quote')
                && str_contains($html, 'Please share price for chrome basin mixer.')
                && str_contains($html, 'Phone Number')
                && str_contains($html, 'Submission Date');
        });

        $success = session('contact_success');
        $this->assertSame($inquiry->referenceId(), $success['reference_id']);
        $whatsappUrl = $success['whatsapp_url'];
        $this->assertIsString($whatsappUrl);
        $this->assertStringStartsWith('https://wa.me/923314324807?', $whatsappUrl);
        $this->assertStringContainsString(rawurlencode('Inquiry ID: '.$inquiry->referenceId()), $whatsappUrl);
        $this->assertStringContainsString(rawurlencode('Customer Name: Hassan Ali'), $whatsappUrl);
        $this->assertStringContainsString(rawurlencode('Phone Number: 03001234567'), $whatsappUrl);
        $this->assertStringContainsString(rawurlencode('Email: customer@example.com'), $whatsappUrl);
        $this->assertStringContainsString(rawurlencode('Subject: Basin mixer quote'), $whatsappUrl);
        $this->assertStringContainsString(rawurlencode('Please share price for chrome basin mixer.'), $whatsappUrl);

        $expected = SocialLinks::whatsappUrl(
            "New Customer Inquiry\n\nInquiry ID: {$inquiry->referenceId()}\nCustomer Name: Hassan Ali\nPhone Number: 03001234567\nEmail: customer@example.com\nSubject: Basin mixer quote\n\nMessage:\nPlease share price for chrome basin mixer."
        );
        $this->assertSame($expected, $whatsappUrl);

        $this->assertDatabaseHas('notifications', [
            'type' => 'admin.inquiry_received',
            'title' => 'New Customer Inquiry Received',
        ]);

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->get(route('admin.inquiries.index'))
            ->assertOk()
            ->assertSee('Hassan Ali')
            ->assertSee('Basin mixer quote')
            ->assertSee('03001234567')
            ->assertSee('New');

        $this->actingAs($admin)
            ->get(route('admin.inquiries.show', $inquiry))
            ->assertOk()
            ->assertSee('Hassan Ali')
            ->assertSee('customer@example.com')
            ->assertSee('Please share price for chrome basin mixer.');

        $this->assertSame(InquiryStatus::New, $inquiry->fresh()->status);
    }
}
