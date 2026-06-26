<button {{ $attributes->merge(['type' => 'submit', 'class' => 'ds-btn-danger']) }}>
    {{ $slot }}
</button>
