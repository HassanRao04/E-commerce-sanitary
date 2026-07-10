<?php

namespace App\Services\Admin;

use App\Models\Banner;
use App\Models\SiteSetting;
use App\Support\HomepageSections;
use App\Support\SocialLinks;
use App\Support\StorefrontContact;
use App\Support\StorefrontFooter;
use App\Support\StorefrontHeader;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class HomepageContentService
{
    public const PLACEMENT_HOME_HERO = 'home-hero';

    public function heroBanners()
    {
        return Banner::query()
            ->where('placement', self::PLACEMENT_HOME_HERO)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    public function updateBranding(SiteSetting $settings, array $data, ?UploadedFile $logo = null, ?UploadedFile $favicon = null): SiteSetting
    {
        $attributes = [];

        if ($logo !== null) {
            if (filled($settings->logo)) {
                Storage::disk('public')->delete($settings->logo);
            }

            $attributes['logo'] = $logo->store('site/branding', 'public');
        }

        if ($favicon !== null) {
            if (filled($settings->favicon)) {
                Storage::disk('public')->delete($settings->favicon);
            }

            $attributes['favicon'] = $favicon->store('site/branding', 'public');
        }

        if (array_key_exists('remove_logo', $data) && $data['remove_logo']) {
            if (filled($settings->logo)) {
                Storage::disk('public')->delete($settings->logo);
            }

            $attributes['logo'] = null;
        }

        if (array_key_exists('remove_favicon', $data) && $data['remove_favicon']) {
            if (filled($settings->favicon)) {
                Storage::disk('public')->delete($settings->favicon);
            }

            $attributes['favicon'] = null;
        }

        if ($attributes !== []) {
            $settings->update($attributes);
        }

        return $settings->fresh();
    }

    public function createHeroBanner(array $data, ?UploadedFile $image = null): Banner
    {
        return DB::transaction(function () use ($data, $image): Banner {
            $banner = Banner::query()->create([
                'title' => $data['title'],
                'subtitle' => $data['subtitle'] ?? null,
                'image' => $image ? $image->store('site/homepage/hero', 'public') : null,
                'button_text' => $data['button_text'] ?? null,
                'button_url' => $data['button_url'] ?? null,
                'placement' => self::PLACEMENT_HOME_HERO,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'is_active' => ! empty($data['is_active']),
                'metadata' => $this->heroMetadata($data),
            ]);

            return $banner;
        });
    }

    public function updateHeroBanner(Banner $banner, array $data, ?UploadedFile $image = null): Banner
    {
        return DB::transaction(function () use ($banner, $data, $image): Banner {
            $attributes = [
                'title' => $data['title'],
                'subtitle' => $data['subtitle'] ?? null,
                'button_text' => $data['button_text'] ?? null,
                'button_url' => $data['button_url'] ?? null,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
                'is_active' => ! empty($data['is_active']),
                'metadata' => $this->heroMetadata($data),
            ];

            if ($image !== null) {
                if (filled($banner->image)) {
                    Storage::disk('public')->delete($banner->image);
                }

                $attributes['image'] = $image->store('site/homepage/hero', 'public');
            }

            if (! empty($data['remove_image']) && $image === null) {
                if (filled($banner->image)) {
                    Storage::disk('public')->delete($banner->image);
                }

                $attributes['image'] = null;
            }

            $banner->update($attributes);

            return $banner->fresh();
        });
    }

    /**
     * @param  array<string, array<string, mixed>>  $sections
     */
    public function updateSections(SiteSetting $settings, array $sections): SiteSetting
    {
        $settings->update([
            'homepage_sections' => HomepageSections::sanitize($sections),
        ]);

        return $settings->fresh();
    }

    /**
     * @param  array<string, mixed>  $header
     */
    public function updateHeader(SiteSetting $settings, array $header): SiteSetting
    {
        $attributes = [
            'storefront_header' => StorefrontHeader::sanitize($header),
            'social_links' => SocialLinks::sanitize($header['social_links'] ?? []),
        ];

        if (array_key_exists('whatsapp', $header)) {
            $whatsapp = trim((string) ($header['whatsapp'] ?? ''));
            $attributes['whatsapp'] = $whatsapp !== '' ? $whatsapp : null;
        }

        $settings->update($attributes);

        return $settings->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateSocial(SiteSetting $settings, array $data): SiteSetting
    {
        $header = StorefrontHeader::resolved($settings);
        $header['social'] = array_replace_recursive($header['social'] ?? [], $data['social'] ?? []);

        $attributes = [
            'storefront_header' => StorefrontHeader::sanitize([
                'announcement' => $header['announcement'],
                'social' => $header['social'],
                'nav_items' => $header['nav_items'],
            ]),
            'social_links' => SocialLinks::sanitize($data['social_links'] ?? []),
        ];

        if (array_key_exists('whatsapp', $data)) {
            $whatsapp = trim((string) ($data['whatsapp'] ?? ''));
            $attributes['whatsapp'] = $whatsapp !== '' ? $whatsapp : null;
        }

        $settings->update($attributes);

        return $settings->fresh();
    }

    /** @param  array<string, mixed>  $footer */
    public function updateFooter(SiteSetting $settings, array $footer): SiteSetting
    {
        $settings->update([
            'storefront_footer' => StorefrontFooter::sanitize($footer, $settings),
        ]);

        return $settings->fresh();
    }

    /** @param  array<string, mixed>  $contact */
    public function updateContact(SiteSetting $settings, array $contact): SiteSetting
    {
        $contactInfo = StorefrontContact::sanitize([
            'page_title' => $contact['page_title'] ?? null,
            'intro' => $contact['intro'] ?? null,
            'business_hours' => $contact['business_hours'] ?? null,
            'show_order_tracking' => $contact['show_order_tracking'] ?? true,
            'order_tracking_label' => $contact['order_tracking_label'] ?? null,
        ], $settings);

        $settings->update([
            'contact_info' => collect($contactInfo)->only([
                'page_title',
                'intro',
                'business_hours',
                'show_order_tracking',
                'order_tracking_label',
            ])->all(),
            'email' => filled($contact['email'] ?? null) ? trim((string) $contact['email']) : $settings->email,
            'contact_phone' => filled($contact['contact_phone'] ?? null) ? trim((string) $contact['contact_phone']) : null,
            'whatsapp' => filled($contact['whatsapp'] ?? null) ? trim((string) $contact['whatsapp']) : null,
            'address' => filled($contact['address'] ?? null) ? trim((string) $contact['address']) : null,
        ]);

        return $settings->fresh();
    }

    public function deleteHeroBanner(Banner $banner): void
    {
        DB::transaction(function () use ($banner): void {
            if (filled($banner->image)) {
                Storage::disk('public')->delete($banner->image);
            }

            $banner->delete();
        });
    }

    /**
     * @return array<string, mixed>
     */
    private function heroMetadata(array $data): array
    {
        return array_filter([
            'eyebrow' => $data['eyebrow'] ?? null,
            'badge' => $data['badge'] ?? null,
            'badge_class' => $data['badge_class'] ?? null,
            'promo' => $data['promo'] ?? null,
            'promo_detail' => $data['promo_detail'] ?? null,
            'secondary_button_text' => $data['secondary_button_text'] ?? null,
            'secondary_button_url' => $data['secondary_button_url'] ?? null,
            'background' => $data['background'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
    }
}
