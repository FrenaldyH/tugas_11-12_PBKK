<div class="h-full flex flex-col">
    <div class="p-3 space-y-2.5">
        <div class="flex items-center gap-2">
            <button type="button" wire:click="startNew" x-on:click="$store.ui.sidebar = false"
                    class="flex-1 h-10 inline-flex items-center justify-center gap-2 rounded-lg bg-black text-white text-sm font-medium hover:bg-body transition">
                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" /></svg>
                Percakapan baru
            </button>
            <button type="button" x-on:click="$store.ui.sidebar = false"
                    class="lg:hidden grid h-10 w-10 place-items-center rounded-lg text-mute hover:bg-black/5 hover:text-ink" aria-label="Tutup riwayat">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" /></svg>
            </button>
        </div>

        <div class="relative">
            <label for="search-conversation" class="sr-only">Cari percakapan</label>
            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-mute" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" /></svg>
            <input id="search-conversation" type="search" wire:model.live.debounce.300ms="search" placeholder="Cari percakapan"
                   class="w-full h-9 pl-9 pr-3 text-sm rounded-lg bg-white border-black/10 placeholder:text-mute focus:border-brand-dark focus:ring-brand-dark">
        </div>
    </div>

    <nav class="flex-1 min-h-0 overflow-y-auto scroll-thin px-2 pb-4" aria-label="Daftar percakapan">
        @forelse ($this->groups as $label => $conversations)
            <p class="px-3 pt-4 pb-1.5 text-xs font-medium text-mute">{{ $label }}</p>
            <ul class="space-y-0.5">
                @foreach ($conversations as $conversation)
                    @php $active = $conversation->id === $activeId; @endphp
                    <li wire:key="conversation-{{ $conversation->id }}" class="group relative">
                        <button type="button" wire:click="select({{ $conversation->id }})" x-on:click="$store.ui.sidebar = false"
                                @class([
                                    'w-full text-left rounded-lg pl-3 pr-10 py-2 transition',
                                    'bg-white shadow-sm ring-1 ring-black/5' => $active,
                                    'hover:bg-black/[0.04]' => ! $active,
                                ])
                                @if ($active) aria-current="true" @endif>
                            <span @class(['block text-sm truncate', 'font-semibold text-ink' => $active, 'text-body' => ! $active])>{{ $conversation->title }}</span>
                            <span class="mt-0.5 flex items-center gap-1.5 text-xs text-mute">
                                @if ($active)<span class="h-1.5 w-1.5 rounded-full bg-brand" aria-hidden="true"></span>@endif
                                {{ $conversation->messages_count }} pesan · {{ $conversation->updated_at->locale('id')->diffForHumans() }}
                            </span>
                        </button>
                        <button type="button" wire:click="delete({{ $conversation->id }})"
                                wire:confirm="Hapus percakapan &quot;{{ $conversation->title }}&quot; beserta semua pesannya?"
                                class="absolute right-1.5 top-1/2 -translate-y-1/2 grid h-7 w-7 place-items-center rounded-md text-mute hover:text-danger hover:bg-black/5
                                       opacity-100 lg:opacity-0 lg:group-hover:opacity-100 focus:opacity-100 transition"
                                aria-label="Hapus percakapan {{ $conversation->title }}">
                            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd" /></svg>
                        </button>
                    </li>
                @endforeach
            </ul>
        @empty
            <p class="px-3 py-8 text-sm text-mute text-center">
                {{ trim($search) !== '' ? 'Tidak ada judul yang cocok.' : 'Belum ada percakapan.' }}
            </p>
        @endforelse
    </nav>
</div>
