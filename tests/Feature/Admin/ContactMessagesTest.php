<?php

namespace Tests\Feature\Admin;

use App\Enums\InquiryStatus;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactMessagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_admin_can_view_search_filter_by_date_and_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        Inquiry::query()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '03001234567',
            'subject' => 'Basin quote',
            'message' => 'Looking for a wall hung basin.',
            'type' => 'contact',
            'source' => Inquiry::SOURCE_CONTACT_FORM,
            'ip_address' => '127.0.0.1',
            'status' => InquiryStatus::New,
        ])->forceFill(['created_at' => now()->subDay()])->save();

        Inquiry::query()->create([
            'name' => 'John Smith',
            'email' => 'john@example.com',
            'phone' => null,
            'subject' => 'Shipping question',
            'message' => 'Do you deliver to Lahore?',
            'type' => 'contact',
            'source' => Inquiry::SOURCE_CONTACT_FORM,
            'ip_address' => '10.0.0.1',
            'status' => InquiryStatus::Pending,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.inquiries.index', ['q' => 'Basin']))
            ->assertOk()
            ->assertSee('Jane Doe')
            ->assertDontSee('John Smith');

        $this->actingAs($admin)
            ->get(route('admin.inquiries.index', ['status' => InquiryStatus::Pending->value]))
            ->assertOk()
            ->assertSee('John Smith')
            ->assertDontSee('Jane Doe');

        $this->actingAs($admin)
            ->get(route('admin.inquiries.index', ['date' => now()->toDateString()]))
            ->assertOk()
            ->assertSee('John Smith')
            ->assertDontSee('Jane Doe');
    }

    public function test_admin_can_update_status_and_delete_messages(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $inquiry = Inquiry::query()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '03001234567',
            'subject' => 'Basin quote',
            'message' => 'Looking for a wall hung basin.',
            'type' => 'contact',
            'source' => Inquiry::SOURCE_CONTACT_FORM,
            'ip_address' => '127.0.0.1',
            'status' => InquiryStatus::New,
        ]);

        $this->actingAs($admin)
            ->patch(route('admin.inquiries.status', $inquiry), ['status' => InquiryStatus::Pending->value])
            ->assertRedirect();

        $this->assertSame(InquiryStatus::Pending, $inquiry->fresh()->status);

        $this->actingAs($admin)
            ->patch(route('admin.inquiries.status', $inquiry), ['status' => InquiryStatus::Replied->value])
            ->assertRedirect();

        $this->assertSame(InquiryStatus::Replied, $inquiry->fresh()->status);

        $this->actingAs($admin)
            ->patch(route('admin.inquiries.status', $inquiry), ['status' => InquiryStatus::Closed->value])
            ->assertRedirect();

        $this->assertSame(InquiryStatus::Closed, $inquiry->fresh()->status);

        $this->actingAs($admin)
            ->patch(route('admin.inquiries.status', $inquiry), ['status' => InquiryStatus::Spam->value])
            ->assertRedirect();

        $this->assertSame(InquiryStatus::Spam, $inquiry->fresh()->status);

        $this->actingAs($admin)
            ->delete(route('admin.inquiries.destroy', $inquiry))
            ->assertRedirect(route('admin.inquiries.index'));

        $this->assertDatabaseMissing('inquiries', ['id' => $inquiry->id]);
    }

    public function test_viewing_a_message_does_not_change_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $inquiry = Inquiry::query()->create([
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '03001234567',
            'subject' => 'Basin quote',
            'message' => 'Looking for a wall hung basin.',
            'type' => 'contact',
            'source' => Inquiry::SOURCE_CONTACT_FORM,
            'ip_address' => '127.0.0.1',
            'status' => InquiryStatus::New,
        ]);

        $this->actingAs($admin)
            ->get(route('admin.inquiries.show', $inquiry))
            ->assertOk()
            ->assertSee('Jane Doe')
            ->assertSee('Basin quote')
            ->assertSee('Looking for a wall hung basin.')
            ->assertSee('Contact form')
            ->assertSee('127.0.0.1')
            ->assertSee('Reply Later');

        $this->assertSame(InquiryStatus::New, $inquiry->fresh()->status);
    }
}
