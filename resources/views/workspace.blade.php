{{-- Agentic AI workspace: conversations | chat | inspector (Kode / Scan).
     Each panel is its own Livewire component; they only talk through events (see README "Alur Livewire").
     Layout state (open drawers, active tab) lives in Alpine.store('ui'), see resources/js/app.js. --}}
<x-app-layout>
    <x-slot name="title">Workspace</x-slot>

    <div class="relative flex h-[calc(100dvh-3.5rem)] min-h-0 overflow-hidden bg-white"
         x-data x-on:keydown.escape.window="$store.ui.closeDrawers()">

        {{-- Dim the page behind an open drawer (below lg for the sidebar, below xl for the inspector) --}}
        <div x-cloak x-show="$store.ui.sidebar || ($store.ui.panel && ! window.matchMedia('(min-width: 1280px)').matches)"
             x-transition.opacity x-on:click="$store.ui.closeDrawers()"
             class="fixed inset-0 z-30 bg-black/40 xl:hidden" aria-hidden="true"></div>

        {{-- Conversations: drawer below lg, fixed column from lg --}}
        <aside class="fixed inset-y-0 left-0 z-40 w-[18rem] max-w-[85vw] bg-soft border-r border-black/5 transition-transform duration-200
                      lg:static lg:z-auto lg:w-[17rem] lg:max-w-none lg:translate-x-0 shrink-0"
               :class="$store.ui.sidebar ? 'translate-x-0 shadow-xl' : '-translate-x-full lg:translate-x-0'"
               aria-label="Riwayat percakapan">
            <livewire:conversation-sidebar :conversation-id="$conversationId" />
        </aside>

        {{-- Chat --}}
        <section class="flex-1 min-w-0 flex flex-col" aria-label="Percakapan">
            <livewire:chat-box :conversation-id="$conversationId" />
        </section>

        {{-- Inspector: drawer below xl, closable column from xl --}}
        <aside x-cloak x-show="$store.ui.panel"
               class="fixed inset-y-0 right-0 z-40 w-[30rem] max-w-[92vw] flex flex-col bg-white border-l border-black/5 shadow-xl
                      xl:static xl:z-auto xl:max-w-none xl:shadow-none xl:w-[27rem] 2xl:w-[32rem] shrink-0"
               aria-label="Inspector">
            <div class="h-14 shrink-0 flex items-center gap-1 px-3 border-b border-black/5">
                <div class="flex items-center gap-1 rounded-lg bg-soft p-1" role="tablist">
                    @foreach (['code' => 'Kode', 'scan' => 'Scan workspace'] as $tab => $label)
                        <button type="button" role="tab" x-on:click="$store.ui.tab = '{{ $tab }}'"
                                :aria-selected="($store.ui.tab === '{{ $tab }}').toString()"
                                :class="$store.ui.tab === '{{ $tab }}' ? 'bg-white text-ink shadow-sm' : 'text-mute hover:text-ink'"
                                class="h-8 px-3 rounded-md text-sm font-medium transition">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
                <button type="button" x-on:click="$store.ui.togglePanel(false)"
                        class="ml-auto grid h-9 w-9 place-items-center rounded-lg text-mute hover:bg-soft hover:text-ink" aria-label="Tutup panel">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
                </button>
            </div>

            <div x-show="$store.ui.tab === 'code'" class="flex-1 min-h-0 flex flex-col" role="tabpanel">
                <livewire:code-panel :conversation-id="$conversationId" />
            </div>
            <div x-show="$store.ui.tab === 'scan'" x-cloak class="flex-1 min-h-0 flex flex-col" role="tabpanel">
                <livewire:scan-monitor />
            </div>
        </aside>
    </div>
</x-app-layout>
