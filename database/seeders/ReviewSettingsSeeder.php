<?php

namespace Database\Seeders;

use App\Models\ReviewSetting;
use App\Services\ReviewSettingsService;
use Illuminate\Database\Seeder;

class ReviewSettingsSeeder extends Seeder
{
    public function run(): void
    {
        ReviewSetting::current()->update([
            'reviews_enabled' => true,
            'auto_approve' => false,
            'show_on_homepage' => true,
            'max_featured' => 6,
            'homepage_mode' => 'featured',
        ]);

        app(ReviewSettingsService::class)->clearCache();
    }
}
