@props(['current' => 'cart'])

@php
    $steps = [
        ['key' => 'cart', 'label' => 'Cart', 'route' => 'shop.cart.index'],
        ['key' => 'checkout', 'label' => 'Checkout', 'route' => 'shop.checkout.index'],
        ['key' => 'confirmation', 'label' => 'Confirmation', 'route' => null],
    ];

    $currentIndex = collect($steps)->search(fn ($step) => $step['key'] === $current) ?: 0;
@endphp

<nav class="checkout-progress" aria-label="Checkout progress">
    <ol class="checkout-progress__list">
        @foreach ($steps as $index => $step)
            @php
                $isComplete = $index < $currentIndex;
                $isCurrent = $step['key'] === $current;
                $isUpcoming = $index > $currentIndex;
            @endphp
            <li class="checkout-progress__item @if($isComplete) is-complete @elseif($isCurrent) is-current @else is-upcoming @endif">
                @if ($step['route'] && ! $isUpcoming)
                    <a href="{{ route($step['route']) }}" class="checkout-progress__link">
                        <span class="checkout-progress__marker">{{ $index + 1 }}</span>
                        <span class="checkout-progress__label">{{ $step['label'] }}</span>
                    </a>
                @else
                    <span class="checkout-progress__link" @if($isUpcoming) aria-disabled="true" @endif>
                        <span class="checkout-progress__marker">{{ $index + 1 }}</span>
                        <span class="checkout-progress__label">{{ $step['label'] }}</span>
                    </span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>
