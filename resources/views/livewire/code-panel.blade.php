{{-- Challenge 2: split-panel code visualizer. Tabs are Alpine-only (no request when switching) --}}
<div class="flex-1 min-h-[22rem] xl:min-h-0 flex flex-col text-white" x-data="{ tab: 0, copied: false }">
    <div class="shrink-0 h-16 px-4 flex items-center justify-between gap-3 border-b border-white/15">
        <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-wider text-white/70">Panel kode</p>
            <p class="text-sm font-bold truncate">
                @if ($this->message)
                    Dari jawaban {{ $this->message->created_at->format('H:i') }} · {{ count($this->blocks) }} blok
                @else
                    Belum ada kode
                @endif
            </p>
        </div>
        @if ($this->blocks !== [])
            <button type="button"
                    x-on:click="navigator.clipboard.writeText($refs['code-' + tab].innerText); copied = true; setTimeout(() => copied = false, 1500)"
                    class="shrink-0 h-9 px-3 rounded border border-white/40 text-xs font-bold hover:bg-white/10">
                <span x-show="! copied">Salin</span><span x-show="copied" x-cloak>Tersalin</span>
            </button>
        @endif
    </div>

    @if ($this->blocks === [])
        <div class="flex-1 flex items-center justify-center p-6">
            <p class="max-w-xs text-sm text-white/70 font-mono leading-relaxed">
                Blok kode (<span class="text-white">```php … ```</span>) dari jawaban AI akan tampil di sini dengan pewarnaan sintaks.
            </p>
        </div>
    @else
        {{-- wire:key changes with the message, so a new answer re-creates (and re-highlights) the blocks --}}
        <div wire:key="code-{{ $messageId }}" class="flex-1 min-h-0 flex flex-col" x-init="tab = 0">
            <div class="shrink-0 flex overflow-x-auto border-b border-white/15" role="tablist" aria-label="Blok kode">
                @foreach ($this->blocks as $i => $block)
                    <button type="button" role="tab" x-on:click="tab = {{ $i }}"
                            :aria-selected="(tab === {{ $i }}).toString()"
                            :class="tab === {{ $i }} ? 'border-brand text-white bg-white/5' : 'border-transparent text-white/70 hover:text-white'"
                            class="shrink-0 px-4 h-10 border-b-4 font-mono text-xs">
                        {{ $block['name'] }}
                    </button>
                @endforeach
            </div>

            <div wire:ignore x-highlight class="flex-1 min-h-0 overflow-auto">
                @foreach ($this->blocks as $i => $block)
                    <pre x-show="tab === {{ $i }}" @if ($i > 0) x-cloak @endif role="tabpanel"
                         class="p-4 text-[13px] leading-relaxed"><code x-ref="code-{{ $i }}" class="language-{{ $block['lang'] }}">{{ $block['code'] }}</code></pre>
                @endforeach
            </div>
        </div>
    @endif
</div>
