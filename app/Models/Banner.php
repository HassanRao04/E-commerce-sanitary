<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Banner extends Model
{
    public const PLACEMENT_HOME_HERO = 'home-hero';

    protected $fillable = [
        'title',
        'subtitle',
        'image',
        'button_text',
        'button_url',
        'metadata',
        'placement',
        'sort_order',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => filled($this->image) ? Storage::url($this->image) : null,
        );
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->where(function (Builder $builder): void {
                $builder->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $builder): void {
                $builder->whereNull('ends_at')->orWhere('ends_at', '>=', now());
            });
    }

    public function scopeHomeHero(Builder $query): Builder
    {
        return $query->where('placement', self::PLACEMENT_HOME_HERO);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return array<string, mixed>
     */
    public function heroSlideData(): array
    {
        $meta = $this->metadata ?? [];

        return [
            'key' => 'banner-'.$this->id,
            'eyebrow' => $meta['eyebrow'] ?? '',
            'title' => $this->title,
            'subtitle' => $this->subtitle ?? '',
            'badge' => $meta['badge'] ?? 'Featured',
            'badge_class' => $meta['badge_class'] ?? 'ds-badge-accent !bg-white/15 !text-white border border-white/20',
            'promo' => $meta['promo'] ?? '',
            'promo_detail' => $meta['promo_detail'] ?? '',
            'cta_primary' => [
                'label' => $this->button_text ?: 'Shop now',
                'url' => $this->button_url ?: route('shop.products.index'),
            ],
            'cta_secondary' => [
                'label' => $meta['secondary_button_text'] ?? 'Learn more',
                'url' => $meta['secondary_button_url'] ?? route('shop.about'),
            ],
            'image_url' => $this->image_url,
            'bg' => $meta['background'] ?? 'linear-gradient(135deg, #0b0b0f 0%, #1c1c1e 42%, #003566 100%)',
            'orb_a' => $meta['orb_a'] ?? 'bg-accent/40 w-72 h-72 -top-16 -right-10',
            'orb_b' => $meta['orb_b'] ?? 'bg-white/10 w-56 h-56 bottom-0 left-1/4',
        ];
    }
}
