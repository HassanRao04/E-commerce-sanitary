@props(['messages'])

@if ($messages)
    <ul {{ $attributes->merge(['class' => 'ds-error-text space-y-1']) }}>
        @foreach ((array) $messages as $message)
            <li>{{ $message }}</li>
        @endforeach
    </ul>
@endif
