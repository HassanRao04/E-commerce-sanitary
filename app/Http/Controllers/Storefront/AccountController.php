<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\StoreReviewRequest;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\Admin\InvoiceService;
use App\Services\CustomerDashboardService;
use App\Services\OrderWorkflowService;
use App\Services\ReviewService;
use App\Services\ReviewSettingsService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccountController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CustomerDashboardService $dashboardService,
        private readonly InvoiceService $invoiceService,
        private readonly ReviewService $reviewService,
        private readonly ReviewSettingsService $reviewSettings,
        private readonly OrderWorkflowService $workflow,
    ) {}

    public function dashboard(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->isStaff()) {
            return redirect()->route('admin.dashboard');
        }

        $stats = $this->dashboardService->stats($user);

        return view('storefront.account.dashboard', [
            'stats' => $stats,
            'recentOrders' => $stats['recent_orders'],
        ]);
    }

    public function orders(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->isStaff()) {
            return redirect()->route('admin.dashboard');
        }

        $orders = $user->orders()
            ->with(['shipments' => fn ($q) => $q->latest()->limit(1)])
            ->withCount('items')
            ->latest()
            ->paginate(10);

        $reviewStates = $orders->getCollection()->mapWithKeys(function (Order $order) use ($user): array {
            return [
                $order->id => [
                    'is_delivered' => $this->workflow->isDelivered($order->status),
                    'can_review' => $this->reviewService->orderHasPendingReviewableItems($order, $user),
                    'has_review' => $this->reviewService->orderHasAnyReview($order, $user),
                ],
            ];
        });

        return view('storefront.account.orders.index', [
            'orders' => $orders,
            'reviewStates' => $reviewStates,
            'reviewsEnabled' => $this->reviewSettings->reviewsEnabled(),
        ]);
    }

    public function orderShow(Order $order): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->isStaff()) {
            return redirect()->route('admin.dashboard');
        }

        $this->authorize('view', $order);

        $order->load([
            'items.product',
            'items.review',
            'billingAddress',
            'shippingAddress',
            'statusHistories' => fn ($q) => $q->orderBy('created_at'),
            'shipments',
        ]);

        return view('storefront.account.orders.show', [
            'order' => $order,
            'reviewsEnabled' => $this->reviewSettings->reviewsEnabled(),
            'orderReviewEligible' => $this->reviewService->orderIsReviewEligible($order),
            'reviewableItems' => $this->reviewService->reviewableItemsForOrder($order, $user),
        ]);
    }

    public function orderTrack(Order $order): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->isStaff()) {
            return redirect()->route('admin.dashboard');
        }

        $this->authorize('view', $order);

        $order->load([
            'items',
            'statusHistories' => fn ($q) => $q->orderBy('created_at'),
            'shipments.trackingEvents' => fn ($q) => $q->orderByDesc('event_at'),
        ]);

        return view('storefront.account.orders.track', compact('order'));
    }

    public function downloadInvoice(Order $order): View
    {
        $this->authorize('downloadInvoice', $order);

        if (! $order->invoice) {
            $this->invoiceService->generateFromOrder($order);
            $order->load('invoice.items');
        }

        return view('admin.invoices.print', [
            'invoice' => $order->invoice,
            'order' => $order,
        ]);
    }

    public function orderReviewHub(Order $order): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->isStaff()) {
            return redirect()->route('admin.dashboard');
        }

        $this->authorize('view', $order);

        $reviewableItems = $this->reviewService->reviewableItemsForOrder($order, $user);

        if ($reviewableItems->isEmpty()) {
            return redirect()
                ->route('shop.account.orders.show', $order)
                ->with('error', 'There are no products left to review for this order.');
        }

        if ($reviewableItems->count() === 1) {
            return redirect()->route('shop.account.orders.review.create', [
                'order' => $order,
                'orderItem' => $reviewableItems->first(),
            ]);
        }

        return view('storefront.account.orders.review-hub', [
            'order' => $order,
            'reviewableItems' => $reviewableItems,
        ]);
    }

    public function createReview(Order $order, OrderItem $orderItem): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->isStaff()) {
            return redirect()->route('admin.dashboard');
        }

        $this->authorize('view', $order);

        abort_unless($orderItem->order_id === $order->id, 404);
        abort_unless($this->reviewService->canReviewOrderItem($user, $orderItem), 403);

        $orderItem->load('product');

        return view('storefront.account.orders.review-form', [
            'order' => $order,
            'orderItem' => $orderItem,
        ]);
    }

    public function storeReview(StoreReviewRequest $request, Order $order, OrderItem $orderItem): RedirectResponse
    {
        $user = auth()->user();

        if ($user->isStaff()) {
            return redirect()->route('admin.dashboard');
        }

        $this->authorize('view', $order);

        abort_unless($orderItem->order_id === $order->id, 404);

        $this->reviewService->submit(
            $user,
            $orderItem,
            $request->validated(),
            $request->file('images', []),
        );

        $message = $this->reviewSettings->autoApprove()
            ? 'Thank you! Your review has been published.'
            : 'Thank you! Your review has been submitted and is pending approval.';

        return redirect()
            ->route('shop.account.orders.show', $order)
            ->with('success', $message);
    }
}
