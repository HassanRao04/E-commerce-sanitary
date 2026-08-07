<?php

namespace App\Services\Admin;

use App\Enums\PaymentMethod;
use App\Models\Order;
use App\Models\Shipping;
use App\Models\SiteSetting;

class ShippingLabelService
{
    /** @return array<string, mixed> */
    public function prepare(Shipping $shipment, string $format = 'a4'): array
    {
        $shipment->loadMissing([
            'order.items',
            'order.shippingAddress',
            'order.invoice',
            'courierProvider',
        ]);

        $order = $shipment->order;
        $address = $order?->shippingAddress;
        $settings = SiteSetting::current();
        $pricing = $this->pricingBreakdown($order);
        $shipmentDate = $shipment->booked_at ?? $shipment->shipped_at ?? $shipment->created_at;

        $addressLines = $address
            ? array_values(array_filter([
                $address->line1,
                $address->line2,
                trim(collect([$address->city, $address->state, $address->postal_code])->filter()->implode(', ')),
                $address->country,
            ]))
            : array_filter([$order?->notes ?? 'Address on file']);

        $barcodeValue = $shipment->tracking_number
            ?? $shipment->awb_number
            ?? $order?->order_number
            ?? '';

        return [
            'format' => $this->normalizeFormat($format),
            'storeName' => $settings->displayName(),
            'storeLogoUrl' => $settings->logo_url,
            'storePhone' => $settings->contact_phone,
            'storeAddress' => $settings->address,
            'customerName' => $order?->customer_name,
            'customerPhone' => $order?->customer_phone,
            'shippingAddress' => [
                'line1' => $address?->line1,
                'line2' => $address?->line2,
                'city' => $address?->city,
                'province' => $address?->state,
                'postalCode' => $address?->postal_code,
                'country' => $address?->country,
            ],
            'addressLines' => $addressLines,
            'orderNumber' => $order?->order_number,
            'order' => $order,
            'orderAmount' => $pricing['grandTotal'],
            'codAmountToCollect' => $this->amountToCollect($order),
            'grandTotal' => $pricing['grandTotal'],
            'amountToCollect' => $this->amountToCollect($order),
            'paymentMethod' => $this->formatEnumLabel($order?->payment_method?->value),
            'paymentStatus' => $this->formatEnumLabel($order?->payment_status?->value),
            'invoiceNumber' => $order?->invoice?->invoice_number,
            'shipmentDate' => $shipmentDate,
            'shipmentDateFormatted' => $shipmentDate?->format('M j, Y'),
            'trackingNumber' => $shipment->tracking_number,
            'awbNumber' => $shipment->awb_number,
            'courierName' => $shipment->courier_name,
            'items' => $order?->items ?? collect(),
            'itemCount' => (int) ($order?->items?->sum('quantity') ?? 0),
            'barcodeValue' => $barcodeValue,
            'scanPayload' => $this->scanPayload($shipment),
        ];
    }

    public function normalizeFormat(?string $format): string
    {
        return in_array($format, ['a4', 'thermal'], true) ? $format : 'a4';
    }

    /** @return array{subtotal: float, shippingCharges: float, discountAmount: float, offerDiscount: float, couponDiscount: float, couponCode: ?string, grandTotal: float} */
    private function pricingBreakdown(?Order $order): array
    {
        if (! $order) {
            return [
                'subtotal' => 0.0,
                'shippingCharges' => 0.0,
                'discountAmount' => 0.0,
                'offerDiscount' => 0.0,
                'couponDiscount' => 0.0,
                'couponCode' => null,
                'grandTotal' => 0.0,
            ];
        }

        $offerDiscount = (float) ($order->offer_discount_total ?? 0);
        $discountAmount = (float) ($order->discount_total ?? 0);
        $shippingCharged = (float) ($order->shipping_total ?? 0);
        $shippingDiscount = (float) ($order->shipping_discount_total ?? 0);

        return [
            'subtotal' => (float) ($order->subtotal ?? 0),
            'shippingCharges' => round($shippingCharged + $shippingDiscount, 2),
            'discountAmount' => $discountAmount,
            'offerDiscount' => $offerDiscount,
            'couponDiscount' => max(0, round($discountAmount - $offerDiscount, 2)),
            'couponCode' => $order->coupon_code,
            'grandTotal' => (float) ($order->grand_total ?? 0),
        ];
    }

    private function amountToCollect(?Order $order): float
    {
        if (! $order || $order->payment_method !== PaymentMethod::Cod) {
            return 0.0;
        }

        return (float) ($order->grand_total ?? 0);
    }

    private function formatEnumLabel(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return str($value)->replace('_', ' ')->title()->value();
    }

    private function scanPayload(Shipping $shipment): string
    {
        $order = $shipment->order;

        return json_encode([
            'order' => $order?->order_number,
            'tracking' => $shipment->tracking_number,
            'awb' => $shipment->awb_number,
            'courier' => $shipment->courier_name,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }
}
