@props([
    'rating' => 0,
    'count' => 0,
    'size' => 'sm',
])

@php
    $rating = max(0, min(5, (float) $rating));
    $fullStars = (int) floor($rating);
    $hasHalf = ($rating - $fullStars) >= 0.5;
    $sizeClass = match ($size) {
        'md' => 'h-5 w-5',
        'sm' => 'h-3.5 w-3.5',
        default => 'h-4 w-4',
    };
@endphp

<span class="product-card__rating" aria-label="{{ $rating }} out of 5 stars{{ $count ? ', '.$count.' reviews' : '' }}">
    <span class="product-card__stars" aria-hidden="true">
        @for ($i = 1; $i <= 5; $i++)
            @if ($i <= $fullStars)
                <svg class="{{ $sizeClass }}" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            @elseif ($hasHalf && $i === $fullStars + 1)
                <svg class="{{ $sizeClass }}" viewBox="0 0 20 20" fill="currentColor"><defs><linearGradient id="half-{{ md5((string) $rating.$count) }}"><stop offset="50%" stop-color="currentColor"/><stop offset="50%" stop-color="rgb(209 213 219)"/></linearGradient></defs><path fill="url(#half-{{ md5((string) $rating.$count) }})" d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            @else
                <svg class="{{ $sizeClass }} text-ink-200" viewBox="0 0 20 20" fill="currentColor"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            @endif
        @endfor
    </span>
    @if ($count > 0)
        <span class="text-xs text-ink-500">({{ $count }})</span>
    @endif
</span>
