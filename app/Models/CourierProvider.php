<?php

namespace App\Models;

use App\Models\Concerns\NormalizesStrings;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class CourierProvider extends Model
{
    use NormalizesStrings;

    protected $fillable = [
        'name',
        'slug',
        'logo',
        'is_active',
        'is_sandbox',
        'tracking_url_template',
        'api_base_url',
        'account_number',
        'pickup_address',
        'pickup_city',
        'default_package_weight',
        'config',
        'credentials',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_sandbox' => 'boolean',
            'default_package_weight' => 'decimal:2',
            'config' => 'array',
            'credentials' => 'encrypted:array',
            'sort_order' => 'integer',
        ];
    }

    protected function logoUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->logo ? Storage::url($this->logo) : null,
        );
    }

    protected function apiKey(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->credential('api_key'),
        );
    }

    protected function apiSecret(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->credential('api_secret'),
        );
    }

    protected function modeLabel(): Attribute
    {
        return Attribute::make(
            get: fn (): string => $this->is_sandbox ? 'Sandbox' : 'Production',
        );
    }

    protected function slug(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => static::normalizeLower($value),
        );
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            set: fn (?string $value): ?string => static::normalizeTrim($value),
        );
    }

    #[Scope]
    protected function active(Builder $query): void
    {
        $query->where('is_active', true);
    }

    #[Scope]
    protected function ordered(Builder $query): void
    {
        $query->orderBy('sort_order')->orderBy('name');
    }

    #[Scope]
    protected function search(Builder $query, ?string $term): void
    {
        if (blank($term)) {
            return;
        }

        $query->where(function (Builder $builder) use ($term): void {
            $builder->where('name', 'like', "%{$term}%")
                ->orWhere('slug', 'like', "%{$term}%")
                ->orWhere('pickup_city', 'like', "%{$term}%");
        });
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipping::class);
    }

    public function webhookLogs(): HasMany
    {
        return $this->hasMany(CourierWebhookLog::class);
    }

    public function trackingUrlFor(?string $trackingNumber): ?string
    {
        if (blank($trackingNumber) || blank($this->tracking_url_template)) {
            return null;
        }

        return str_replace(
            '{tracking_number}',
            urlencode($trackingNumber),
            $this->tracking_url_template,
        );
    }

    public function credential(string $key, mixed $default = null): mixed
    {
        return data_get($this->credentials, $key, $default);
    }

    public function hasApiCredentials(): bool
    {
        return filled($this->credential('api_key')) || filled($this->credential('api_secret'));
    }

    public function isConfigured(): bool
    {
        return filled($this->api_base_url)
            && filled($this->credential('api_key'))
            && filled($this->credential('api_secret'));
    }
}
