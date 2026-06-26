@props(['status'])

@php
    $colors = match ($status instanceof \App\Enums\OrderStatus ? $status->value : (string) $status) {
        'pending' => 'bg-amber-100 text-amber-800',
        'confirmed' => 'bg-blue-100 text-blue-800',
        'processing' => 'bg-indigo-100 text-indigo-800',
        'packed' => 'bg-violet-100 text-violet-800',
        'shipped' => 'bg-cyan-100 text-cyan-800',
        'out_for_delivery' => 'bg-sky-100 text-sky-800',
        'delivered' => 'bg-emerald-100 text-emerald-800',
        'cancelled' => 'bg-red-100 text-red-800',
        'refunded' => 'bg-gray-200 text-gray-700',
        default => 'bg-gray-100 text-gray-700',
    };
    $label = $status instanceof \App\Enums\OrderStatus
        ? str($status->value)->headline()->replace('_', ' ')
        : str((string) $status)->headline()->replace('_', ' ');
@endphp

<span {{ $attributes->merge(['class' => "inline-flex px-2 py-1 rounded-full text-xs font-medium capitalize {$colors}"]) }}>
    {{ $label }}
</span>
