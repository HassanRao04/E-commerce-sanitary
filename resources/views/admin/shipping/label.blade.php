@extends('layouts.print')

@section('title', 'Shipping Label — '.$label['orderNumber'])

@push('head')
    @include('admin.shipping.partials.label-styles')
@endpush

@section('content')
    <div class="no-print label-toolbar">
        <button type="button" onclick="window.print()">Print</button>
        <button type="button" onclick="window.close()">Close</button>
        <span class="label-toolbar-divider">|</span>
        <a href="{{ route('admin.shipping.label', ['shipping' => $shipment, 'format' => 'a4']) }}"
           class="label-format-link {{ $format === 'a4' ? 'is-active' : '' }}">A5</a>
        <a href="{{ route('admin.shipping.label', ['shipping' => $shipment, 'format' => 'thermal']) }}"
           class="label-format-link {{ $format === 'thermal' ? 'is-active' : '' }}">4×6 Thermal</a>
    </div>

    <div class="shipping-label shipping-label--{{ $format }}" data-format="{{ $format }}">
        {{-- Store --}}
        <section class="shipping-label__block shipping-label__block--store">
            @if ($label['storeLogoUrl'])
                <img src="{{ $label['storeLogoUrl'] }}" alt="{{ $label['storeName'] }}" class="shipping-label__logo">
            @endif
            <p class="shipping-label__store-name">{{ $label['storeName'] }}</p>
            @if ($label['storeAddress'])
                <p class="shipping-label__line">{{ $label['storeAddress'] }}</p>
            @endif
            @if ($label['storePhone'])
                <p class="shipping-label__line">{{ $label['storePhone'] }}</p>
            @endif
        </section>

        {{-- Ship To --}}
        <section class="shipping-label__block">
            <p class="shipping-label__heading">Ship To</p>
            <p class="shipping-label__line shipping-label__line--name">{{ $label['customerName'] }}</p>
            @if ($label['customerPhone'])
                <p class="shipping-label__line">{{ $label['customerPhone'] }}</p>
            @endif
            @php($addr = $label['shippingAddress'])
            @if (filled($addr['line1']))
                <p class="shipping-label__line">{{ $addr['line1'] }}</p>
            @endif
            @if (filled($addr['line2']))
                <p class="shipping-label__line">{{ $addr['line2'] }}</p>
            @endif
            @if (blank($addr['line1']) && blank($addr['line2']) && $label['addressLines'] !== [])
                @foreach ($label['addressLines'] as $line)
                    <p class="shipping-label__line">{{ $line }}</p>
                @endforeach
            @endif
            @if (filled($addr['city']))
                <p class="shipping-label__line">{{ $addr['city'] }}</p>
            @endif
            @if (filled($addr['country']))
                <p class="shipping-label__line">{{ $addr['country'] }}</p>
            @endif
        </section>

        {{-- Order --}}
        <section class="shipping-label__block">
            <p class="shipping-label__row">
                <span class="shipping-label__row-label">Order #:</span>
                <span class="shipping-label__mono">{{ $label['orderNumber'] }}</span>
            </p>
            @if ($label['invoiceNumber'])
                <p class="shipping-label__row">
                    <span class="shipping-label__row-label">Invoice #:</span>
                    <span class="shipping-label__mono">{{ $label['invoiceNumber'] }}</span>
                </p>
            @endif
            @if ($label['courierName'])
                <p class="shipping-label__row">
                    <span class="shipping-label__row-label">Courier:</span>
                    <span>{{ $label['courierName'] }}</span>
                </p>
            @endif
            @if ($label['trackingNumber'])
                <p class="shipping-label__row">
                    <span class="shipping-label__row-label">Tracking #:</span>
                    <span class="shipping-label__mono">{{ $label['trackingNumber'] }}</span>
                </p>
            @endif
            @if ($label['shipmentDate'])
                <p class="shipping-label__row">
                    <span class="shipping-label__row-label">Shipment Date:</span>
                    <span>{{ $label['shipmentDate']->format('d M Y') }}</span>
                </p>
            @endif
        </section>

        {{-- Products --}}
        @if ($label['items']->isNotEmpty())
            <section class="shipping-label__block">
                <p class="shipping-label__heading">Products</p>
                @foreach ($label['items'] as $item)
                    <p class="shipping-label__product-row">
                        <span class="shipping-label__product-name">{{ $item->product_name }}</span>
                        <span class="shipping-label__product-qty">Qty:{{ $item->quantity }}</span>
                    </p>
                @endforeach
            </section>
        @endif

        {{-- Payment --}}
        @if ($label['order'])
            <section class="shipping-label__block">
                <p class="shipping-label__row shipping-label__row--amount">
                    <span class="shipping-label__row-label shipping-label__row-label--upper">Order Amount</span>
                    <span><x-money :amount="$label['orderAmount']" /></span>
                </p>
                <p class="shipping-label__row shipping-label__row--amount shipping-label__row--cod">
                    <span class="shipping-label__row-label shipping-label__row-label--upper">COD To Collect</span>
                    <span><x-money :amount="$label['codAmountToCollect']" /></span>
                </p>
                @if ($label['paymentMethod'])
                    <p class="shipping-label__row">
                        <span class="shipping-label__row-label">Payment Method</span>
                        <span>{{ str($label['paymentMethod'])->upper()->value() === 'COD' ? 'COD' : $label['paymentMethod'] }}</span>
                    </p>
                @endif
                @if ($label['paymentStatus'])
                    <p class="shipping-label__row">
                        <span class="shipping-label__row-label">Payment Status</span>
                        <span>{{ $label['paymentStatus'] }}</span>
                    </p>
                @endif
            </section>
        @endif

        {{-- Bottom --}}
        @if ($label['barcodeValue'])
            <section class="shipping-label__block shipping-label__block--bottom">
                <p class="shipping-label__heading">Barcode</p>
                <svg id="shipping-label-barcode" class="shipping-label__barcode" aria-label="Barcode for {{ $label['barcodeValue'] }}"></svg>
                @if ($label['trackingNumber'])
                    <p class="shipping-label__mono shipping-label__tracking-no">{{ $label['trackingNumber'] }}</p>
                @endif

                <p class="shipping-label__heading shipping-label__heading--qr">QR Code</p>
                <canvas id="shipping-label-qrcode" class="shipping-label__qrcode" aria-label="QR code for shipment"></canvas>
            </section>
        @endif
    </div>

    @if ($label['barcodeValue'])
        @include('admin.shipping.partials.label-scripts', [
            'barcodeValue' => $label['barcodeValue'],
            'scanPayload' => $label['scanPayload'],
            'format' => $format,
        ])
    @endif
@endsection
