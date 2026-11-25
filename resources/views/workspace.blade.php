{{-- Agentic AI workspace: conversations | chat | code panel. (The project scan has its own page, /scan.)
     Each panel is its own Livewire component; they only talk through events (see README "Alur Livewire").
     Layout state (open drawers, active tab) lives in Alpine.store('ui'), see resources/js/app.js. --}}
<x-app-layout>
    <x-slot name="title">Workspace</x-slot>

    <div class="relative flex h-[calc(100dvh-3.5rem)] min-h-0 overflow-hidden bg-white"
         x-data x-on:keydown.escape.window="$store.ui.closeDrawers()">

        {{-- Dim the page behind an open drawer (below lg for the sidebar, below xl for the code panel) --}}
        <div x-cloak x-show="$store.ui.sidebar || ($store.ui.panel && ! window.matchMedia('(min-width: 1280px)').matches)"
             x-transition.opacity x-on:click="$store.ui.closeDrawers()"
             class="fixed inset-0 z-30 bg-black/40 xl:hidden" aria-hidden="true"></div>

        {{-- Conversations: drawer below lg, fixed column from lg --}}
        <aside class="fixed inset-y-0 left-0 z-40 w-[18rem] max-w-[85vw] bg-gradient-to-b from-[#fafaf8] via-[#f6f7f3] to-[#eef1ea] border-r border-black/5 transition-transform duration-200
                      lg:static lg:z-auto lg:w-[17rem] lg:max-w-none lg:translate-x-0 shrink-0"
               :class="$store.ui.sidebar ? 'translate-x-0 shadow-xl' : '-translate-x-full lg:translate-x-0'"
               aria-label="Riwayat percakapan">
            <livewire:conversation-sidebar :conversation-id="$conversationId" />
        </aside>

        {{-- Chat --}}
        <section class="flex-1 min-w-0 flex flex-col" aria-label="Percakapan">
            <livewire:chat-box :conversation-id="$conversationId" />
        </section>

        {{-- Code panel: drawer below xl, closable column from xl --}}
        <aside x-cloak x-show="$store.ui.panel"
               class="fixed inset-y-0 right-0 z-40 w-[30rem] max-w-[92vw] flex flex-col bg-gradient-to-b from-white via-white to-[#f6f8f3] border-l border-black/5 shadow-xl
                      xl:static xl:z-auto xl:max-w-none xl:shadow-none xl:w-[27rem] 2xl:w-[32rem] shrink-0"
               aria-label="Panel kode">
            <div class="h-14 shrink-0 flex items-center gap-2 px-4 border-b border-black/5">
                <span class="grid h-7 w-7 place-items-center rounded-lg bg-gradient-to-br from-lime-100 to-teal-100 text-success ring-1 ring-black/5" aria-hidden="true">
                    <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6.28 5.22a.75.75 0 0 1 0 1.06L2.56 10l3.72 3.72a.75.75 0 0 1-1.06 1.06L.97 10.53a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06 0Zm7.44 0a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L17.44 10l-3.72-3.72a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg>
                </span>
                <h2 class="text-[15px] font-semibold text-ink">Panel kode</h2>
                <button type="button" x-on:click="$store.ui.togglePanel(false)"
                        class="ml-auto grid h-9 w-9 place-items-center rounded-lg text-mute hover:bg-soft hover:text-ink" aria-label="Tutup panel kode">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
                </button>
            </div>

            <div class="flex-1 min-h-0 flex flex-col">
                <livewire:code-panel :conversation-id="$conversationId" />
            </div>
        </aside>
    </div>
</x-app-layout>
