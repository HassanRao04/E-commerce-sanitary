<?php

namespace Tests\Feature\Storefront;

use App\Models\SiteSetting;
use App\Support\StorefrontContact;
use App\Support\StorefrontFooter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorefrontContentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_contact_page_uses_erp_contact_information(): void
    {
        SiteSetting::current()->update([
            'email' => 'support@sanitary.test',
            'contact_phone' => '03001234567',
            'address' => '123 ERP Street, Karachi',
            'contact_info' => [
                'page_title' => 'Talk to our team',
                'intro' => 'We respond within one business day.',
                'business_hours' => 'Mon–Sat, 10:00 AM – 5:00 PM',
                'show_order_tracking' => true,
                'order_tracking_label' => 'Track an order',
            ],
        ]);

        $this->get(route('shop.contact'))
            ->assertOk()
            ->assertSee('Talk to our team')
            ->assertSee('support@sanitary.test')
            ->assertSee('123 ERP Street, Karachi')
            ->assertSee('Mon–Sat, 10:00 AM – 5:00 PM');
    }

    public function test_footer_uses_erp_footer_content(): void
    {
        SiteSetting::current()->update([
            'storefront_footer' => StorefrontFooter::sanitize([
                'tagline' => 'ERP-managed footer tagline',
                'bottom_meta' => 'Managed by ERP footer meta',
                'newsletter' => [
                    'title' => 'ERP Newsletter',
                    'copy' => 'Subscribe for ERP-managed offers.',
                ],
            ]),
        ]);

        $this->get(route('shop.home'))
            ->assertOk()
            ->assertSee('ERP-managed footer tagline')
            ->assertSee('ERP Newsletter')
            ->assertSee('Managed by ERP footer meta');
    }

    public function test_homepage_trust_section_uses_erp_headings(): void
    {
        $sections = \App\Support\HomepageSections::resolved();
        $sections['trust']['why_choose']['title'] = 'ERP trust headline';

        SiteSetting::current()->update([
            'homepage_sections' => $sections,
        ]);

        $this->get(route('shop.home'))
            ->assertOk()
            ->assertSee('ERP trust headline');
    }
}
