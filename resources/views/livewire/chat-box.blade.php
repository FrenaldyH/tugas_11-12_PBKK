@php use App\Support\Markdown; @endphp

<div class="flex-1 flex flex-col min-h-0">
    {{-- Header: conversation title + agent theme (system prompt) --}}
    <header class="shrink-0 h-16 px-4 sm:px-6 flex items-center justify-between gap-4 border-b border-hairline">
        <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-wider text-mute">Percakapan</p>
            <h1 class="text-base font-bold text-ink truncate">{{ $this->conversation?->title ?? 'Percakapan baru' }}</h1>
        </div>
        <div class="flex items-center gap-2 shrink-0">
            <label for="theme" class="hidden sm:block text-xs font-bold uppercase tracking-wider text-mute">Agent</label>
            <select id="theme" wire:model.live="theme" @disabled($waiting)
                    class="h-10 py-0 text-sm font-bold rounded-sm border-hairline-strong focus:border-brand-dark focus:ring-brand-dark disabled:bg-soft">
                @foreach ($themes as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </header>

    {{-- Bubbles. role="log" + aria-live lets screen readers announce new answers --}}
    <div x-data x-autoscroll class="flex-1 min-h-0 overflow-y-auto px-4 sm:px-6 py-6 space-y-6" role="log" aria-live="polite">
        @if ($this->bubbles->isEmpty() && ! $waiting)
            <div class="max-w-xl mx-auto pt-6 sm:pt-12">
                <span class="block h-3 w-3 bg-brand" aria-hidden="true"></span>
                <h2 class="mt-3 text-2xl font-bold text-ink">Mulai dari satu instruksi.</h2>
                <p class="mt-2 text-[15px] text-body">
                    Pilih agent di kanan atas, lalu tulis prompt. Jawaban mengalir kata demi kata, kode otomatis tampil
                    berwarna di panel kode, dan semua pesan tersimpan di akun Anda.
                </p>
                <div class="mt-6 grid gap-2 sm:grid-cols-2">
                    @foreach ($suggestions as $suggestion)
                        <button type="button" wire:click="usePrompt(@js($suggestion))"
                                class="text-left text-sm border border-hairline rounded-sm px-4 py-3 hover:border-ink transition">
                            {{ $suggestion }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        @foreach ($this->bubbles as $message)
            @if ($message->role === 'user')
                <div wire:key="msg-{{ $message->id }}" class="flex justify-end">
                    <div class="max-w-[85%] bg-black text-white rounded-sm px-4 py-3 text-[15px] leading-relaxed whitespace-pre-wrap break-words">{{ $message->content }}</div>
                </div>
            @else
                @php $failed = $message->status === 'failed'; @endphp
                <div wire:key="msg-{{ $message->id }}" class="flex gap-3">
                    <span class="mt-1 h-3 w-3 shrink-0 {{ $failed ? 'bg-danger' : 'bg-brand' }}" aria-hidden="true"></span>
                    <div class="min-w-0 flex-1 max-w-[92%]">
                        <div @class([
                            'rounded-sm px-4 py-3',
                            'border border-hairline bg-white' => ! $failed,
                            'border-l-4 border-danger bg-soft' => $failed,
                        ])>
                            {{-- wire:ignore: highlight.js colors this once; a re-render must not strip the colors --}}
                            <div wire:ignore x-data x-highlight class="prose-chat text-[15px] leading-relaxed break-words">{!! Markdown::toHtml($message->content) !!}</div>
                        </div>
                        <div class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-mute">
                            <span class="inline-flex items-center gap-1 font-bold uppercase {{ $failed ? 'text-danger' : 'text-success' }}">
                                <span class="h-2 w-2 {{ $failed ? 'bg-danger' : 'bg-brand' }}" aria-hidden="true"></span>{{ $failed ? 'Gagal' : 'Berhasil' }}
                            </span>
                            @if ($message->provider)
                                <span class="font-mono">{{ $message->provider }} · {{ $message->model }}</span>
                            @endif
                            @if ($message->total_tokens)
                                <span class="font-mono">{{ $message->total_tokens }} token</span>
                            @endif
                            <time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('H:i') }}</time>
                            @if (Markdown::codeBlocks($message->content) !== [])
                                <button type="button" wire:click="$dispatch('show-code', { id: {{ $message->id }} })"
                                        class="font-bold text-ink underline underline-offset-2 hover:no-underline">
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
            <div wire:key="waiting-{{ $conversationId }}" class="flex gap-3">
                <span class="mt-1 h-3 w-3 shrink-0 bg-brand animate-pulse" aria-hidden="true"></span>
                <div class="min-w-0 flex-1 max-w-[92%] rounded-sm px-4 py-3 bg-elevated text-white">
                    <p x-data="{ s: 0, t: null, init() { this.t = setInterval(() => this.s++, 1000) }, destroy() { clearInterval(this.t) } }"
                       class="font-mono text-xs text-white/70">
                        <span wire:stream="status">Menghubungi AI… <span x-text="s"></span> s</span>
                    </p>
                    <p class="font-mono text-sm leading-relaxed whitespace-pre-wrap break-words"><span wire:stream="answer"></span><span class="caret" aria-hidden="true"></span></p>
                </div>
            </div>
        @endif
    </div>

    {{-- Prompt box --}}
    <form wire:submit="send" class="shrink-0 border-t border-hairline bg-white px-4 sm:px-6 py-4">
        <label for="prompt" class="block text-sm font-bold text-ink mb-1">Prompt</label>
        <div class="relative">
            <textarea id="prompt" rows="3" wire:model.live.debounce.400ms="prompt"
                      x-on:keydown.enter="if (! $event.shiftKey && ! $event.isComposing) { $event.preventDefault(); $wire.send() }"
                      placeholder="Tulis instruksi untuk agent…"
                      aria-describedby="prompt-help"
                      @class([
                          'block w-full resize-none rounded-sm bg-black/5 px-4 pt-3 pb-8 text-[15px] focus:bg-white focus:ring-brand-dark',
                          'border-transparent focus:border-brand-dark' => ! $errors->has('prompt'),
                          'border-danger focus:border-danger' => $errors->has('prompt'),
                      ])></textarea>
            <span class="absolute right-3 bottom-2 font-mono text-xs {{ $sisaKarakter < 0 ? 'text-danger font-bold' : 'text-mute' }}">
                sisa {{ $sisaKarakter }}
            </span>
        </div>

        <div class="mt-2 flex items-start justify-between gap-4">
            <div id="prompt-help" class="min-w-0 text-xs pt-1">
                @error('prompt')
                    <p class="text-danger font-bold" role="alert">{{ $message }}</p>
                @else
                    <p class="text-mute"><kbd class="font-mono">Enter</kbd> kirim · <kbd class="font-mono">Shift+Enter</kbd> baris baru</p>
                @enderror
            </div>

            {{-- Locked twice: wire:loading disables it during any request of send/answer, and $waiting keeps it
                 disabled server-side for the whole answer, so a double click can never send twice --}}
            <button type="submit" wire:loading.attr="disabled" wire:target="send,answer" @disabled($waiting)
                    class="shrink-0 h-11 px-6 inline-flex items-center justify-center gap-2 rounded bg-brand text-black text-sm font-bold hover:bg-brand-dark transition disabled:bg-soft disabled:text-mute disabled:cursor-not-allowed">
                @if ($waiting)
                    <x-spinner /> AI sedang menjawab…
                @else
                    <span wire:loading.remove wire:target="send">Kirim</span>
                    <span wire:loading.inline-flex wire:target="send" class="items-center gap-2"><x-spinner /> Mengirim…</span>
                @endif
            </button>
        </div>
    </form>
</div>
