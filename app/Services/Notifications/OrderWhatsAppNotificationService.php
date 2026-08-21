<?php

namespace App\Services\Notifications;

use App\Models\ActivityLog;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\SiteSetting;
use App\Services\ActivityLogService;
use App\Services\Admin\InvoiceService;
use App\Support\OrderTrackingUrl;
use Illuminate\Support\Facades\Log;

class OrderWhatsAppNotificationService
{
    public function __construct(
        private readonly InvoiceService $invoices,
        private readonly WhatsAppNotificationService $whatsapp,
        private readonly ActivityLogService $activityLog,
    ) {}

    public function sendForOrder(int $orderId): bool
    {
        Log::info('Order WhatsApp delivery started.', ['order_id' => $orderId]);

        if ($this->alreadySent($orderId)) {
            Log::info('Order WhatsApp skipped: already sent.', ['order_id' => $orderId]);

            return true;
        }

        $order = Order::query()
            ->with(['user.notificationPreference'])
            ->find($orderId);

        if (! $order || blank($order->customer_phone)) {
            Log::info('Order WhatsApp skipped: order or customer phone missing.', ['order_id' => $orderId]);

            return false;
        }

        Log::info('Order WhatsApp order loaded.', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
            'customer_phone' => $order->customer_phone,
        ]);

        $settings = SiteSetting::current();

        if (! $settings->areWhatsappNotificationsEnabled()) {
            Log::info('Order WhatsApp skipped: site notifications disabled.', ['order_id' => $orderId]);

            return false;
        }

        if ($this->customerOptedOut($order)) {
            Log::info('Order WhatsApp skipped: customer opted out.', ['order_id' => $orderId]);

            return false;
        }

        if (! $this->whatsapp->isConfigured()) {
            Log::warning('Order WhatsApp skipped: invalid API configuration.', [
                'order_id' => $orderId,
                'issues' => $this->whatsapp->configurationIssues(),
            ]);

            return false;
        }

        $invoice = $this->invoices->generateFromOrder($order);
        $message = $this->buildMessage($order, $invoice->invoice_number, $settings);
        $templateBodyParameters = $this->buildTemplateBodyParameters($order, $invoice, $settings);

        $sent = $this->whatsapp->sendOrderConfirmation(
            phone: $order->customer_phone,
            orderId: $order->id,
            templateBodyParameters: $templateBodyParameters,
            fallbackText: $message,
        );

        if (! $sent) {
            Log::warning('Order WhatsApp delivery failed.', [
                'order_id' => $orderId,
                'order_number' => $order->order_number,
                'phone' => $order->customer_phone,
                'message_mode' => $this->whatsapp->usesTemplateMode() ? 'template' : 'text',
            ]);

            return false;
        }

        $this->activityLog->log('order.whatsapp_sent', $order, [], [
            'phone' => $order->customer_phone,
            'invoice_number' => $invoice->invoice_number,
            'message_mode' => $this->whatsapp->usesTemplateMode() ? 'template' : 'text',
            'template_name' => config('services.whatsapp.order_template'),
        ]);

        Log::info('Order WhatsApp delivery completed.', [
            'order_id' => $order->id,
            'order_number' => $order->order_number,
        ]);

        return true;
    }

    /** @return list<string> */
    public function buildTemplateBodyParameters(Order $order, Invoice $invoice, ?SiteSetting $settings = null): array
    {
        if ((string) config('services.whatsapp.order_template', 'hello_world') === 'hello_world') {
            return [];
        }

        $settings ??= SiteSetting::current();
        $currencySymbol = config('shop.currency_symbol', 'Rs.');

        return array_map(
            fn ($value): string => (string) $value,
            [
                $settings->displayName(),
                $order->order_number,
                $invoice->invoice_number,
                $currencySymbol.' '.number_format((float) $order->grand_total, 2),
                $this->formatLabel($order->payment_method?->value),
            ],
        );
    }

    public function buildMessage(Order $order, string $invoiceNumber, ?SiteSetting $settings = null): string
    {
        $settings ??= SiteSetting::current();
        $currencySymbol = config('shop.currency_symbol', 'Rs.');
        $supportNumber = filled($settings->contact_phone)
            ? $settings->contact_phone
            : ($settings->whatsapp ?? 'N/A');

        return implode("\n", array_filter([
            'Thank you for your order at '.$settings->displayName().'!',
            '',
            'Order Number: '.$order->order_number,
            'Invoice Number: '.$invoiceNumber,
            'Order Total: '.$currencySymbol.' '.number_format((float) $order->grand_total, 2),
            'Payment Method: '.$this->formatLabel($order->payment_method?->value),
            'Order Status: '.$order->status_label,
            '',
            'Track Order: '.OrderTrackingUrl::forOrder($order),
            '',
            'Support: '.$supportNumber,
        ]));
    }

    private function alreadySent(int $orderId): bool
    {
        return ActivityLog::query()
            ->where('action', 'order.whatsapp_sent')
            ->where('model_type', Order::class)
            ->where('model_id', $orderId)
            ->exists();
    }

    private function customerOptedOut(Order $order): bool
    {
        $preference = $order->user?->notificationPreference;

        if (! $preference) {
            return false;
        }

        return $preference->sms_orders === false;
    }

    private function formatLabel(?string $value): string
    {
        if (blank($value)) {
            return 'N/A';
        }

        return str($value)->replace('_', ' ')->title()->value();
    }
}
