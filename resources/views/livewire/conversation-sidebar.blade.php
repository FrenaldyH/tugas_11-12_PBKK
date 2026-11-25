<div x-data="{ open: false }" class="h-full flex flex-col">
    <div class="p-4 flex items-center gap-2 border-b border-hairline">
        <button type="button" wire:click="startNew"
                class="flex-1 h-11 inline-flex items-center justify-center gap-2 border-2 border-brand rounded text-sm font-bold text-ink hover:bg-white transition">
            <span aria-hidden="true">+</span> Percakapan baru
        </button>
        {{-- Below lg the list is folded away so the chat stays on screen --}}
        <button type="button" @click="open = !open" class="lg:hidden h-11 px-3 border border-hairline-strong rounded text-sm font-bold"
                :aria-expanded="open.toString()" aria-controls="conversation-list">
            Riwayat ({{ $this->conversations->count() }})
        </button>
    </div>

    <div id="conversation-list" class="hidden lg:flex flex-col flex-1 min-h-0" :class="{ '!flex': open }">
        <div class="px-4 pt-3">
            <label for="search-conversation" class="sr-only">Cari percakapan</label>
            <input id="search-conversation" type="search" wire:model.live.debounce.300ms="search" placeholder="Cari judul percakapan…"
                   class="w-full h-10 text-sm border-hairline-strong rounded-sm focus:border-brand-dark focus:ring-brand-dark">
        </div>

        <p class="px-4 pt-4 pb-2 text-xs font-bold uppercase tracking-wider text-mute">Riwayat</p>

        <ul class="flex-1 overflow-y-auto px-2 pb-4 space-y-1 max-h-72 lg:max-h-none">
            @forelse ($this->conversations as $conversation)
                @php $active = $conversation->id === $activeId; @endphp
                <li wire:key="conversation-{{ $conversation->id }}" class="group relative">
                    <button type="button" wire:click="select({{ $conversation->id }})" @click="open = false"
                            @class([
                                'w-full text-left rounded-sm pl-3 pr-9 py-2 border-l-4 transition',
                                'bg-white border-brand' => $active,
                                'border-transparent hover:bg-white' => ! $active,
                            ])
                            @if ($active) aria-current="true" @endif>
                        <span class="block text-sm font-bold text-ink truncate">{{ $conversation->title }}</span>
                        <span class="block text-xs text-mute">
                            {{ $conversation->messages_count }} pesan · {{ $conversation->updated_at->locale('id')->diffForHumans() }}
                        </span>
                    </button>
                    <button type="button" wire:click="delete({{ $conversation->id }})"
                            wire:confirm="Hapus percakapan &quot;{{ $conversation->title }}&quot; beserta semua pesannya?"
                            class="absolute right-1 top-1/2 -translate-y-1/2 h-8 w-8 rounded text-mute hover:text-danger hover:bg-soft lg:opacity-0 lg:group-hover:opacity-100 focus:opacity-100"
                            aria-label="Hapus percakapan {{ $conversation->title }}">
                        <svg class="h-4 w-4 mx-auto" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 0 0 6 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 1 0 .23 1.482l.149-.022.841 10.518A2.75 2.75 0 0 0 7.596 19h4.807a2.75 2.75 0 0 0 2.742-2.53l.841-10.52.149.023a.75.75 0 0 0 .23-1.482A41.03 41.03 0 0 0 14 4.193V3.75A2.75 2.75 0 0 0 11.25 1h-2.5ZM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4ZM8.58 7.72a.75.75 0 0 0-1.5.06l.3 7.5a.75.75 0 1 0 1.5-.06l-.3-7.5Zm4.34.06a.75.75 0 1 0-1.5-.06l-.3 7.5a.75.75 0 1 0 1.5.06l.3-7.5Z" clip-rule="evenodd" /></svg>
                    </button>
                </li>
            @empty
                <li class="px-3 py-6 text-sm text-mute">
                    {{ trim($search) !== '' ? 'Tidak ada judul yang cocok.' : 'Belum ada percakapan. Tulis prompt pertama Anda.' }}
                </li>
            @endforelse
        </ul>
    </div>
</div>
