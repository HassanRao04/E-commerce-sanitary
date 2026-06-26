@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'ds-input block w-full']) }}>
