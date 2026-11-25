@props(['active'])

@php
// On the black nav: active link = white text with a green bar underneath
$classes = ($active ?? false)
            ? 'inline-flex items-center border-b-4 border-brand text-sm font-bold text-white'
            : 'inline-flex items-center border-b-4 border-transparent text-sm font-bold text-white/70 hover:text-white hover:border-white/40 transition';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }} @if($active ?? false) aria-current="page" @endif>
    {{ $slot }}
</a>
