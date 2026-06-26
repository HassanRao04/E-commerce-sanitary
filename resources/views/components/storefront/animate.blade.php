@props([
    'effect' => 'fade-up',
    'delay' => 0,
    'duration' => null,
    'offset' => null,
    'once' => true,
    'hover' => null,
    'as' => 'div',
])

@php
    $aosMap = [
        'fade-up' => 'fade-up',
        'fade-in' => 'fade',
        'slide-left' => 'fade-left',
        'slide-right' => 'fade-right',
        'scale' => 'zoom-in',
        'zoom-in' => 'zoom-in',
    ];

    $aosEffect = $aosMap[$effect] ?? $effect;
    $tag = $as;
@endphp

<{{ $tag }}
    {{ $attributes->class([
        'anim-gpu',
        'anim-fade-up' => $effect === 'fade-up',
        'anim-fade-in' => $effect === 'fade-in',
        'anim-slide-left' => $effect === 'slide-left',
        'anim-slide-right' => $effect === 'slide-right',
        'anim-scale' => in_array($effect, ['scale', 'zoom-in'], true),
    ]) }}
    data-aos="{{ $aosEffect }}"
    @if ($delay > 0) data-aos-delay="{{ (int) $delay }}" @endif
    @if ($duration) data-aos-duration="{{ (int) $duration }}" @endif
    @if ($offset) data-aos-offset="{{ (int) $offset }}" @endif
    data-aos-once="{{ $once ? 'true' : 'false' }}"
    @if ($hover) data-gsap-hover="{{ $hover }}" @endif
>{{ $slot }}</{{ $tag }}>
