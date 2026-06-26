<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Admin\InvoiceService;
use App\Services\CustomerDashboardService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AccountController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CustomerDashboardService $dashboardService,
        private readonly InvoiceService $invoiceService,
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

        return view('storefront.account.orders.index', compact('orders'));
    }

    public function orderShow(Order $order): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->isStaff()) {
            return redirect()->route('admin.dashboard');
        }

        $this->authorize('view', $order);

        $order->load([
            'items',
            'billingAddress',
            'shippingAddress',
            'statusHistories' => fn ($q) => $q->orderBy('created_at'),
            'shipments',
        ]);

        return view('storefront.account.orders.show', compact('order'));
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
}
