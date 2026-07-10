<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\ReviewStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\ReviewSetting;
use App\Models\User;
use App\Services\ReviewSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ReviewSystemTest extends TestCase
{
    use RefreshDatabase;

    protected User $customer;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');

        $this->customer = User::where('email', 'customer@example.com')->first()
            ?? User::factory()->create(['email' => 'customer@example.com']);

        if (! $this->customer->hasRole('customer')) {
            $this->customer->syncRoles(['customer']);
        }

        $this->admin = User::where('email', config('shop.admin_email'))->first();
    }

    public function test_customer_can_submit_review_for_delivered_order_item(): void
    {
        [$order, $item] = $this->deliveredOrderForCustomer();

        $this->actingAs($this->customer)
            ->post(route('shop.account.orders.review.store', [$order, $item]), [
                'rating' => 5,
                'title' => 'Excellent quality',
                'body' => 'The basin arrived on time and looks premium in our bathroom.',
            ])
            ->assertRedirect(route('shop.account.orders.show', $order))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('reviews', [
            'user_id' => $this->customer->id,
            'product_id' => $item->product_id,
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'rating' => 5,
            'status' => ReviewStatus::Pending->value,
        ]);
    }

    public function test_customer_cannot_review_pending_order(): void
    {
        $variant = ProductVariant::query()->firstOrFail();

        $order = Order::factory()->create([
            'user_id' => $this->customer->id,
            'status' => 'pending',
            'payment_status' => PaymentStatus::Pending,
        ]);

        $item = OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'product_name' => $variant->product->name,
            'sku' => $variant->sku,
            'quantity' => 1,
            'unit_price' => 1000,
            'total' => 1000,
        ]);

        $this->actingAs($this->customer)
            ->get(route('shop.account.orders.review.create', [$order, $item]))
            ->assertForbidden();
    }

    public function test_duplicate_review_for_same_order_item_is_prevented(): void
    {
        [$order, $item] = $this->deliveredOrderForCustomer();

        Review::query()->create([
            'user_id' => $this->customer->id,
            'product_id' => $item->product_id,
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'rating' => 4,
            'title' => 'First review',
            'body' => 'Already reviewed this purchase item once.',
            'status' => ReviewStatus::Pending,
        ]);

        $this->actingAs($this->customer)
            ->post(route('shop.account.orders.review.store', [$order, $item]), [
                'rating' => 5,
                'title' => 'Duplicate',
                'body' => 'Trying to submit another review for the same item.',
            ])
            ->assertSessionHasErrors('review');
    }

    public function test_admin_can_approve_and_reject_reviews(): void
    {
        $review = $this->createPendingReview();

        $this->actingAs($this->admin)
            ->patch(route('admin.reviews.approve', $review))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(ReviewStatus::Approved, $review->fresh()->status);

        $this->actingAs($this->admin)
            ->patch(route('admin.reviews.reject', $review))
            ->assertRedirect();

        $this->assertSame(ReviewStatus::Rejected, $review->fresh()->status);
    }

    public function test_only_approved_reviews_appear_on_product_page(): void
    {
        $variant = ProductVariant::query()->firstOrFail();
        $product = $variant->product;

        Review::query()->create([
            'user_id' => $this->customer->id,
            'product_id' => $product->id,
            'rating' => 5,
            'title' => 'Pending review',
            'body' => 'This should not appear publicly yet.',
            'status' => ReviewStatus::Pending,
        ]);

        Review::query()->create([
            'user_id' => $this->customer->id,
            'product_id' => $product->id,
            'rating' => 4,
            'title' => 'Approved review',
            'body' => 'This approved review should appear on the product page.',
            'status' => ReviewStatus::Approved,
        ]);

        $this->get(route('shop.products.show', $product))
            ->assertOk()
            ->assertSee('Approved review')
            ->assertDontSee('This should not appear publicly yet.');
    }

    public function test_auto_approve_setting_publishes_review_immediately(): void
    {
        ReviewSetting::current()->update(['auto_approve' => true]);
        app(ReviewSettingsService::class)->clearCache();

        [$order, $item] = $this->deliveredOrderForCustomer();

        $this->actingAs($this->customer)
            ->post(route('shop.account.orders.review.store', [$order, $item]), [
                'rating' => 5,
                'title' => 'Instant publish',
                'body' => 'Auto approved review for immediate storefront visibility.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('reviews', [
            'order_item_id' => $item->id,
            'status' => ReviewStatus::Approved->value,
        ]);
    }

    public function test_delivered_order_shows_write_review_in_my_orders(): void
    {
        [$order] = $this->deliveredOrderForCustomer();

        $this->actingAs($this->customer)
            ->get(route('shop.account.orders.index'))
            ->assertOk()
            ->assertSee($order->order_number)
            ->assertSee('Write review');
    }

    public function test_admin_can_update_review_settings(): void
    {
        $this->actingAs($this->admin)
            ->patch(route('admin.reviews.settings.update'), [
                'reviews_enabled' => '1',
                'auto_approve' => '0',
                'show_on_homepage' => '1',
                'max_featured' => 4,
                'homepage_mode' => 'featured',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $settings = ReviewSetting::current()->fresh();
        $this->assertTrue($settings->reviews_enabled);
        $this->assertSame(4, $settings->max_featured);
    }

    public function test_featured_reviews_appear_on_homepage_when_marked(): void
    {
        ReviewSetting::current()->update([
            'show_on_homepage' => true,
            'homepage_mode' => 'featured',
        ]);
        app(ReviewSettingsService::class)->clearCache();

        $variant = ProductVariant::query()->firstOrFail();

        Review::query()->create([
            'user_id' => $this->customer->id,
            'product_id' => $variant->product_id,
            'rating' => 5,
            'title' => 'Featured buyer review',
            'body' => 'This featured review should appear in homepage testimonials.',
            'status' => ReviewStatus::Approved,
            'is_featured' => true,
        ]);

        $this->get(route('shop.home'))
            ->assertOk()
            ->assertSee('Featured buyer review');
    }

    /** @return array{0: Order, 1: OrderItem} */
    private function deliveredOrderForCustomer(): array
    {
        $variant = ProductVariant::query()->firstOrFail();

        $order = Order::factory()->paid()->create([
            'user_id' => $this->customer->id,
            'status' => 'delivered',
        ]);

        $item = OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'product_name' => $variant->product->name,
            'sku' => $variant->sku,
            'quantity' => 1,
            'unit_price' => 1000,
            'total' => 1000,
        ]);

        return [$order->fresh(), $item];
    }

    private function createPendingReview(): Review
    {
        [$order, $item] = $this->deliveredOrderForCustomer();

        return Review::query()->create([
            'user_id' => $this->customer->id,
            'product_id' => $item->product_id,
            'order_id' => $order->id,
            'order_item_id' => $item->id,
            'rating' => 5,
            'title' => 'Needs moderation',
            'body' => 'Pending review awaiting admin approval.',
            'status' => ReviewStatus::Pending,
        ]);
    }
}
