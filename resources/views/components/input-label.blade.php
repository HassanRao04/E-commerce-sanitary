@props(['value'])

<label {{ $attributes->merge(['class' => 'ds-label block']) }}>
    {{ $value ?? $slot }}
</label>
