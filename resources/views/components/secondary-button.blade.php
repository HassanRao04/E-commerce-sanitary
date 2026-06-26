<button {{ $attributes->merge(['type' => 'button', 'class' => 'ds-btn-secondary disabled:opacity-25']) }}>
    {{ $slot }}
</button>
