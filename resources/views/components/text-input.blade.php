@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'h-11 rounded-lg border-black/15 bg-white/80 px-3.5 text-[15px] text-ink placeholder:text-mute shadow-[inset_0_1px_2px_rgba(0,0,0,0.04)] transition focus:border-brand-dark focus:bg-white focus:ring-2 focus:ring-brand/30']) }}>
