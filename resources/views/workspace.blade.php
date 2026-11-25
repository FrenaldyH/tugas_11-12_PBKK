{{-- Agentic AI workspace: conversations | chat | code + scan. Each panel is its own Livewire component;
     they only talk through events (see README "Alur Livewire"). --}}
<x-app-layout>
    <x-slot name="title">Workspace</x-slot>

    {{-- lg: sidebar | chat fill one screen-high row, code + scan go below. xl: all three side by side.
         The fixed row height lets each panel scroll on its own instead of stretching the page. --}}
    <div class="flex-1 flex flex-col lg:grid lg:grid-cols-[16rem_minmax(0,1fr)] lg:grid-rows-[calc(100dvh-4rem)_auto] xl:grid-cols-[16rem_minmax(0,1fr)_minmax(22rem,30rem)] xl:grid-rows-[calc(100dvh-4rem)]">
        <aside class="bg-soft border-b lg:border-b-0 lg:border-r border-hairline lg:min-h-0 lg:overflow-hidden">
            <livewire:conversation-sidebar :conversation-id="$conversationId" />
        </aside>

        <section class="flex flex-col min-h-[75dvh] lg:min-h-0 lg:overflow-hidden" aria-label="Percakapan">
            <livewire:chat-box :conversation-id="$conversationId" />
        </section>

        <aside class="flex flex-col border-t xl:border-t-0 xl:border-l border-hairline bg-elevated lg:col-span-2 xl:col-span-1 xl:min-h-0 xl:overflow-hidden">
            <livewire:code-panel :conversation-id="$conversationId" />
            <livewire:scan-monitor />
        </aside>
    </div>
</x-app-layout>
