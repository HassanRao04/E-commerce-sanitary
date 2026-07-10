<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storefront\CheckoutRequest;
use App\Models\Order;
use App\Services\CartService;
use App\Services\CheckoutPricingService;
use App\Services\CheckoutService;
use App\Services\PaymentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    use AuthorizesRequests;
    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutPricingService $pricing,
        private readonly CheckoutService $checkoutService,
        private readonly PaymentService $paymentService,
    ) {}

    public function index(): View|RedirectResponse
    {
        $cart = $this->cartService->getCart();

        if ($cart->items->isEmpty()) {
            return redirect()->route('shop.cart.index')->with('error', 'Your cart is empty.');
        }

        $pricing = $this->pricing->calculate($cart);
        $paymentMethods = $this->paymentService->enabledMethods();
        $addresses = auth()->user()?->addresses ?? collect();

        return view('storefront.checkout.index', [
            'cart' => $cart,
            'totals' => collect($pricing)->only(['subtotal', 'discount', 'shipping', 'service_charge', 'handling_charge', 'tax', 'grand_total'])->all(),
            'pricing' => $pricing,
            'paymentMethods' => $paymentMethods,
            'shippingAddresses' => $addresses,
            'billingAddresses' => $addresses,
        ]);
    }

    public function store(CheckoutRequest $request): RedirectResponse
    {
        $cart = $this->cartService->resolve();
        $order = $this->checkoutService->placeOrder($cart, $request->validated());

        $redirectUrl = session()->pull('shop.payment_redirect');

        if ($redirectUrl && $order->payment_method->value !== 'cod') {
            return redirect($redirectUrl);
        }

        return redirect()->route('shop.checkout.success', $order);
    }

    public function success(Order $order): View
    {
        if (session('shop.last_order_id') !== $order->id) {
            $this->authorize('view', $order);
        }

        $order->load(['items', 'payments', 'billingAddress', 'shippingAddress']);

        return view('storefront.checkout.success', compact('order'));
    }
}
