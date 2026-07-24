<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Support\Facades\Storage;

class SiteSetting extends Model
{
    protected $fillable = [
        'site_name',
        'logo',
        'favicon',
        'email',
        'support_email',
        'contact_phone',
        'whatsapp',
        'contact_form_enabled',
        'email_notifications_enabled',
        'whatsapp_notifications_enabled',
        'auto_reply_enabled',
        'address',
        'default_meta_description',
        'currency',
        'tax_rate',
        'shipping_flat_rate',
        'social_links',
        'homepage_sections',
        'storefront_header',
        'storefront_footer',
        'contact_info',
    ];

    protected function casts(): array
    {
        return [
            'tax_rate' => 'decimal:2',
            'shipping_flat_rate' => 'decimal:2',
            'contact_form_enabled' => 'boolean',
            'email_notifications_enabled' => 'boolean',
            'whatsapp_notifications_enabled' => 'boolean',
            'auto_reply_enabled' => 'boolean',
            'social_links' => 'array',
            'homepage_sections' => 'array',
            'storefront_header' => 'array',
            'storefront_footer' => 'array',
            'contact_info' => 'array',
        ];
    }

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'site_name' => config('app.name'),
            'currency' => 'PKR',
            'tax_rate' => 0,
            'shipping_flat_rate' => 0,
        ]);
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => filled($this->logo) ? Storage::url($this->logo) : null,
        );
    }

    protected function faviconUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => filled($this->favicon) ? Storage::url($this->favicon) : null,
        );
    }

    public function displayName(): string
    {
        return $this->site_name ?: config('app.name', 'Sanitary Store');
    }

    public function isContactFormEnabled(): bool
    {
        return (bool) ($this->contact_form_enabled ?? true);
    }

    public function areEmailNotificationsEnabled(): bool
    {
        return (bool) ($this->email_notifications_enabled ?? true);
    }

    public function areWhatsappNotificationsEnabled(): bool
    {
        return (bool) ($this->whatsapp_notifications_enabled ?? true);
    }

    public function isAutoReplyEnabled(): bool
    {
        return (bool) ($this->auto_reply_enabled ?? false);
    }

    public function inquiryNotificationEmail(): string
    {
        if (filled($this->support_email)) {
            return $this->support_email;
        }

        if (filled($this->email)) {
            return $this->email;
        }

        return 'inayatsanitaryhouse@gmail.com';
    }
}
