@props(['items' => []])

@if (! empty($items))
    <nav aria-label="Breadcrumb" class="mb-6">
        <ol class="flex flex-wrap items-center gap-2 ds-body-sm text-ink-400">
            <li><a href="{{ route('shop.home') }}" class="hover:text-ink ds-hover-underline">Home</a></li>
            @foreach ($items as $item)
                <li class="flex items-center gap-2">
                    <span aria-hidden="true" class="text-ink-300">/</span>
                    @if (! empty($item['url']))
                        <a href="{{ $item['url'] }}" class="hover:text-ink ds-hover-underline">{{ $item['label'] }}</a>
                    @else
                        <span class="text-ink font-medium">{{ $item['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
