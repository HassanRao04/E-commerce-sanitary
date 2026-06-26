<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreShippingRequest;
use App\Http\Requests\Admin\StoreTrackingEventRequest;
use App\Http\Requests\Admin\UpdateShippingRequest;
use App\Models\Order;
use App\Models\Shipping;
use App\Services\Admin\ShippingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShippingController extends Controller
{
    public function __construct(private readonly ShippingService $shippingService) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Shipping::class);

        return view('admin.shipping.index', [
            'shipments' => $this->shippingService->paginatedList($request->only('q', 'status')),
        ]);
    }

    public function show(Shipping $shipping): View
    {
        $this->authorize('view', $shipping);

        $shipping->load(['order', 'trackingEvents' => fn ($q) => $q->latestFirst()]);

        return view('admin.shipping.show', ['shipment' => $shipping]);
    }

    public function store(StoreShippingRequest $request, Order $order): RedirectResponse
    {
        $this->authorize('create', Shipping::class);

        $this->shippingService->createForOrder($order, $request->validated());

        return back()->with('success', 'Shipment created.');
    }

    public function update(UpdateShippingRequest $request, Shipping $shipping): RedirectResponse
    {
        $this->authorize('update', $shipping);

        $this->shippingService->update($shipping, $request->validated());

        return back()->with('success', 'Shipment updated.');
    }

    public function storeEvent(StoreTrackingEventRequest $request, Shipping $shipping): RedirectResponse
    {
        $this->authorize('update', $shipping);

        $this->shippingService->addTrackingEvent($shipping, $request->validated());

        return back()->with('success', 'Tracking event added.');
    }

    public function printLabel(Shipping $shipping): View
    {
        $this->authorize('view', $shipping);

        $shipping->load([
            'order.items',
            'order.shippingAddress',
            'order.billingAddress',
        ]);

        return view('admin.shipping.label', ['shipment' => $shipping]);
    }
}
