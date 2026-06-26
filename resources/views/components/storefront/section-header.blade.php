@props([
    'title',
    'subtitle' => null,
    'eyebrow' => null,
    'badge' => null,
    'badgeClass' => 'ds-badge-accent',
    'align' => 'left',
    'size' => 'default',
])

<div {{ $attributes->class([
    'ds-section-header',
    'ds-section-header--center' => $align === 'center',
    'ds-section-header--compact' => $size === 'compact',
]) }}>
    @if ($badge)
        <span class="{{ $badgeClass }}">{{ $badge }}</span>
    @elseif ($eyebrow)
        <p class="ds-section-eyebrow">{{ $eyebrow }}</p>
    @endif

    <h2 class="ds-section-header__title">{{ $title }}</h2>

    @if ($subtitle)
        <p class="ds-section-header__subtitle">{{ $subtitle }}</p>
    @endif

    {{ $slot }}
</div>
