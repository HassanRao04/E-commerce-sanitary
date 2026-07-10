<?php

namespace App\Services;

use App\Models\ReviewSetting;
use Illuminate\Support\Facades\Cache;

class ReviewSettingsService
{
    private const CACHE_KEY = 'review.settings.resolved';

    public function settings(): ReviewSetting
    {
        return Cache::remember(self::CACHE_KEY, now()->addHour(), function (): ReviewSetting {
            return ReviewSetting::current();
        });
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function reviewsEnabled(): bool
    {
        return (bool) $this->settings()->reviews_enabled;
    }

    public function autoApprove(): bool
    {
        return (bool) $this->settings()->auto_approve;
    }

    public function showOnHomepage(): bool
    {
        return (bool) $this->settings()->show_on_homepage;
    }

    public function maxFeatured(): int
    {
        return max(1, min(12, (int) $this->settings()->max_featured));
    }

    public function homepageMode(): string
    {
        $mode = $this->settings()->homepage_mode;

        return in_array($mode, ['featured', 'latest'], true) ? $mode : 'featured';
    }

    /** @param  array<string, mixed>  $data */
    public function sync(array $data): ReviewSetting
    {
        $settings = ReviewSetting::current();
        $settings->update($data);
        $this->clearCache();

        return $settings->fresh();
    }
}
