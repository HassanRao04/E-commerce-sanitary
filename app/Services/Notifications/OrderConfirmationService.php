<?php

namespace App\Services\Notifications;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\SiteSetting;
use App\Services\Admin\InvoiceService;
use App\Mail\OrderConfirmationMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class OrderConfirmationService
{
    public function __construct(
        private readonly InvoiceService $invoices,
    ) {}

    public function sendForOrder(int $orderId): void
    {
        $order = Order::query()
            ->with(['items', 'shippingAddress', 'user.notificationPreference'])
            ->find($orderId);

        if (! $order || blank($order->customer_email)) {
            return;
        }

        if ($this->customerOptedOut($order)) {
            return;
        }

        $invoice = $this->invoices->generateFromOrder($order);
        $invoice = $this->invoices->ensurePdf($invoice);
        $presentation = $this->buildPresentation($order, $invoice);

        Mail::to($order->customer_email)->send(new OrderConfirmationMail($presentation, $invoice));
    }

    /** @return array<string, mixed> */
    public function buildPresentation(Order $order, Invoice $invoice): array
    {
        $settings = SiteSetting::current();

        return [
            'storeName' => $settings->displayName(),
            'storeLogoUrl' => $this->absoluteLogoUrl($settings->logo_url),
            'customerName' => $order->customer_name,
            'orderNumber' => $order->order_number,
            'invoiceNumber' => $invoice->invoice_number,
            'orderDate' => $order->created_at?->timezone(config('app.timezone'))->format('F j, Y'),
            'paymentMethod' => $this->formatLabel($order->payment_method?->value),
            'paymentStatus' => $this->formatLabel($order->payment_status?->value),
            'grandTotal' => (float) $order->grand_total,
            'currencySymbol' => config('shop.currency_symbol', 'Rs.'),
            'items' => $order->items->map(fn ($item): array => [
                'name' => $item->product_name,
                'quantity' => (int) $item->quantity,
            ])->all(),
            'shippingAddress' => $this->formatShippingAddress($order),
            'trackOrderUrl' => $this->trackOrderUrl($order),
            'contactEmail' => $settings->inquiryNotificationEmail(),
            'contactPhone' => $settings->contact_phone,
            'storeAddress' => $settings->address,
            'supportWhatsapp' => $settings->whatsapp,
        ];
    }

    private function customerOptedOut(Order $order): bool
    {
        $preference = $order->user?->notificationPreference;

        if (! $preference) {
            return false;
        }

        return $preference->email_orders === false;
    }

    private function absoluteLogoUrl(?string $logoUrl): ?string
    {
        if (blank($logoUrl)) {
            return null;
        }

        return URL::to($logoUrl);
    }

    private function formatShippingAddress(Order $order): ?string
    {
        $address = $order->shippingAddress;

        if (! $address) {
            return null;
        }

        return collect([
            $address->line1,
            $address->line2,
            $address->city,
            $address->state,
            $address->postal_code,
            $address->country,
        ])->filter()->implode(', ');
    }

    private function trackOrderUrl(Order $order): string
    {
        if (filled($order->tracking_token)) {
            return route('shop.orders.track', [
                'tracking_token' => $order->tracking_token,
            ], absolute: true);
        }

        return route('shop.orders.track', absolute: true);
    }

    private function formatLabel(?string $value): string
    {
        if (blank($value)) {
            return 'N/A';
        }

        return str($value)->replace('_', ' ')->title()->value();
    }
}
