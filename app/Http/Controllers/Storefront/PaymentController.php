<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\View\View;

class PaymentController extends Controller
{
    use AuthorizesRequests;

    public function show(Order $order): View
    {
        if (session('shop.last_order_id') !== $order->id) {
            $this->authorize('view', $order);
        }

        $order->load('payments');
        $bankDetails = config('payments.bank_transfer');

        return view('storefront.payment.show', compact('order', 'bankDetails'));
    }
}
