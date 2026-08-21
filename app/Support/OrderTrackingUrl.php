<?php

namespace App\Support;

use App\Models\Order;

class OrderTrackingUrl
{
    public static function forOrder(Order $order): string
    {
        $parameters = filled($order->tracking_token)
            ? ['tracking_token' => $order->tracking_token]
            : [];

        return route('shop.orders.track', $parameters, absolute: true);
    }
}
