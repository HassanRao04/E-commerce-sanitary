<?php

namespace App\Support;

use App\Models\SiteSetting;

class StorefrontContact
{
    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            'page_title' => 'Contact us',
            'intro' => "Have a question about a product, need a quote, or want help with an order? Send us a message and we'll get back to you within one business day.",
            'business_hours' => 'Monday – Saturday, 9:00 AM – 6:00 PM (PKT)',
            'show_order_tracking' => true,
            'order_tracking_label' => 'Track your order online',
        ];
    }

    /** @return array<string, mixed> */
    public static function resolved(?SiteSetting $settings = null): array
    {
        $settings ??= SiteSetting::current();
        $stored = is_array($settings->contact_info) ? $settings->contact_info : [];

        return self::sanitize(array_replace_recursive(self::defaults(), $stored), $settings);
    }

    /** @param  array<string, mixed>  $input */
    public static function sanitize(array $input, ?SiteSetting $settings = null): array
    {
        $contact = array_replace_recursive(self::defaults(), $input);

        $contact['page_title'] = trim((string) ($contact['page_title'] ?? 'Contact us'));
        $contact['intro'] = trim((string) ($contact['intro'] ?? ''));
        $contact['business_hours'] = trim((string) ($contact['business_hours'] ?? ''));
        $contact['show_order_tracking'] = filter_var($contact['show_order_tracking'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $contact['order_tracking_label'] = trim((string) ($contact['order_tracking_label'] ?? 'Track your order online'));

        $contact['email'] = $settings?->email;
        $contact['phone'] = $settings?->contact_phone;
        $contact['whatsapp'] = $settings?->whatsapp;
        $contact['address'] = $settings?->address;

        return $contact;
    }
}
