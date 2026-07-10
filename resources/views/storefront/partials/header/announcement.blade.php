@php
    use App\Support\StorefrontHeader;

    $header = StorefrontHeader::resolved();
    $announcement = $header['announcement'] ?? [];
    $showTopBar = StorefrontHeader::showTopBar();
    $showSocial = StorefrontHeader::showSocialInTopBar();
    $showAnnouncement = ($announcement['enabled'] ?? true) && filled($announcement['text'] ?? null);
@endphp

@if ($showTopBar)
    <div @class(['storefront-announcement', 'storefront-announcement--with-social' => $showSocial])>
        <div class="storefront-announcement__inner ds-container">
            @if ($showAnnouncement)
                <div class="storefront-announcement__message">
                    @if (filled($announcement['link_url'] ?? null))
                        <a href="{{ $announcement['link_url'] }}" class="storefront-announcement__link">
                            {{ $announcement['text'] }}
                            @if (filled($announcement['link_label'] ?? null))
                                <span class="storefront-announcement__cta">{{ $announcement['link_label'] }}</span>
                            @endif
                        </a>
                    @else
                        {{ $announcement['text'] }}
                    @endif
                </div>
            @endif

            @if ($showSocial)
                <x-storefront.social-links variant="top-bar" />
            @endif
        </div>
    </div>
@endif
