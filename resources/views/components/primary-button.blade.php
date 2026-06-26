<button {{ $attributes->merge(['type' => 'submit', 'class' => 'ds-btn-primary']) }}>
    {{ $slot }}
</button>
