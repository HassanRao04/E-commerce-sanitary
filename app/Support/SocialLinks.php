<?php

namespace App\Support;

use App\Models\SiteSetting;
use Illuminate\Support\Collection;

class SocialLinks
{
    /**
     * @return array<string, array{label: string, icon: string}>
     */
    public static function platforms(): array
    {
        return [
            'facebook' => ['label' => 'Facebook', 'icon' => 'facebook'],
            'instagram' => ['label' => 'Instagram', 'icon' => 'instagram'],
            'youtube' => ['label' => 'YouTube', 'icon' => 'youtube'],
            'twitter' => ['label' => 'X (Twitter)', 'icon' => 'twitter'],
            'linkedin' => ['label' => 'LinkedIn', 'icon' => 'linkedin'],
            'tiktok' => ['label' => 'TikTok', 'icon' => 'tiktok'],
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, string|null>
     */
    public static function sanitize(array $input): array
    {
        $sanitized = [];

        foreach (array_keys(self::platforms()) as $key) {
            $value = trim((string) ($input[$key] ?? ''));

            if ($value === '') {
                $sanitized[$key] = null;

                continue;
            }

            if (! str_starts_with($value, 'http://') && ! str_starts_with($value, 'https://')) {
                $value = 'https://'.$value;
            }

            $sanitized[$key] = $value;
        }

        return $sanitized;
    }

    /**
     * @return Collection<int, array{key: string, label: string, icon: string, url: string}>
     */
    public static function visible(?SiteSetting $settings = null): Collection
    {
        $settings ??= SiteSetting::current();
        $stored = collect($settings->social_links ?? []);
        $items = collect();

        foreach (self::platforms() as $key => $profile) {
            $url = trim((string) ($stored[$key] ?? ''));

            if ($url === '') {
                continue;
            }

            $items->push([
                'key' => $key,
                'label' => $profile['label'],
                'icon' => $profile['icon'],
                'url' => $url,
            ]);
        }

        if (filled($settings->whatsapp)) {
            $items->push([
                'key' => 'whatsapp',
                'label' => 'WhatsApp',
                'icon' => 'whatsapp',
                'url' => self::whatsappUrl(settings: $settings) ?? '',
            ]);
        }

        return $items->filter(fn (array $item): bool => filled($item['url'] ?? null))->values();
    }

    public static function whatsappDigits(?SiteSetting $settings = null): ?string
    {
        $settings ??= SiteSetting::current();

        return self::phoneDigits($settings->whatsapp);
    }

    public static function phoneDigits(?string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone);

        return filled($digits) ? $digits : null;
    }

    public static function normalizePhoneForWhatsapp(
        ?string $phone,
        ?string $defaultCountryCode = null,
    ): ?string {
        $digits = self::phoneDigits($phone);

        if ($digits === null) {
            return null;
        }

        $countryCode = $defaultCountryCode ?? (string) config('services.whatsapp.default_country_code', '92');

        if (str_starts_with($digits, '0')) {
            return $countryCode.substr($digits, 1);
        }

        if (strlen($digits) === 10 && str_starts_with($digits, '3')) {
            return $countryCode.$digits;
        }

        return $digits;
    }

    public static function whatsappUrl(?string $text = null, ?SiteSetting $settings = null): ?string
    {
        $digits = self::whatsappDigits($settings);

        if ($digits === null) {
            return null;
        }

        $url = 'https://wa.me/'.$digits;

        if (filled($text)) {
            $url .= '?text='.rawurlencode($text);
        }

        return $url;
    }

    public static function hasAny(?SiteSetting $settings = null): bool
    {
        return self::visible($settings)->isNotEmpty();
    }
}