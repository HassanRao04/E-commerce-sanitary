<?php

namespace Tests\Feature\Admin;

use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_save_contact_notification_and_auto_reply_settings(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $this->actingAs($admin)
            ->patch(route('admin.settings.update'), [
                'site_name' => SiteSetting::current()->site_name,
                'currency' => SiteSetting::current()->currency,
                'email' => 'business@example.com',
                'support_email' => 'support@example.com',
                'contact_form_enabled' => '1',
                'email_notifications_enabled' => '1',
                'whatsapp_notifications_enabled' => '0',
                'auto_reply_enabled' => '1',
            ])
            ->assertRedirect();

        $settings = SiteSetting::current()->fresh();

        $this->assertTrue($settings->isContactFormEnabled());
        $this->assertTrue($settings->areEmailNotificationsEnabled());
        $this->assertFalse($settings->areWhatsappNotificationsEnabled());
        $this->assertTrue($settings->isAutoReplyEnabled());
        $this->assertSame('support@example.com', $settings->inquiryNotificationEmail());
    }

    public function test_inquiry_notification_email_falls_back_to_default(): void
    {
        SiteSetting::current()->update([
            'email' => null,
            'support_email' => null,
        ]);

        $this->assertSame('inayatsanitaryhouse@gmail.com', SiteSetting::current()->inquiryNotificationEmail());
    }
}
