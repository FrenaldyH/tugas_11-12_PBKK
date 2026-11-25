<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center h-11 px-6 bg-brand rounded font-bold text-sm text-black hover:bg-brand-dark active:bg-brand-dark disabled:bg-soft disabled:text-mute transition']) }}>
    {{ $slot }}
</button>
