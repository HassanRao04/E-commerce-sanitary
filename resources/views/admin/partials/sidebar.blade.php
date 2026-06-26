@php
    $currency = config('shop.currency_symbol');
@endphp

<nav class="flex-1 overflow-y-auto px-3 py-4 space-y-6">
    @foreach (config('admin.menu') as $group)
        @php
            $visibleItems = collect($group['items'] ?? [])->filter(
                fn (array $item): bool => auth()->user()?->can($item['permission'] ?? '') ?? false
            );
        @endphp

        @if ($visibleItems->isEmpty())
            @continue
        @endif

        @if (! empty($group['section']))
            <p class="px-3 text-[11px] font-semibold uppercase tracking-wider text-slate-500">
                {{ $group['section'] }}
            </p>
        @endif

        <div class="space-y-0.5">
            @foreach ($visibleItems as $item)
                @php
                    $active = request()->routeIs($item['active'] ?? $item['route']);
                @endphp
                <a href="{{ route($item['route']) }}"
                   class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition
                          {{ $active ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-300 hover:bg-slate-800/70 hover:text-white' }}">
                    @include('admin.partials.icon', ['name' => $item['icon'], 'class' => 'w-5 h-5 shrink-0'])
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>
    @endforeach
</nav>
