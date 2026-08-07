<x-mail::message>
@if (! empty($data['storeLogoUrl']))
<p style="text-align: center; margin-bottom: 16px;">
<img src="{{ $data['storeLogoUrl'] }}" alt="{{ $data['storeName'] }}" style="max-height: 64px; max-width: 180px;">
</p>
@endif

# Thank you for your order

Hi **{{ $data['customerName'] }}**,

We have received your order at **{{ $data['storeName'] }}**. Here is a summary of your purchase.

Your invoice is attached to this email as a PDF.

## Order Details

| | |
|---|---|
| **Order Number** | {{ $data['orderNumber'] }} |
| **Invoice Number** | {{ $data['invoiceNumber'] }} |
| **Order Date** | {{ $data['orderDate'] }} |
| **Payment Method** | {{ $data['paymentMethod'] }} |
| **Payment Status** | {{ $data['paymentStatus'] }} |

## Products

@foreach ($data['items'] as $item)
- **{{ $item['name'] }}** — Qty: {{ $item['quantity'] }}
@endforeach

**Grand Total:** {{ $data['currencySymbol'] }} {{ number_format($data['grandTotal'], 2) }}

@if (! empty($data['shippingAddress']))
## Shipping Address

{{ $data['shippingAddress'] }}
@endif

<x-mail::button :url="$data['trackOrderUrl']">
Track Order
</x-mail::button>

## Contact Us

@if (! empty($data['contactPhone']))
**Phone:** {{ $data['contactPhone'] }}
@endif

@if (! empty($data['contactEmail']))
**Email:** [{{ $data['contactEmail'] }}](mailto:{{ $data['contactEmail'] }})
@endif

@if (! empty($data['storeAddress']))
**Address:** {{ $data['storeAddress'] }}
@endif

@if (! empty($data['supportWhatsapp']))
**WhatsApp:** {{ $data['supportWhatsapp'] }}
@endif

---

If you have any questions about your order, reply to this email or contact us using the details above.

Thanks,<br>
**{{ $data['storeName'] }}**

<x-slot:subcopy>
This is an automated confirmation for order **{{ $data['orderNumber'] }}**. Please keep this email for your records.
</x-slot:subcopy>
</x-mail::message>
