<?php

namespace Tests\Feature\Admin;

use App\Models\Inquiry;
use App\Models\Notification;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class InquiryDashboardNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        Mail::fake();

        SiteSetting::current()->update([
            'contact_form_enabled' => true,
            'email_notifications_enabled' => true,
            'whatsapp_notifications_enabled' => false,
        ]);
    }

    public function test_new_inquiry_appears_as_unread_dashboard_notification(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->post(route('shop.contact.store'), [
            'name' => 'Dashboard Customer',
            'email' => 'dashboard@example.com',
            'phone' => '03001234567',
            'subject' => 'Dashboard inquiry',
            'message' => 'Testing dashboard notifications.',
        ])->assertRedirect(route('shop.contact.success'));

        $notification = Notification::query()
            ->where('user_id', $admin->id)
            ->where('type', 'admin.inquiry_received')
            ->first();

        $this->assertNotNull($notification);
        $this->assertNull($notification->read_at);
        $this->assertSame('New Customer Inquiry Received', $notification->title);
        $this->assertSame('Dashboard Customer', $notification->body);

        $this->actingAs($admin)
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('New Customer Inquiry Received')
            ->assertSee('Dashboard Customer');
    }

    public function test_opening_dashboard_notification_marks_it_read_and_redirects_to_inquiry(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->post(route('shop.contact.store'), [
            'name' => 'Open Me',
            'email' => 'open@example.com',
            'subject' => 'Open notification',
            'message' => 'Please open this notification.',
        ]);

        $inquiry = Inquiry::query()->first();
        $notification = Notification::query()
            ->where('user_id', $admin->id)
            ->where('type', 'admin.inquiry_received')
            ->first();

        $this->assertNotNull($inquiry);
        $this->assertNotNull($notification);

        $this->actingAs($admin)
            ->post(route('admin.notifications.open', $notification))
            ->assertRedirect(route('admin.inquiries.show', $inquiry));

        $this->assertNotNull($notification->fresh()->read_at);
    }
}
