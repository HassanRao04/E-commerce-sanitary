@props([
    'options' => null,
    'item' => null,
    'fallback' => null,
    'class' => '',
])

@php
    use App\Support\VariantOptionFormatter;

    $resolvedOptions = $options;

    if ($resolvedOptions === null && $item !== null) {
        $resolvedOptions = $item->variant_options
            ?? ($item->productVariant ? VariantOptionFormatter::forVariant($item->productVariant) : []);
    }

    $resolvedOptions = $resolvedOptions ?? [];
    $label = VariantOptionFormatter::labelOrFallback($resolvedOptions, $fallback ?? ($item->variant_name ?? null));
@endphp

@if ($resolvedOptions !== [])
    <ul {{ $attributes->merge(['class' => trim('variant-options '.$class)]) }}>
        @foreach ($resolvedOptions as $option)
            <li>
                <span class="variant-options__name">{{ $option['name'] }}:</span>
                <span class="variant-options__value">{{ $option['value'] }}</span>
            </li>
        @endforeach
    </ul>
@elseif ($label)
    <p {{ $attributes->merge(['class' => trim('variant-options variant-options--fallback '.$class)]) }}>{{ $label }}</p>
@endif
