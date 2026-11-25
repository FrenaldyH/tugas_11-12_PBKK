<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-brand inline-flex items-center justify-center gap-2 h-11 px-6 rounded-xl font-semibold text-sm disabled:cursor-not-allowed']) }}>
    {{ $slot }}
</button>
