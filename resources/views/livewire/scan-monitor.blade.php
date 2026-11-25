{{-- Challenge 3: background scan followed with wire:poll --}}
@php $run = $this->run; @endphp
<div class="shrink-0 xl:max-h-[50%] xl:overflow-y-auto border-t border-white/15 text-white px-4 py-4">
    {{-- The poller only exists while a scan is queued/running, so an idle page sends no requests --}}
    @if ($run?->isActive())
        <div wire:poll.1s="cekStatus" wire:key="poll-{{ $run->id }}"></div>
    @endif

    <div class="flex items-center justify-between gap-3">
        <div class="min-w-0">
            <p class="text-xs font-bold uppercase tracking-wider text-white/70">Scan workspace</p>
            <p class="text-sm font-bold">Hash SHA-256 &amp; pencarian kredensial bocor</p>
        </div>
        <button type="button" wire:click="start" wire:loading.attr="disabled" wire:target="start" @disabled($run?->isActive())
                class="shrink-0 h-9 px-4 rounded bg-brand text-black text-xs font-bold hover:bg-brand-dark disabled:bg-white/10 disabled:text-white/50 disabled:cursor-not-allowed">
            {{ $run?->isActive() ? 'Berjalan…' : 'Mulai scan' }}
        </button>
    </div>

    @if ($run)
        @php $percent = $run->percent(); @endphp
        <div class="mt-3">
            <div class="flex justify-between font-mono text-xs text-white/70">
                <span>
                    @switch($run->status)
                        @case('queued') Menunggu queue worker… @break
                        @case('running') {{ $run->processed }}/{{ $run->total }} file @break
                        @case('done') Selesai · {{ $run->total }} file @break
                        @default Gagal
                    @endswitch
                </span>
                <span>{{ $percent }}%</span>
            </div>
            <div class="mt-1 h-2 bg-white/10" role="progressbar" aria-label="Progres scan"
                 aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percent }}">
                <div @class(['h-full transition-[width] duration-500', 'bg-danger' => $run->status === 'failed', 'bg-brand' => $run->status !== 'failed'])
                     style="width: {{ $percent }}%"></div>
            </div>

            @if ($run->status === 'running' && $run->current_file)
                <p class="mt-2 font-mono text-xs text-white/70 truncate">→ {{ $run->current_file }}</p>
            @endif

            @if ($this->stale())
                <p class="mt-2 text-xs text-white">Job belum diambil. Jalankan <span class="font-mono">php artisan queue:work</span> di terminal lain.</p>
            @endif

            @if ($run->status === 'failed')
                <p class="mt-2 text-xs text-white">Scan gagal: {{ $run->error }}</p>
            @endif

            @if ($run->status === 'done')
                @php $findings = $run->findings ?? []; $changed = $run->changed ?? []; @endphp
                <div class="mt-3 grid grid-cols-2 gap-2 font-mono text-xs">
                    <div class="border border-white/15 px-3 py-2">
                        <span class="block text-white/70">Temuan</span>
                        <span class="text-lg font-bold">{{ count($findings) }}</span>
                    </div>
                    <div class="border border-white/15 px-3 py-2">
                        <span class="block text-white/70">Berubah sejak scan lalu</span>
                        <span class="text-lg font-bold">{{ count($changed) }}</span>
                    </div>
                </div>

                @if ($findings !== [])
                    <ul class="mt-2 max-h-28 overflow-y-auto font-mono text-xs space-y-1">
                        @foreach (array_slice($findings, 0, 20) as $finding)
                            <li class="truncate"><span class="text-danger" aria-hidden="true">■</span> {{ $finding['file'] }}:{{ $finding['line'] }} <span class="text-white/60">{{ $finding['rule'] }}</span></li>
                        @endforeach
                    </ul>
                    <button type="button" wire:click="askAi" class="mt-2 text-xs font-bold underline underline-offset-2 hover:no-underline">
                        Tanyakan temuan ini ke AI
                    </button>
                @endif

                @if ($changed !== [])
                    <ul class="mt-2 max-h-20 overflow-y-auto font-mono text-xs space-y-1 text-white/80">
                        @foreach (array_slice($changed, 0, 20) as $item)
                            <li class="truncate">{{ $item['change'] }} · {{ $item['file'] }}</li>
                        @endforeach
                    </ul>
                @endif
            @endif

            <p class="mt-2 font-mono text-[11px] text-white/50">Cek server terakhir: {{ now()->format('H:i:s') }}</p>
        </div>
    @endif
</div>
