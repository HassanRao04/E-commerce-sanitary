@props([
    'item',
])

@php
    $hasOffer = filled($item->selected_offer ?? null) || (float) ($item->discount_amount ?? 0) > 0;
    $hasPipe = filled($item->pipe_length ?? null) || (float) ($item->pipe_extra_cost ?? 0) > 0;
@endphp

@if ($hasOffer || $hasPipe)
    <ul {{ $attributes->class(['order-item-offer-meta text-xs space-y-0.5 mt-1']) }}>
        @if ($hasOffer)
            <li>
                <span class="opacity-70">Selected Offer:</span>
                {{ $item->selected_offer ?: 'Applied' }}
                @if ((float) ($item->discount_percent ?? 0) > 0)
                    ({{ rtrim(rtrim(number_format((float) $item->discount_percent, 2), '0'), '.') }}%)
                @endif
                @if ((float) ($item->discount_amount ?? 0) > 0)
                    — Discount: <x-money :amount="$item->discount_amount" />
                @endif
            </li>
        @endif
        @if ($hasPipe)
            <li>
                <span class="opacity-70">{{ $item->option_title ?: 'Option' }}:</span>
                {{ $item->pipe_length ?: '—' }}
                @if ((float) ($item->pipe_extra_cost ?? 0) > 0)
                    — Extra cost: <x-money :amount="$item->pipe_extra_cost" />
                @endif
            </li>
        @endif
        @if ($item->original_unit_price !== null && (float) ($item->pipe_extra_cost ?? 0) > 0)
            <li>
                <span class="opacity-70">Original Price:</span>
                <x-money :amount="$item->original_unit_price" />
            </li>
        @endif
    </ul>
@endif
