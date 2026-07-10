@props(['status'])

@php
    $workflow = app(\App\Services\OrderWorkflowService::class);
    $slug = $status instanceof \App\Models\OrderStatus ? $status->slug : (string) $status;
    $definition = $status instanceof \App\Models\OrderStatus ? $status : $workflow->find($slug);
    $label = $definition?->name ?? $workflow->label($slug);
    $colors = $workflow->badgeClasses($slug);
@endphp

<span {{ $attributes->merge(['class' => "inline-flex px-2 py-1 rounded-full text-xs font-medium capitalize {$colors}"]) }}>
    {{ $label }}
</span>
