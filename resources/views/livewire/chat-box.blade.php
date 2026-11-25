@php use App\Support\Markdown; @endphp

<div class="relative flex-1 min-h-0 flex flex-col bg-aurora">
    <div class="dot-grid pointer-events-none absolute inset-x-0 top-0 h-[28rem]" aria-hidden="true"></div>

    {{-- Header --}}
    <header class="glass relative z-10 h-14 shrink-0 flex items-center gap-2 px-3 sm:px-5 border-b border-black/5">
        <button type="button" x-on:click="$store.ui.sidebar = true"
                class="lg:hidden grid h-9 w-9 place-items-center rounded-lg text-body hover:bg-soft" aria-label="Buka riwayat percakapan">
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M2 4.75A.75.75 0 0 1 2.75 4h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 4.75ZM2 10a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H2.75A.75.75 0 0 1 2 10Zm0 5.25a.75.75 0 0 1 .75-.75h14.5a.75.75 0 0 1 0 1.5H2.75a.75.75 0 0 1-.75-.75Z" clip-rule="evenodd" /></svg>
        </button>

        <div class="min-w-0 flex-1">
            <h1 class="text-[15px] font-semibold text-ink truncate">{{ $this->conversation?->title ?? 'Percakapan baru' }}</h1>
            <p class="text-xs text-mute truncate">
                Agent {{ $themes[$theme] ?? $theme }}@if ($this->bubbles->isNotEmpty()) · {{ $this->bubbles->count() }} pesan @endif
            </p>
        </div>

        <button type="button" x-on:click="$store.ui.togglePanel()"
                :class="$store.ui.panel ? 'bg-soft text-ink' : 'text-body hover:bg-soft'"
                class="h-9 px-3 inline-flex items-center gap-2 rounded-lg text-sm font-medium transition"
                :aria-pressed="$store.ui.panel.toString()">
            <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M2 4.25A2.25 2.25 0 0 1 4.25 2h11.5A2.25 2.25 0 0 1 18 4.25v11.5A2.25 2.25 0 0 1 15.75 18H4.25A2.25 2.25 0 0 1 2 15.75V4.25ZM12.5 3.5v13h3.25a.75.75 0 0 0 .75-.75V4.25a.75.75 0 0 0-.75-.75H12.5Z" clip-rule="evenodd" /></svg>
            <span class="hidden sm:inline">Panel kode</span>
        </button>
    </header>

    {{-- Messages. role="log" + aria-live lets screen readers announce new answers --}}
    <div x-data x-autoscroll class="relative flex-1 min-h-0 overflow-y-auto scroll-thin" role="log" aria-live="polite">
        <div class="mx-auto w-full max-w-3xl px-4 sm:px-6 py-8">
            @if ($this->bubbles->isEmpty() && ! $waiting)
                <div class="pt-[8vh] text-center">
                    <span class="halo isolate mx-auto grid h-14 w-14 place-items-center rounded-2xl brand-tile" aria-hidden="true">
                        <span class="brand-dot h-5 w-5 rounded-[4px]"></span>
                    </span>
                    <h2 class="mt-6 text-3xl font-semibold tracking-tight text-ink">Mulai dari <span class="text-gradient">satu instruksi.</span></h2>
                    <p class="mt-2 text-[15px] text-mute max-w-md mx-auto">
                        Jawaban mengalir kata demi kata, kode tampil berwarna di panel Kode, dan semua pesan tersimpan di akun Anda.
                    </p>
                    <div class="mt-8 grid gap-3 sm:grid-cols-2 text-left">
                        @foreach ($suggestions as $suggestion)
                            <button type="button" wire:click="usePrompt(@js($suggestion['prompt']))"
                                    class="gradient-border lift group rounded-xl px-4 py-3.5">
                                <span class="flex items-center gap-2 text-sm font-semibold text-ink">
                                    <span class="h-1.5 w-1.5 rounded-full bg-gradient-to-br from-lime-400 to-teal-500" aria-hidden="true"></span>{{ $suggestion['title'] }}
                                </span>
                                <span class="mt-0.5 block text-sm text-mute line-clamp-2">{{ $suggestion['prompt'] }}</span>
                            </button>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="space-y-8">
                @foreach ($this->bubbles as $message)
                    @if ($message->role === 'user')
                        <div wire:key="msg-{{ $message->id }}" class="msg-in flex justify-end">
                            <div class="bubble-user max-w-[85%] sm:max-w-[75%] rounded-2xl rounded-br-md px-4 py-2.5 text-[15px] leading-relaxed whitespace-pre-wrap break-words">{{ $message->content }}</div>
                        </div>
                    @else
                        @php $failed = $message->status === 'failed'; @endphp
                        <div wire:key="msg-{{ $message->id }}" class="msg-in flex gap-3.5">
                            <x-ai-avatar :failed="$failed" />
                            <div class="min-w-0 flex-1">
                                @if ($failed)
                                    <div class="rounded-xl border border-danger/25 bg-danger/[0.04] px-4 py-3">
                                        <p class="text-sm font-semibold text-danger">Jawaban gagal</p>
                                        <div wire:ignore x-data x-highlight class="prose-chat mt-1 !text-sm">{!! Markdown::toHtml($message->content) !!}</div>
                                    </div>
                                @else
                                    {{-- wire:ignore: highlight.js colors this once; a re-render must not strip the colors --}}
                                    <div wire:ignore x-data x-highlight class="prose-chat">{!! Markdown::toHtml($message->content) !!}</div>
                                @endif

                                <div class="mt-2.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-mute">
                                    <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('H:i') }}</time>
                                    @if ($message->provider)
                                        <span>{{ ucfirst($message->provider) }} · {{ $message->model }}</span>
                                    @endif
                                    @if ($message->total_tokens)
                                        <span>{{ number_format($message->total_tokens, 0, ',', '.') }} token</span>
                                    @endif
                                    @if (Markdown::codeBlocks($message->content) !== [])
                                        <button type="button" wire:click="$dispatch('show-code', { id: {{ $message->id }} })"
                                                x-on:click="$store.ui.openPanel()"
                                                class="inline-flex items-center gap-1 rounded-md px-1.5 py-0.5 -mx-1.5 font-medium text-ink hover:bg-soft">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M6.28 5.22a.75.75 0 0 1 0 1.06L2.56 10l3.72 3.72a.75.75 0 0 1-1.06 1.06L.97 10.53a.75.75 0 0 1 0-1.06l4.25-4.25a.75.75 0 0 1 1.06 0Zm7.44 0a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L17.44 10l-3.72-3.72a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg>
                                            Buka di panel kode
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach

                {{-- Answer streaming in (Challenge 1). answer() fills the two wire:stream targets piece by piece --}}
                @if ($waiting)
                    <div wire:key="waiting-{{ $conversationId }}" class="msg-in flex gap-3.5">
                        <x-ai-avatar pulse />
                        <div class="min-w-0 flex-1">
                            <p x-data="{ s: 0, t: null, init() { this.t = setInterval(() => this.s++, 1000) }, destroy() { clearInterval(this.t) } }"
                               class="flex items-center gap-2 text-xs text-mute">
                                {{-- Emptied by answer() when the first word arrives, so it takes no space after that --}}
                                <span wire:stream="status" class="inline-flex items-center gap-2 pt-1.5 pb-2 empty:p-0">
                                    <span class="typing-dots" aria-hidden="true"><span></span><span></span><span></span></span>
                                    Menghubungi AI… <span x-text="s + ' dtk'"></span>
                                </span>
                            </p>
                            <p class="no-ligatures text-[15px] leading-[1.75] text-body whitespace-pre-wrap break-words"><span wire:stream="answer"></span><span class="caret" aria-hidden="true"></span></p>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Composer --}}
    <div class="relative shrink-0 px-3 sm:px-6 pb-4 pt-2">
        <form wire:submit="send" class="mx-auto w-full max-w-3xl">
            <div @class([
                'glass gradient-border rounded-2xl shadow-[0_10px_30px_-16px_rgba(0,0,0,0.35)]',
                'is-invalid' => $errors->has('prompt'),
            ])>
                <label for="prompt" class="sr-only">Prompt</label>
                <textarea id="prompt" rows="1" wire:model.live.debounce.400ms="prompt" x-data x-autogrow
                          x-on:keydown.enter="if (! $event.shiftKey && ! $event.isComposing) { $event.preventDefault(); $wire.send() }"
                          placeholder="Tulis instruksi untuk agent…"
                          aria-describedby="prompt-help"
                          class="block w-full resize-none border-0 bg-transparent px-4 pt-3.5 pb-1 text-[15px] leading-relaxed text-ink placeholder:text-mute focus:ring-0 scroll-thin"></textarea>

                <div class="flex items-center gap-2 px-2.5 pb-2.5 pt-1">
                    <label for="theme" class="sr-only">Agent</label>
                    <div class="relative">
                        <select id="theme" wire:model.live="theme" @disabled($waiting)
                                class="h-8 pl-3 pr-8 py-0 rounded-lg border-black/10 bg-white/70 text-xs font-medium text-body focus:border-brand-dark focus:ring-brand-dark disabled:opacity-60">
                            @foreach ($themes as $key => $label)
                                <option value="{{ $key }}">Agent: {{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <span @class([
                        'ml-auto text-xs tabular-nums',
                        'text-danger font-semibold' => $sisaKarakter < 0,
                        'text-mute' => $sisaKarakter >= 0,
                    ])>{{ number_format(1000 - $sisaKarakter) }}/1000</span>

                    {{-- Locked twice: wire:loading disables it during any request of send/answer, and $waiting keeps it
                         disabled server-side for the whole answer, so a double click can never send twice --}}
                    <button type="submit" wire:loading.attr="disabled" wire:target="send,answer" @disabled($waiting)
                            class="btn-brand h-9 inline-flex items-center gap-2 rounded-xl px-3.5 text-sm font-semibold disabled:cursor-not-allowed">
                        @if ($waiting)
                            <x-spinner /> <span>AI sedang menjawab…</span>
                        @else
                            <span wire:loading.remove wire:target="send" class="inline-flex items-center gap-1.5">
                                Kirim
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M3.105 2.288a.75.75 0 0 0-.826.95l1.414 4.926A1.5 1.5 0 0 0 5.135 9.25h6.115a.75.75 0 0 1 0 1.5H5.135a1.5 1.5 0 0 0-1.442 1.086l-1.414 4.926a.75.75 0 0 0 .826.95 28.897 28.897 0 0 0 15.293-7.155.75.75 0 0 0 0-1.114A28.897 28.897 0 0 0 3.105 2.288Z" /></svg>
                            </span>
                            <span wire:loading.inline-flex wire:target="send" class="items-center gap-2"><x-spinner /> Mengirim…</span>
                        @endif
                    </button>
                </div>
            </div>

            <div id="prompt-help" class="mt-2 min-h-[1.25rem] text-center text-xs">
                @error('prompt')
                    <p class="text-danger font-medium" role="alert">{{ $message }}</p>
                @else
                    <p class="text-mute"><kbd class="font-sans font-medium text-body">Enter</kbd> untuk kirim · <kbd class="font-sans font-medium text-body">Shift + Enter</kbd> baris baru</p>
                @enderror
            </div>
        </form>
    </div>
</div>
