<?php

namespace App\Services\Couriers;

use App\DataTransferObjects\ShipmentBookingRequestDTO;
use App\Models\CourierProvider;
use App\Models\Order;
use App\Services\Couriers\Http\TcsApiClient;
use Illuminate\Support\Str;

class TcsBookingPayloadFactory
{
    public function make(ShipmentBookingRequestDTO $request, CourierProvider $provider): array
    {
        $order = $request->order->loadMissing(['items', 'shippingAddress']);
        $address = $order->shippingAddress;
        $nameParts = $this->splitName((string) $order->customer_name);
        $weight = max(0.5, (float) ($request->weightKg ?? $provider->default_package_weight ?? 0.5));
        $pieces = max(1, (int) ($request->pieces ?? 1));

        return [
            'shipperinfo' => [
                'tcsaccount' => (string) ($provider->account_number ?? config('couriers.providers_config.tcs.defaults.account_number')),
                'shippername' => $provider->name,
                'address1' => (string) ($provider->pickup_address ?? 'Pickup address not configured'),
                'address2' => '',
                'address3' => '',
                'zip' => '',
                'countrycode' => 'PK',
                'countryname' => 'Pakistan',
                'cityname' => (string) ($provider->pickup_city ?? 'Karachi'),
                'mobile' => $this->normalizePhone((string) config('shop.contact_phone', '03001234567')),
            ],
            'consigneeinfo' => [
                'firstname' => $nameParts['first'],
                'middlename' => $nameParts['middle'],
                'lastname' => $nameParts['last'],
                'address1' => (string) ($address?->line1 ?? 'Address on file'),
                'address2' => (string) ($address?->line2 ?? ''),
                'address3' => '',
                'zip' => (string) ($address?->postal_code ?? ''),
                'countrycode' => 'PK',
                'countryname' => 'Pakistan',
                'cityname' => (string) ($address?->city ?? 'Karachi'),
                'email' => (string) $order->customer_email,
                'mobile' => $this->normalizePhone((string) ($order->customer_phone ?? '03001234567')),
            ],
            'shipmentinfo' => [
                'costcentercode' => (string) ($provider->config['costcentercode'] ?? config('couriers.providers_config.tcs.defaults.costcentercode')),
                'referenceno' => (string) $order->order_number,
                'contentdesc' => 'Order '.$order->order_number,
                'servicecode' => (string) ($provider->config['servicecode'] ?? config('couriers.providers_config.tcs.defaults.servicecode')),
                'currency' => (string) config('couriers.providers_config.tcs.defaults.currency', 'PKR'),
                'codamount' => (int) round($request->codAmount ?? 0),
                'weightinkg' => $weight,
                'pieces' => $pieces,
                'fragile' => false,
                'remarks' => 'ERP booking for order '.$order->order_number,
                'skus' => $order->items->map(fn ($item): array => [
                    'description' => Str::limit((string) $item->product_name, 50, ''),
                    'quantity' => (int) $item->quantity,
                    'weight' => round($weight / max(1, $order->items->count()), 2),
                    'uom' => 'KG',
                    'unitprice' => (int) round((float) $item->unit_price),
                    'declaredvalue' => null,
                    'insuredvalue' => null,
                ])->values()->all(),
            ],
        ];
    }

    /** @return array{first: string, middle: string, last: string} */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/', trim($name)) ?: [];

        return [
            'first' => $parts[0] ?? 'Customer',
            'middle' => $parts[1] ?? 'N',
            'last' => implode(' ', array_slice($parts, 2)) ?: '.',
        ];
    }

    private function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) === 10 && str_starts_with($digits, '3')) {
            $digits = '0'.$digits;
        }

        if (strlen($digits) === 12 && str_starts_with($digits, '92')) {
            $digits = '0'.substr($digits, 2);
        }

        return str_pad(substr($digits, 0, 11), 11, '0');
    }
}
