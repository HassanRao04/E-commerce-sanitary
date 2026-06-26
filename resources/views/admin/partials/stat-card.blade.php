@props([
    'label',
    'value',
    'hint' => null,
    'tone' => 'slate',
    'icon' => null,
])

@php
    $tones = [
        'slate' => 'bg-slate-50 text-slate-700 ring-slate-200',
        'amber' => 'bg-amber-50 text-amber-700 ring-amber-200',
        'blue' => 'bg-blue-50 text-blue-700 ring-blue-200',
        'emerald' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
        'violet' => 'bg-violet-50 text-violet-700 ring-violet-200',
    ];
    $toneClass = $tones[$tone] ?? $tones['slate'];
@endphp

<div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 p-5">
    <div class="flex items-start justify-between gap-3">
        <div>
            <p class="text-sm font-medium text-gray-500">{{ $label }}</p>
            <p class="text-2xl font-bold text-gray-900 mt-1 tracking-tight">{{ $value }}</p>
            @if ($hint)
                <p class="text-xs text-gray-500 mt-2">{{ $hint }}</p>
            @endif
        </div>
        @if ($icon)
            <div class="rounded-lg p-2.5 ring-1 {{ $toneClass }}">
                @include('admin.partials.icon', ['name' => $icon, 'class' => 'w-5 h-5'])
            </div>
        @endif
    </div>
</div>
