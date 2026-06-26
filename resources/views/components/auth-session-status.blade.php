@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'ds-alert-success', 'role' => 'status']) }}>
        <p>{{ $status }}</p>
    </div>
@endif
