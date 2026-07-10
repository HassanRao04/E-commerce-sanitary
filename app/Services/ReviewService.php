<?php

namespace App\Services;

use App\Enums\ReviewStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ReviewService
{
    public function __construct(
        private readonly OrderWorkflowService $workflow,
        private readonly ReviewSettingsService $settings,
    ) {}

    public function orderIsReviewEligible(Order $order): bool
    {
        if (! $this->settings->reviewsEnabled()) {
            return false;
        }

        if (! $this->workflow->isDelivered($order->status)) {
            return false;
        }

        if ($this->workflow->isCancelled($order->status) || $this->workflow->isReturned($order->status)) {
            return false;
        }

        return true;
    }

    public function canReviewOrderItem(User $user, OrderItem $item): bool
    {
        if (! $this->settings->reviewsEnabled()) {
            return false;
        }

        $item->loadMissing('order');
        $order = $item->order;

        if ($order === null || $order->user_id !== $user->id) {
            return false;
        }

        if (! $this->orderIsReviewEligible($order)) {
            return false;
        }

        if ($item->product_id === null) {
            return false;
        }

        return ! $this->hasReviewForOrderItem($user, $item);
    }

    public function hasReviewForOrderItem(User $user, OrderItem $item): bool
    {
        return Review::query()
            ->where('user_id', $user->id)
            ->where('order_item_id', $item->id)
            ->exists();
    }

    /** @return Collection<int, OrderItem> */
    public function reviewableItemsForOrder(Order $order, User $user): Collection
    {
        if ($order->user_id !== $user->id || ! $this->orderIsReviewEligible($order)) {
            return collect();
        }

        $order->loadMissing('items');

        return $order->items
            ->filter(fn (OrderItem $item): bool => $this->canReviewOrderItem($user, $item))
            ->values();
    }

    /** @return Collection<int, OrderItem> */
    public function reviewedItemsForOrder(Order $order, User $user): Collection
    {
        $reviewedItemIds = Review::query()
            ->where('user_id', $user->id)
            ->where('order_id', $order->id)
            ->whereNotNull('order_item_id')
            ->pluck('order_item_id');

        return $order->items->whereIn('id', $reviewedItemIds)->values();
    }

    public function orderHasPendingReviewableItems(Order $order, User $user): bool
    {
        return $this->reviewableItemsForOrder($order, $user)->isNotEmpty();
    }

    public function orderHasAnyReview(Order $order, User $user): bool
    {
        return Review::query()
            ->where('user_id', $user->id)
            ->where('order_id', $order->id)
            ->exists();
    }

    /**
     * @param  array{rating: int, title?: string|null, body: string}  $data
     * @param  list<UploadedFile>  $images
     */
    public function submit(User $user, OrderItem $item, array $data, array $images = []): Review
    {
        if (! $this->canReviewOrderItem($user, $item)) {
            throw ValidationException::withMessages([
                'review' => 'You are not eligible to review this product for this order.',
            ]);
        }

        $order = $item->order;

        return DB::transaction(function () use ($user, $item, $order, $data, $images): Review {
            $status = $this->settings->autoApprove()
                ? ReviewStatus::Approved
                : ReviewStatus::Pending;

            $review = Review::query()->create([
                'user_id' => $user->id,
                'product_id' => $item->product_id,
                'order_id' => $order->id,
                'order_item_id' => $item->id,
                'rating' => (int) $data['rating'],
                'title' => $data['title'] ?? null,
                'body' => $data['body'],
                'status' => $status,
                'is_featured' => false,
            ]);

            foreach ($images as $index => $image) {
                if (! $image instanceof UploadedFile) {
                    continue;
                }

                $path = $image->store('reviews/'.$review->id, 'public');

                $review->images()->create([
                    'path' => $path,
                    'sort_order' => $index,
                ]);
            }

            return $review->fresh(['images', 'product', 'user']);
        });
    }

    public function deleteReview(Review $review): void
    {
        DB::transaction(function () use ($review): void {
            foreach ($review->images as $image) {
                Storage::disk('public')->delete($image->path);
            }

            $review->delete();
        });
    }
}
