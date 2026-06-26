<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\TrackOrderRequest;
use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OrderTrackingController extends Controller
{
    public function index(Request $request): View
    {
        if ($request->filled('tracking_token')) {
            $order = Order::query()
                ->where('tracking_token', $request->string('tracking_token')->toString())
                ->first();

            if ($order) {
                return $this->renderOrder($order);
            }
        }

        return view('storefront.orders.track');
    }

    public function show(TrackOrderRequest $request): View|RedirectResponse
    {
        $order = $this->findOrder($request->validated());

        if (! $order) {
            return back()
                ->withInput()
                ->with('error', 'We could not find an order matching those details. Please check and try again.');
        }

        return $this->renderOrder($order);
    }

    private function renderOrder(Order $order): View
    {
        $order->load([
            'items',
            'statusHistories' => fn ($q) => $q->orderByDesc('created_at'),
            'shipments.trackingEvents' => fn ($q) => $q->orderByDesc('event_at'),
        ]);

        return view('storefront.orders.track', compact('order'));
    }

    /** @param array<string, mixed> $data */
    private function findOrder(array $data): ?Order
    {
        if (! empty($data['tracking_token'])) {
            return Order::query()
                ->where('tracking_token', $data['tracking_token'])
                ->first();
        }

        return Order::query()
            ->where('order_number', strtoupper($data['order_number']))
            ->where('customer_email', strtolower($data['email']))
            ->first();
    }
}
