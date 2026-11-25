{{-- Scan Proyek: a page of its own, because it checks this app's source code, not a conversation --}}
<x-app-layout>
    <x-slot name="title">Scan Proyek</x-slot>

    <div class="relative flex-1 bg-aurora">
        <div class="dot-grid pointer-events-none absolute inset-x-0 top-0 h-[24rem]" aria-hidden="true"></div>

        <div class="relative mx-auto w-full max-w-6xl px-4 sm:px-6 py-8 sm:py-10">
            <p class="inline-flex items-center gap-2 rounded-full bg-white/80 px-3 py-1 text-xs font-medium text-body ring-1 ring-black/10">
                <span class="brand-dot h-1.5 w-1.5 rounded-full" aria-hidden="true"></span> Challenge 3 · wire:poll + queue
            </p>
            <h1 class="mt-4 text-3xl font-semibold tracking-tight text-ink">Scan <span class="text-gradient">kode proyek</span></h1>
            <p class="mt-2 max-w-2xl text-[15px] text-mute">
                Memeriksa kode sumber aplikasi PromptLab ini di latar belakang: membuat sidik jari setiap file dan mencari
                kredensial yang ditulis langsung di kode. Tidak berhubungan dengan percakapan di Workspace.
            </p>

            <div class="mt-8">
                <livewire:scan-monitor />
            </div>
        </div>
    </div>
</x-app-layout>
