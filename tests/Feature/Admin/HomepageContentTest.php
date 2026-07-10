<?php

namespace Tests\Feature\Admin;

use App\Models\Banner;
use App\Models\User;
use App\Services\Admin\HomepageContentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class HomepageContentTest extends TestCase
{
    use RefreshDatabase;

    protected User $contentManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');

        $this->contentManager = User::factory()->create(['email' => 'content@example.com']);
        $this->contentManager->syncRoles(['content-manager']);
    }

    public function test_content_manager_can_view_homepage_admin(): void
    {
        $this->actingAs($this->contentManager)
            ->get(route('admin.homepage.index'))
            ->assertOk()
            ->assertSee('Website Content');
    }

    public function test_content_manager_can_upload_logo_and_create_hero_slide(): void
    {
        $this->actingAs($this->contentManager)
            ->patch(route('admin.homepage.branding.update'), [
                'logo' => UploadedFile::fake()->create('logo.png', 100, 'image/png'),
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertNotNull(\App\Models\SiteSetting::current()->fresh()->logo);

        $this->actingAs($this->contentManager)
            ->post(route('admin.homepage.hero.store'), [
                'title' => 'Summer Sale',
                'subtitle' => 'Big discounts on basins and mixers.',
                'button_text' => 'Shop now',
                'button_url' => '/shop/products',
                'sort_order' => 1,
                'is_active' => 1,
                'image' => UploadedFile::fake()->create('hero.jpg', 100, 'image/jpeg'),
            ])
            ->assertRedirect(route('admin.homepage.index'));

        $banner = Banner::query()->homeHero()->first();

        $this->assertNotNull($banner);
        $this->assertSame('Summer Sale', $banner->title);
        $this->assertSame(HomepageContentService::PLACEMENT_HOME_HERO, $banner->placement);
        Storage::disk('public')->assertExists($banner->image);
    }

    public function test_storefront_uses_erp_hero_banners_when_present(): void
    {
        Banner::query()->create([
            'title' => 'Wall Hung',
            'subtitle' => 'Premium wall-hung basins',
            'image' => UploadedFile::fake()->create('hero.jpg', 100, 'image/jpeg')->store('site/homepage/hero', 'public'),
            'placement' => HomepageContentService::PLACEMENT_HOME_HERO,
            'sort_order' => 0,
            'is_active' => true,
        ]);

        $this->get(route('shop.home'))
            ->assertOk()
            ->assertSee('Wall Hung')
            ->assertSee('Premium wall-hung basins')
            ->assertDontSee('Discover the latest basins, mixers, and bathroom essentials');
    }

    public function test_content_manager_can_update_homepage_sections(): void
    {
        $this->actingAs($this->contentManager)
            ->patch(route('admin.homepage.sections.update'), [
                'sections' => [
                    'featured' => [
                        'enabled' => '1',
                        'title' => 'Staff picks',
                        'subtitle' => 'Our favorites this month.',
                        'badge' => 'Featured',
                        'badge_class' => 'ds-badge-accent',
                        'theme' => 'default',
                        'view_all_label' => 'See all',
                        'collection' => 'featured',
                        'mode' => 'auto',
                        'limit' => 8,
                    ],
                    'best_sellers' => [
                        'enabled' => '0',
                        'title' => 'Best selling products',
                        'subtitle' => null,
                        'badge' => 'Best sellers',
                        'badge_class' => 'ds-badge-neutral',
                        'theme' => 'muted',
                        'view_all_label' => 'View all',
                        'collection' => 'best-sellers',
                        'mode' => 'auto',
                        'limit' => 12,
                    ],
                    'new_arrivals' => [
                        'enabled' => '1',
                        'title' => 'New arrivals',
                        'subtitle' => null,
                        'badge' => 'New in',
                        'badge_class' => 'ds-badge-new',
                        'theme' => 'default',
                        'view_all_label' => 'View all',
                        'collection' => 'new',
                        'mode' => 'auto',
                        'limit' => 12,
                    ],
                    'trending' => [
                        'enabled' => '1',
                        'title' => 'Trending now',
                        'subtitle' => 'Popular this week.',
                        'badge' => 'Trending',
                        'badge_class' => 'ds-badge-neutral',
                        'theme' => 'muted',
                        'view_all_label' => 'View all',
                        'collection' => 'trending',
                        'mode' => 'auto',
                        'limit' => 12,
                    ],
                    'flash_sale' => [
                        'enabled' => '1',
                        'title' => 'Flash sale',
                        'subtitle' => 'Hot deals.',
                        'badge' => 'Hot deals',
                        'badge_class' => 'ds-badge-sale',
                        'theme' => 'sale',
                        'view_all_label' => 'Shop deals',
                        'collection' => 'sale',
                        'mode' => 'auto',
                        'limit' => 12,
                    ],
                    'categories' => [
                        'enabled' => '1',
                        'title' => 'Shop by category',
                        'eyebrow' => 'Browse',
                        'limit' => 6,
                    ],
                    'brands' => [
                        'enabled' => '1',
                        'title' => 'Trusted brands',
                        'eyebrow' => 'Partners',
                        'limit' => 8,
                    ],
                    'testimonials' => [
                        'enabled' => '1',
                        'badge' => 'Testimonials',
                        'title' => 'Customer stories',
                        'subtitle' => 'Verified buyers.',
                        'limit' => 6,
                    ],
                    'trust' => [
                        'enabled' => '1',
                    ],
                    'newsletter' => [
                        'enabled' => '1',
                        'title' => 'Get offers',
                        'subtitle' => 'Subscribe for deals.',
                        'offer' => '10% off',
                        'offer_code' => 'SAVE10',
                        'theme' => 'dark',
                    ],
                    'cta' => [
                        'enabled' => '1',
                        'title' => 'Need help?',
                        'subtitle' => 'Talk to our team.',
                        'button_text' => 'Contact us',
                        'button_url' => '/contact',
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $sections = \App\Support\HomepageSections::resolved();

        $this->assertSame('Staff picks', $sections['featured']['title']);
        $this->assertFalse($sections['best_sellers']['enabled']);

        $this->get(route('shop.home'))
            ->assertOk()
            ->assertSee('Staff picks')
            ->assertSee('Trending now')
            ->assertDontSee('Best selling products');
    }

    public function test_content_manager_can_update_header_and_announcement(): void
    {
        $payload = [
            'header' => [
                'announcement' => [
                    'enabled' => '1',
                    'text' => 'Summer sale — up to 30% off basins and mixers',
                    'link_url' => '/shop/products',
                    'link_label' => 'Shop deals',
                ],
                'nav_items' => [
                    [
                        'label' => 'Home',
                        'route' => 'shop.home',
                        'url' => null,
                        'active_patterns' => 'shop.home',
                        'enabled' => '1',
                        'sort_order' => 0,
                        'mega_menu' => '0',
                        'open_in_new_tab' => '0',
                    ],
                    [
                        'label' => 'Deals',
                        'route' => 'shop.products.index',
                        'url' => null,
                        'active_patterns' => 'shop.products.*',
                        'enabled' => '1',
                        'sort_order' => 10,
                        'mega_menu' => '1',
                        'open_in_new_tab' => '0',
                    ],
                ],
            ],
        ];

        $this->actingAs($this->contentManager)
            ->patch(route('admin.homepage.header.update'), $payload)
            ->assertRedirect()
            ->assertSessionHas('success');

        $header = \App\Support\StorefrontHeader::resolved();

        $this->assertSame('Summer sale — up to 30% off basins and mixers', $header['announcement']['text']);
        $this->assertSame('Deals', $header['nav_items'][1]['label']);

        $this->get(route('shop.home'))
            ->assertOk()
            ->assertSee('Summer sale — up to 30% off basins and mixers')
            ->assertSee('Shop deals')
            ->assertSee('Deals');
    }

    public function test_content_manager_can_manage_social_icons_in_top_bar_and_footer(): void
    {
        $this->actingAs($this->contentManager)
            ->patch(route('admin.homepage.social.update'), [
                'social' => [
                    'show_in_top_bar' => '1',
                    'show_in_footer' => '1',
                ],
                'social_links' => [
                    'facebook' => 'https://facebook.com/sanitarystore',
                    'instagram' => 'https://instagram.com/sanitarystore',
                ],
                'whatsapp' => '+923001112233',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        \App\Models\SiteSetting::current()->update([
            'storefront_header' => array_replace_recursive(
                \App\Support\StorefrontHeader::defaults(),
                ['announcement' => ['enabled' => true, 'text' => 'Follow us for daily deals']],
            ),
        ]);

        $settings = \App\Models\SiteSetting::current()->fresh();

        $this->assertSame('https://facebook.com/sanitarystore', $settings->social_links['facebook']);
        $this->assertSame('+923001112233', $settings->whatsapp);

        $response = $this->get(route('shop.home'));

        $response->assertOk()
            ->assertSee('Follow us for daily deals')
            ->assertSee('storefront-social-links--top-bar', false)
            ->assertSee('aria-label="Facebook"', false)
            ->assertSee('aria-label="Instagram"', false)
            ->assertSee('aria-label="WhatsApp"', false)
            ->assertSee('https://facebook.com/sanitarystore', false)
            ->assertSee('https://wa.me/923001112233', false);
    }

    public function test_social_icons_can_be_hidden_from_footer_only(): void
    {
        $this->actingAs($this->contentManager)
            ->patch(route('admin.homepage.social.update'), [
                'social' => [
                    'show_in_top_bar' => '1',
                    'show_in_footer' => '0',
                ],
                'social_links' => [
                    'facebook' => 'https://facebook.com/sanitarystore',
                ],
            ])
            ->assertRedirect();

        $this->get(route('shop.home'))
            ->assertOk()
            ->assertSee('aria-label="Facebook"', false)
            ->assertDontSee('storefront-footer__social');
    }

    public function test_announcement_bar_can_be_hidden_from_admin(): void
    {
        $this->actingAs($this->contentManager)
            ->patch(route('admin.homepage.header.update'), [
                'header' => [
                    'announcement' => [
                        'enabled' => '0',
                        'text' => 'Hidden promo',
                    ],
                    'social' => [
                        'show_in_top_bar' => '0',
                        'show_in_footer' => '0',
                    ],
                    'social_links' => [],
                    'whatsapp' => '',
                    'nav_items' => \App\Support\StorefrontHeader::defaults()['nav_items'],
                ],
            ])
            ->assertRedirect();

        $this->get(route('shop.home'))
            ->assertOk()
            ->assertDontSee('Hidden promo')
            ->assertDontSee('storefront-announcement');
    }

    public function test_content_manager_can_update_contact_information(): void
    {
        $this->actingAs($this->contentManager)
            ->patch(route('admin.homepage.contact.update'), [
                'contact' => [
                    'page_title' => 'Talk to our team',
                    'intro' => 'We respond within one business day.',
                    'business_hours' => 'Mon–Sat, 10:00 AM – 5:00 PM',
                    'show_order_tracking' => '1',
                    'order_tracking_label' => 'Track an order',
                    'email' => 'support@sanitary.test',
                    'contact_phone' => '+92-331-4324807',
                    'whatsapp' => '+92-331-4324807',
                    'address' => 'Gujranwala, Pakistan',
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $settings = \App\Models\SiteSetting::current()->fresh();

        $this->assertSame('support@sanitary.test', $settings->email);
        $this->assertSame('+92-331-4324807', $settings->contact_phone);
        $this->assertSame('Gujranwala, Pakistan', $settings->address);
        $this->assertSame('Talk to our team', $settings->contact_info['page_title']);
        $this->assertSame('We respond within one business day.', $settings->contact_info['intro']);
        $this->assertTrue($settings->contact_info['show_order_tracking']);

        $this->get(route('shop.contact'))
            ->assertOk()
            ->assertSee('Talk to our team')
            ->assertSee('support@sanitary.test')
            ->assertSee('Gujranwala, Pakistan');
    }

    public function test_content_manager_can_update_footer_content(): void
    {
        $this->actingAs($this->contentManager)
            ->patch(route('admin.homepage.footer.update'), [
                'footer' => [
                    'tagline' => 'ERP footer tagline from admin',
                    'copyright_name' => 'Sanitary Store',
                    'bottom_meta' => 'All prices in PKR',
                    'newsletter' => [
                        'title' => 'Stay updated',
                        'copy' => 'Subscribe for offers.',
                    ],
                    'categories' => [
                        'mode' => 'auto',
                        'limit' => 6,
                    ],
                ],
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $settings = \App\Models\SiteSetting::current()->fresh();

        $this->assertSame('ERP footer tagline from admin', $settings->storefront_footer['tagline']);

        $this->get(route('shop.home'))
            ->assertOk()
            ->assertSee('ERP footer tagline from admin');
    }

    public function test_inventory_staff_cannot_manage_homepage(): void
    {
        $staff = User::factory()->create(['email' => 'inventory@example.com']);
        $staff->syncRoles(['inventory-staff']);

        $this->actingAs($staff)->get(route('admin.homepage.index'))->assertForbidden();
        $this->actingAs($staff)->post(route('admin.homepage.hero.store'), [
            'title' => 'Blocked',
            'image' => UploadedFile::fake()->create('hero.jpg', 100, 'image/jpeg'),
        ])->assertForbidden();

        $this->actingAs($staff)->patch(route('admin.homepage.social.update'), [
            'social_links' => ['facebook' => 'https://facebook.com/test'],
        ])->assertForbidden();
    }
}
