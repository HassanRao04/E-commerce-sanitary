<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateReviewSettingsRequest;
use App\Models\Review;
use App\Models\ReviewSetting;
use App\Services\ActivityLogService;
use App\Services\ReviewService;
use App\Services\ReviewSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReviewController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviewService,
        private readonly ReviewSettingsService $reviewSettings,
        private readonly ActivityLogService $activityLog,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Review::class);

        $reviews = Review::query()
            ->with(['user', 'product', 'order'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('q'), fn ($q) => $q->search($request->input('q')))
            ->recent()
            ->paginate(15)
            ->withQueryString();

        return view('admin.reviews.index', compact('reviews'));
    }

    public function settings(): View
    {
        $this->authorize('viewAny', Review::class);

        return view('admin.reviews.settings', [
            'settings' => ReviewSetting::current(),
        ]);
    }

    public function updateSettings(UpdateReviewSettingsRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $this->reviewSettings->sync([
            'reviews_enabled' => $request->boolean('reviews_enabled'),
            'auto_approve' => $request->boolean('auto_approve'),
            'show_on_homepage' => $request->boolean('show_on_homepage'),
            'max_featured' => (int) $validated['max_featured'],
            'homepage_mode' => $validated['homepage_mode'],
        ]);

        $this->activityLog->log('review.settings.updated', ReviewSetting::current(), [], $validated);

        return back()->with('success', 'Review settings saved successfully.');
    }

    public function approve(Review $review): RedirectResponse
    {
        $this->authorize('moderate', $review);

        $review->update(['status' => ReviewStatus::Approved]);

        return back()->with('success', 'Review approved.');
    }

    public function reject(Review $review): RedirectResponse
    {
        $this->authorize('moderate', $review);

        $review->update(['status' => ReviewStatus::Rejected]);

        return back()->with('success', 'Review rejected.');
    }

    public function hide(Review $review): RedirectResponse
    {
        $this->authorize('moderate', $review);

        $review->update(['status' => ReviewStatus::Hidden]);

        return back()->with('success', 'Review hidden from the website.');
    }

    public function toggleFeatured(Review $review): RedirectResponse
    {
        $this->authorize('moderate', $review);

        if ($review->status !== ReviewStatus::Approved) {
            return back()->with('error', 'Only approved reviews can be featured.');
        }

        $review->update(['is_featured' => ! $review->is_featured]);

        return back()->with('success', $review->is_featured ? 'Review marked as featured.' : 'Review removed from featured.');
    }

    public function destroy(Review $review): RedirectResponse
    {
        $this->authorize('delete', $review);

        $this->reviewService->deleteReview($review);

        return back()->with('success', 'Review deleted.');
    }
}
