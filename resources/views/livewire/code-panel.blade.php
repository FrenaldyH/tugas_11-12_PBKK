{{-- Challenge 2: split-panel code visualizer. Switching files is Alpine-only (no request) --}}
<div class="flex-1 min-h-0 flex flex-col" x-data="{ tab: 0, copied: false }">
    @if ($this->blocks === [])
        <div class="flex-1 grid place-items-center p-8">
            <div class="max-w-xs text-center">
                <span class="mx-auto grid h-11 w-11 place-items-center rounded-xl bg-gradient-to-br from-lime-100 to-teal-100 text-success ring-1 ring-black/5" aria-hidden="true">
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M6.28 5.22a.75.75 0 0 1 0 1.06L2.56 10l3.72 3.72a.75.75 0 0 1-1.06 1.06L.97 10.53a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06 0Zm7.44 0a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L17.44 10l-3.72-3.72a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg>
                </span>
                <p class="mt-4 text-sm font-semibold text-ink">Belum ada kode</p>
                <p class="mt-1 text-sm text-mute">Blok kode dari jawaban AI akan tampil di sini, lengkap dengan pewarnaan sintaks.</p>
            </div>
        </div>
    @else
        {{-- wire:key changes with the message, so a new answer re-creates (and re-highlights) the blocks --}}
        <div wire:key="code-{{ $messageId }}" class="flex-1 min-h-0 flex flex-col p-4 gap-3" x-init="tab = 0">
            <div class="flex items-center justify-between gap-3">
                <p class="text-sm text-mute">
                    Dari jawaban pukul <span class="font-medium text-ink">{{ $this->message->created_at->format('H:i') }}</span>
                </p>
                <span class="text-xs text-mute">{{ count($this->blocks) }} blok</span>
            </div>

            @if (count($this->blocks) > 1)
                <div class="flex flex-wrap gap-1.5" role="tablist" aria-label="Blok kode">
                    @foreach ($this->blocks as $i => $block)
                        <button type="button" role="tab" x-on:click="tab = {{ $i }}"
                                :aria-selected="(tab === {{ $i }}).toString()"
                                :class="tab === {{ $i }} ? 'btn-ink border-transparent' : 'bg-white text-body border-black/10 hover:border-black/25'"
                                class="h-8 px-3 rounded-lg border font-mono text-xs transition">
                            {{ $block['name'] }}
                        </button>
                    @endforeach
                </div>
            @endif

            <div class="flex-1 min-h-0 flex flex-col rounded-xl bg-gradient-to-b from-[#1b1b1b] to-[#0e0e0e] overflow-hidden shadow-[0_18px_40px_-24px_rgba(0,0,0,0.9)] ring-1 ring-black/10">
                <div class="h-10 shrink-0 flex items-center justify-between pl-3 pr-1.5 bg-white/[0.04] border-b border-white/[0.06]">
                    <span class="mr-3 flex gap-1.5" aria-hidden="true"><span class="h-2.5 w-2.5 rounded-full bg-[#ff5f57]"></span><span class="h-2.5 w-2.5 rounded-full bg-[#febc2e]"></span><span class="h-2.5 w-2.5 rounded-full bg-[#28c840]"></span></span>
                    @foreach ($this->blocks as $i => $block)
                        <span x-show="tab === {{ $i }}" @if ($i > 0) x-cloak @endif class="mr-auto font-mono text-xs text-white/70">{{ $block['name'] }}</span>
                    @endforeach
                    <button type="button"
                            x-on:click="navigator.clipboard?.writeText($refs['code-' + tab].innerText); copied = true; setTimeout(() => copied = false, 1500)"
                            class="h-7 px-2.5 rounded-md text-xs font-semibold text-white hover:bg-white/10">
                        <span x-show="! copied">Salin</span><span x-show="copied" x-cloak>Tersalin</span>
                    </button>
                </div>
                <div wire:ignore x-highlight.bare class="flex-1 min-h-0 overflow-auto scroll-thin-dark">
                    @foreach ($this->blocks as $i => $block)
                        <pre x-show="tab === {{ $i }}" @if ($i > 0) x-cloak @endif role="tabpanel"
                             class="p-4 text-[13px] leading-relaxed text-white"><code x-ref="code-{{ $i }}" class="language-{{ $block['lang'] }}">{{ $block['code'] }}</code></pre>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>
