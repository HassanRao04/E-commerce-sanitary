<?php

namespace App\Services;

use App\Enums\AddressType;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\ProductStatus;
use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Support\VariantOptionFormatter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutRulesEngine $rulesEngine,
        private readonly ProductOfferCalculatorService $productOffers,
        private readonly PaymentService $paymentService,
        private readonly ActivityLogService $activityLog,
        private readonly OrderNumberService $orderNumberService,
        private readonly InventoryStockService $inventoryStock,
        private readonly StockAvailabilityService $stockAvailability,
        private readonly OrderWorkflowService $workflow,
        private readonly CouponService $couponService,
    ) {}

    public function placeOrder(Cart $cart, array $data): Order
    {
        if (! Auth::check()) {
            throw ValidationException::withMessages([
                'auth' => 'You must be signed in to place an order.',
            ]);
        }

        return DB::transaction(function () use ($cart, $data) {
            $cart = $cart->load([
                'items.product',
                'items.productVariant',
                'items.productOffer',
                'items.pipeLengthOption',
                'coupon',
            ]);

            $this->assertCartIsValid($cart);
            $this->rulesEngine->validateForCheckout($cart);

            $totals = $this->rulesEngine->calculate($cart);
            $paymentMethod = PaymentMethod::from($data['payment_method']);
            $shippingAddress = $this->resolveShippingAddress($data);
            $billingAddress = $this->resolveBillingAddress($data, $shippingAddress);

            $order = Order::create([
                'order_number' => $this->orderNumberService->generate(),
                'tracking_token' => $this->generateTrackingToken(),
                'user_id' => Auth::id(),
                'customer_name' => $data['customer_name'],
                'customer_email' => $data['customer_email'],
                'customer_phone' => $data['customer_phone'],
                'billing_address_id' => $billingAddress?->id,
                'shipping_address_id' => $shippingAddress?->id,
                'status' => $this->workflow->defaultSlug(),
                'payment_status' => PaymentStatus::Pending,
                'payment_method' => $paymentMethod,
                'subtotal' => $totals['subtotal'],
                'discount_total' => $totals['discount'],
                'offer_discount_total' => $totals['offer_discount'],
                'shipping_total' => $totals['shipping'],
                'shipping_discount_total' => $totals['shipping_discount'],
                'service_charge_total' => $totals['service_charge'],
                'handling_charge_total' => $totals['handling_charge'],
                'tax_total' => $totals['tax'],
                'tax_type' => $totals['tax_type'],
                'grand_total' => $totals['grand_total'],
                'coupon_code' => $totals['coupon_code'],
                'notes' => $this->buildOrderNotes($data, $shippingAddress, $billingAddress),
            ]);

            $this->createOrderItems($cart, $order);
            $this->recordStatusHistory($order);
            $this->incrementCouponUsage($cart, $totals);
            $this->couponService->trackInfluencerOrder($order, $cart->coupon);
            $paymentResult = $this->paymentService->initiate($order, $paymentMethod);

            $this->activityLog->log('order.placed', $order, [], [
                'order_number' => $order->order_number,
                'payment_method' => $paymentMethod->value,
                'grand_total' => $order->grand_total,
            ]);

            $this->cartService->clear($cart);

            session()->put('shop.last_order_id', $order->id);
            session()->put('shop.payment_redirect', $paymentResult->redirectUrl);

            return $order->fresh(['items', 'payments', 'billingAddress', 'shippingAddress']);
        });
    }

    private function assertCartIsValid(Cart $cart): void
    {
        if ($cart->items->isEmpty()) {
            throw ValidationException::withMessages([
                'cart' => 'Your cart is empty.',
            ]);
        }

        foreach ($cart->items as $item) {
            $variant = $item->productVariant;

            if (! $item->product || $item->product->status !== ProductStatus::Active) {
                throw ValidationException::withMessages([
                    'cart' => "{$item->product?->name} is no longer available.",
                ]);
            }

            if (! $variant || ! $variant->is_active) {
                throw ValidationException::withMessages([
                    'cart' => "A selected variation for {$item->product->name} is no longer available.",
                ]);
            }

            $available = $this->stockAvailability->availableQuantity($variant, $item->quantity);

            if ($item->quantity > $available) {
                throw ValidationException::withMessages([
                    'cart' => "{$item->product->name} only has {$available} units in stock.",
                ]);
            }
        }
    }

    private function createOrderItems(Cart $cart, Order $order): void
    {
        foreach ($cart->items as $item) {
            $variantOptions = $item->variant_options
                ?? VariantOptionFormatter::forVariant($item->productVariant);
            $snapshot = $this->productOffers->snapshotLine($item);

            OrderItem::create([
                'order_id' => $order->id,
                'product_id' => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
                'product_name' => $item->product->name,
                'variant_name' => VariantOptionFormatter::labelOrFallback(
                    $variantOptions,
                    $item->productVariant->variant_name,
                ),
                'variant_options' => $variantOptions,
                'sku' => $item->productVariant->sku,
                'quantity' => $item->quantity,
                'unit_price' => $snapshot['unit_price'],
                'original_unit_price' => $snapshot['original_unit_price'],
                'selected_offer' => $snapshot['selected_offer'],
                'option_title' => $snapshot['option_title'],
                'discount_percent' => $snapshot['discount_percent'],
                'discount_amount' => $snapshot['discount_amount'],
                'pipe_length' => $snapshot['pipe_length'],
                'pipe_extra_cost' => $snapshot['pipe_extra_cost'],
                'total' => $snapshot['total'],
            ]);

            $this->inventoryStock->decrementForSale(
                $item->productVariant,
                $item->quantity,
                Order::class,
                $order->id,
                "Order {$order->order_number}",
            );
        }
    }

    private function recordStatusHistory(Order $order): void
    {
        OrderStatusHistory::create([
            'order_id' => $order->id,
            'status' => $this->workflow->defaultSlug(),
            'note' => 'Order placed via storefront checkout.',
        ]);
    }

    private function incrementCouponUsage(Cart $cart, array $totals): void
    {
        // Only count usage when the coupon actually reduced the total (same gate as coupon_code persistence).
        if ($cart->coupon && ! empty($totals['coupon_code'])) {
            $cart->coupon->increment('used_count');
        }
    }

    private function generateTrackingToken(): string
    {
        do {
            $token = Str::random(32);
        } while (Order::query()->where('tracking_token', $token)->exists());

        return $token;
    }

    private function resolveShippingAddress(array $data): ?Address
    {
        if (! empty($data['shipping_address_id'])) {
            return Address::query()
                ->where('user_id', Auth::id())
                ->findOrFail($data['shipping_address_id']);
        }

        if ($this->hasAddressInput($data, 'shipping_')) {
            return $this->createAddress($data, AddressType::Shipping);
        }

        return null;
    }

    private function resolveBillingAddress(array $data, ?Address $shippingAddress): ?Address
    {
        if ($this->booleanValue($data, 'billing_same_as_shipping') && $shippingAddress) {
            return $shippingAddress;
        }

        if (! empty($data['billing_address_id'])) {
            return Address::query()
                ->where('user_id', Auth::id())
                ->findOrFail($data['billing_address_id']);
        }

        if ($this->hasAddressInput($data, 'billing_')) {
            return $this->createAddress($data, AddressType::Billing);
        }

        return $shippingAddress;
    }

    private function createAddress(array $data, AddressType $type): Address
    {
        $prefix = $type === AddressType::Shipping ? 'shipping_' : 'billing_';

        return Address::create([
            'user_id' => Auth::id(),
            'type' => $type,
            'full_name' => $data[$prefix.'full_name'] ?? $data['customer_name'],
            'phone' => $data[$prefix.'phone'] ?? $data['customer_phone'],
            'line1' => $data[$prefix.'line1'],
            'line2' => $data[$prefix.'line2'] ?? null,
            'city' => $data[$prefix.'city'],
            'state' => $data[$prefix.'state'] ?? null,
            'postal_code' => $data[$prefix.'postal_code'] ?? null,
            'country' => $data[$prefix.'country'] ?? 'Pakistan',
            'is_default' => false,
        ]);
    }

    private function buildOrderNotes(array $data, ?Address $shippingAddress, ?Address $billingAddress): ?string
    {
        $lines = array_filter([$data['notes'] ?? null]);

        return $lines ? implode("\n", $lines) : null;
    }

    private function hasAddressInput(array $data, string $prefix): bool
    {
        return filled($data[$prefix.'line1'] ?? null)
            && filled($data[$prefix.'city'] ?? null);
    }

    private function booleanValue(array $data, string $key): bool
    {
        return filter_var($data[$key] ?? false, FILTER_VALIDATE_BOOLEAN);
    }
}
