{{-- Challenge 3: background scan followed with wire:poll --}}
@php $run = $this->run; @endphp
<div class="flex-1 min-h-0 overflow-y-auto scroll-thin p-4 space-y-4">
    {{-- The poller only exists while a scan is queued/running, so an idle page sends no requests --}}
    @if ($run?->isActive())
        <div wire:poll.1s="cekStatus" wire:key="poll-{{ $run->id }}"></div>
    @endif

    <div>
        <h2 class="text-[15px] font-semibold text-ink">Scan workspace</h2>
        <p class="mt-1 text-sm text-mute">
            Membuat sidik jari SHA-256 setiap file kode, mencari kredensial yang ditulis langsung di kode, dan membandingkan
            dengan scan sebelumnya. Berjalan di queue, progres dipantau dengan <code class="font-mono text-xs text-body">wire:poll</code>.
        </p>
    </div>

    {{-- Progress card --}}
    <div class="rounded-xl border border-black/10 p-4">
        @if ($run)
            @php $percent = $run->percent(); @endphp
            <div class="flex items-center justify-between gap-3 text-sm">
                <span class="inline-flex items-center gap-2 font-medium text-ink">
                    @switch($run->status)
                        @case('queued')
                            <span class="h-2 w-2 rounded-full bg-warning animate-pulse" aria-hidden="true"></span> Menunggu queue worker…
                            @break
                        @case('running')
                            <span class="h-2 w-2 rounded-full bg-brand animate-pulse" aria-hidden="true"></span> Memindai {{ $run->processed }}/{{ $run->total }} file
                            @break
                        @case('done')
                            <span class="h-2 w-2 rounded-full bg-brand" aria-hidden="true"></span> Selesai · {{ $run->total }} file
                            @break
                        @default
                            <span class="h-2 w-2 rounded-full bg-danger" aria-hidden="true"></span> Gagal
                    @endswitch
                </span>
                <span class="tabular-nums text-mute">{{ $percent }}%</span>
            </div>

            <div class="mt-3 h-2 rounded-full bg-soft overflow-hidden" role="progressbar" aria-label="Progres scan"
                 aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percent }}">
                <div @class(['h-full rounded-full transition-[width] duration-500', 'bg-danger' => $run->status === 'failed', 'bg-brand' => $run->status !== 'failed'])
                     style="width: {{ $percent }}%"></div>
            </div>

            <p class="mt-2 h-4 font-mono text-[11px] text-mute truncate">
                @if ($run->status === 'running' && $run->current_file)
                    {{ $run->current_file }}
                @elseif ($run->finished_at)
                    Selesai {{ $run->finished_at->locale('id')->diffForHumans() }}
                @endif
            </p>

            @if ($this->stale())
                <p class="mt-3 rounded-lg bg-warning/10 px-3 py-2 text-xs text-warning">
                    Job belum diambil. Jalankan <span class="font-mono">php artisan queue:work</span> di terminal lain.
                </p>
            @endif
            @if ($run->status === 'failed')
                <p class="mt-3 rounded-lg bg-danger/10 px-3 py-2 text-xs text-danger">Scan gagal: {{ $run->error }}</p>
            @endif
        @else
            <p class="text-sm text-mute">Belum pernah dijalankan.</p>
        @endif

        <button type="button" wire:click="start" wire:loading.attr="disabled" wire:target="start" @disabled($run?->isActive())
                class="mt-4 w-full h-10 inline-flex items-center justify-center gap-2 rounded-lg bg-black text-white text-sm font-medium hover:bg-body transition
                       disabled:bg-soft disabled:text-mute disabled:cursor-not-allowed">
            @if ($run?->isActive())
                <x-spinner /> Scan sedang berjalan
            @else
                {{ $run ? 'Scan ulang' : 'Mulai scan' }}
            @endif
        </button>
    </div>

    @if ($run?->status === 'done')
        @php $findings = $run->findings ?? []; $changed = $run->changed ?? []; @endphp

        <div class="grid grid-cols-3 gap-2">
            @foreach ([['File', $run->total, 'text-ink'], ['Temuan', count($findings), count($findings) ? 'text-danger' : 'text-ink'], ['Berubah', count($changed), 'text-ink']] as [$label, $value, $color])
                <div class="rounded-xl bg-soft px-3 py-2.5">
                    <p class="text-xs text-mute">{{ $label }}</p>
                    <p class="text-xl font-semibold tabular-nums {{ $color }}">{{ $value }}</p>
                </div>
            @endforeach
        </div>

        @if ($findings !== [])
            <div>
                <h3 class="text-sm font-semibold text-ink">Kemungkinan kredensial di kode</h3>
                <ul class="mt-2 divide-y divide-black/5 rounded-xl border border-black/10">
                    @foreach (array_slice($findings, 0, 20) as $finding)
                        <li class="flex items-center justify-between gap-3 px-3 py-2">
                            <span class="min-w-0">
                                <span class="block truncate font-mono text-xs font-medium text-ink">{{ basename($finding['file']) }}<span class="text-mute">:{{ $finding['line'] }}</span></span>
                                <span class="block truncate font-mono text-[11px] text-mute">{{ dirname($finding['file']) }}/</span>
                            </span>
                            <span class="shrink-0 rounded-md bg-danger/10 px-1.5 py-0.5 text-[11px] font-medium text-danger">{{ str_replace('_', ' ', $finding['rule']) }}</span>
                        </li>
                    @endforeach
                </ul>
                <button type="button" wire:click="askAi" x-on:click="$store.ui.closeDrawers(); $nextTick(() => document.getElementById('prompt')?.focus())"
                        class="mt-2 w-full h-9 rounded-lg border border-black/10 text-sm font-medium text-ink hover:bg-soft transition">
                    Tanyakan temuan ini ke AI
                </button>
            </div>
        @else
            <p class="rounded-xl bg-soft px-3 py-2.5 text-sm text-body">Tidak ada kredensial yang ditulis langsung di kode.</p>
        @endif

        @if ($changed !== [])
            <details class="group rounded-xl border border-black/10">
                <summary class="flex cursor-pointer items-center justify-between px-3 py-2.5 text-sm font-semibold text-ink">
                    {{ count($changed) }} file berubah sejak scan sebelumnya
                    <svg class="h-4 w-4 text-mute transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" /></svg>
                </summary>
                <ul class="divide-y divide-black/5 border-t border-black/5">
                    @foreach (array_slice($changed, 0, 30) as $item)
                        <li class="flex items-center gap-2 px-3 py-1.5">
                            <span @class([
                                'shrink-0 w-14 text-[11px] font-medium',
                                'text-success' => $item['change'] === 'baru',
                                'text-warning' => $item['change'] === 'berubah',
                                'text-danger' => $item['change'] === 'dihapus',
                            ])>{{ $item['change'] }}</span>
                            <span class="min-w-0 truncate font-mono text-xs text-body">{{ $item['file'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </details>
        @endif
    @endif

    @if ($run)
        <p class="text-center font-mono text-[11px] text-mute">Cek server terakhir {{ now()->format('H:i:s') }}</p>
    @endif
</div>
