@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-hairline-strong focus:border-brand-dark focus:ring-brand-dark rounded-sm']) }}>
