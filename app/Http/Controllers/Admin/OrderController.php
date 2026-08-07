<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CancelOrderRequest;
use App\Http\Requests\Admin\UpdateOrderPaymentStatusRequest;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Models\CourierProvider;
use App\Models\Order;
use App\Services\Admin\InvoiceService;
use App\Services\Admin\OrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService,
        private readonly InvoiceService $invoiceService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Order::class);

        return view('admin.orders.index', [
            'orders' => $this->orderService->paginatedList($request->only('q', 'status', 'payment_status', 'date_from', 'date_to')),
            'statusCounts' => $this->orderService->statusCounts(),
        ]);
    }

    public function show(Order $order): View
    {
        $this->authorize('view', $order);

        $order = $this->orderService->findWithRelations($order->id);

        return view('admin.orders.show', [
            'order' => $order,
            'bookableCourierProviders' => CourierProvider::query()
                ->where('slug', '!=', 'manual')
                ->active()
                ->ordered()
                ->get(),
        ]);
    }

    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        $this->orderService->updateStatus(
            $order,
            $request->validated('status'),
            $request->input('note'),
        );

        return back()->with('success', 'Order status updated.');
    }

    public function updatePaymentStatus(UpdateOrderPaymentStatusRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('update', $order);

        $this->orderService->updatePaymentStatus(
            $order,
            $request->enum('payment_status', \App\Enums\PaymentStatus::class),
        );

        return back()->with('success', 'Payment status updated.');
    }

    public function cancel(CancelOrderRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('cancel', $order);

        $this->orderService->cancel($order, $request->input('note'));

        return back()->with('success', 'Order cancelled.');
    }

    public function generateInvoice(Order $order): RedirectResponse
    {
        $this->authorize('create', \App\Models\Invoice::class);

        $this->invoiceService->generateFromOrder($order);

        return back()->with('success', 'Invoice generated.');
    }

    public function printInvoice(Order $order): View
    {
        $this->authorize('view', $order);

        $order = $this->orderService->findWithRelations($order->id);

        if (! $order->invoice) {
            $this->invoiceService->generateFromOrder($order);
            $order->load('invoice.items');
        }

        return view('admin.invoices.print', [
            'invoice' => $order->invoice->loadMissing(['items', 'order']),
            'order' => $order,
        ]);
    }

    public function track(Order $order): View
    {
        $this->authorize('view', $order);

        return view('admin.orders.track', [
            'order' => $this->orderService->findWithRelations($order->id),
        ]);
    }
}
