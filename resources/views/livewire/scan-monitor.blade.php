{{-- Challenge 3: background scan of this project's source code, followed with wire:poll --}}
@php
    $run = $this->run;
    $done = $run?->status === 'done';
    $findings = $done ? ($run->findings ?? []) : [];
    $changed = $done ? ($run->changed ?? []) : [];
@endphp
<div class="grid gap-6 lg:grid-cols-[minmax(0,23rem)_minmax(0,1fr)] items-start">
    {{-- The poller only exists while a scan is queued/running, so an idle page sends no requests --}}
    @if ($run?->isActive())
        <div wire:poll.1s="cekStatus" wire:key="poll-{{ $run->id }}" class="hidden"></div>
    @endif

    {{-- Left: progress + what is scanned --}}
    <div class="space-y-6">
        <section class="rounded-2xl bg-white/90 p-5 ring-1 ring-black/[0.06] shadow-[0_18px_40px_-28px_rgba(0,0,0,0.45)] backdrop-blur" aria-label="Progres scan">
            @if ($run)
                @php $percent = $run->percent(); @endphp
                <div class="flex items-center justify-between gap-3 text-sm">
                    <span class="inline-flex items-center gap-2 font-semibold text-ink">
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
                    <span class="text-2xl font-semibold tabular-nums text-ink">{{ $percent }}<span class="text-sm text-mute">%</span></span>
                </div>

                <div class="mt-4 h-2.5 rounded-full bg-soft overflow-hidden" role="progressbar" aria-label="Progres scan"
                     aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percent }}">
                    <div @class(['h-full rounded-full transition-[width] duration-500', 'bg-danger' => $run->status === 'failed', 'progress-fill' => $run->status !== 'failed', 'is-running' => $run->isActive()])
                         style="width: {{ $percent }}%"></div>
                </div>

                <p class="mt-2.5 h-4 font-mono text-[11px] text-mute truncate">
                    @if ($run->status === 'running' && $run->current_file)
                        → {{ $run->current_file }}
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
                <p class="text-sm font-semibold text-ink">Belum pernah dijalankan</p>
                <p class="mt-1 text-sm text-mute">Mulai scan pertama untuk membuat sidik jari proyek.</p>
            @endif

            <button type="button" wire:click="start" wire:loading.attr="disabled" wire:target="start" @disabled($run?->isActive())
                    class="btn-brand mt-5 w-full h-11 inline-flex items-center justify-center gap-2 rounded-xl text-sm font-semibold disabled:cursor-not-allowed">
                @if ($run?->isActive())
                    <x-spinner /> Scan sedang berjalan
                @else
                    {{ $run ? 'Scan ulang' : 'Mulai scan' }}
                @endif
            </button>

            @if ($run)
                <p class="mt-3 text-center font-mono text-[11px] text-mute">Cek server terakhir {{ now()->format('H:i:s') }}</p>
            @endif
        </section>

        <section class="rounded-2xl bg-gradient-to-br from-[#f7f8f4] to-[#eef3ec] p-5 ring-1 ring-black/5" aria-label="Yang dipindai">
            <h2 class="text-sm font-semibold text-ink">Folder yang dipindai</h2>
            <div class="mt-3 flex flex-wrap gap-1.5">
                @foreach (config('scan.paths') as $path)
                    <span class="rounded-md bg-white px-2 py-0.5 font-mono text-xs text-ink ring-1 ring-black/10">{{ $path }}/</span>
                @endforeach
            </div>
            <dl class="mt-4 space-y-3 text-sm">
                <div>
                    <dt class="font-medium text-ink">Sidik jari SHA-256</dt>
                    <dd class="text-mute">Hash tiap file, untuk tahu file mana yang baru, berubah, atau dihapus sejak scan terakhir.</dd>
                </div>
                <div>
                    <dt class="font-medium text-ink">Kredensial bocor</dt>
                    <dd class="text-mute">Password, token, atau API key yang ditulis langsung di kode, bukan di <span class="font-mono text-xs">.env</span>.</dd>
                </div>
                <div>
                    <dt class="font-medium text-ink">Cara kerja</dt>
                    <dd class="text-mute">Berjalan di queue worker; halaman ini memantau progresnya dengan <span class="font-mono text-xs">wire:poll</span>.</dd>
                </div>
            </dl>
        </section>
    </div>

    {{-- Right: results --}}
    <div class="space-y-6 min-w-0">
        @if ($done)
            <div class="grid grid-cols-3 gap-3">
                @foreach ([['File dipindai', $run->total, 'text-ink'], ['Temuan', count($findings), count($findings) ? 'text-danger' : 'text-ink'], ['Berubah', count($changed), 'text-ink']] as [$label, $value, $color])
                    <div class="rounded-2xl bg-white/90 px-4 py-3.5 ring-1 ring-black/[0.06] shadow-[0_12px_30px_-24px_rgba(0,0,0,0.5)]">
                        <p class="text-xs text-mute">{{ $label }}</p>
                        <p class="mt-0.5 text-2xl font-semibold tabular-nums {{ $color }}">{{ $value }}</p>
                    </div>
                @endforeach
            </div>

            <section class="rounded-2xl bg-white/90 ring-1 ring-black/[0.06] shadow-[0_18px_40px_-28px_rgba(0,0,0,0.45)]">
                <div class="flex flex-wrap items-center justify-between gap-3 px-5 pt-5">
                    <div>
                        <h2 class="text-[15px] font-semibold text-ink">Kemungkinan kredensial di kode</h2>
                        <p class="text-sm text-mute">Pindahkan nilai rahasia ke <span class="font-mono text-xs">.env</span> lalu baca dengan <span class="font-mono text-xs">env()</span>.</p>
                    </div>
                    @if ($findings !== [])
                        <button type="button" wire:click="askAi"
                                class="gradient-border h-9 inline-flex items-center gap-2 rounded-lg px-3 text-sm font-medium text-ink">
                            <span class="brand-dot h-2 w-2 rounded-[2px]" aria-hidden="true"></span>
                            Tanyakan ke AI di Workspace
                        </button>
                    @endif
                </div>
                @if ($findings !== [])
                    <ul class="mt-4 divide-y divide-black/5 border-t border-black/5">
                        @foreach (array_slice($findings, 0, 30) as $finding)
                            <li class="flex items-center justify-between gap-3 px-5 py-2.5">
                                <span class="min-w-0 truncate font-mono text-xs">
                                    <span class="text-mute">{{ dirname($finding['file']) }}/</span><span class="font-medium text-ink">{{ basename($finding['file']) }}</span><span class="text-mute">:{{ $finding['line'] }}</span>
                                </span>
                                <span class="shrink-0 rounded-md bg-danger/10 px-2 py-0.5 text-[11px] font-medium text-danger">{{ str_replace('_', ' ', $finding['rule']) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="m-5 mt-4 rounded-xl bg-soft px-4 py-3 text-sm text-body">Tidak ada kredensial yang ditulis langsung di kode.</p>
                @endif
            </section>

            <section class="rounded-2xl bg-white/90 ring-1 ring-black/[0.06] shadow-[0_18px_40px_-28px_rgba(0,0,0,0.45)]">
                <div class="px-5 pt-5">
                    <h2 class="text-[15px] font-semibold text-ink">File berubah sejak scan sebelumnya</h2>
                    <p class="text-sm text-mute">Dibandingkan dengan sidik jari dari scan selesai sebelumnya.</p>
                </div>
                @if ($changed !== [])
                    <ul class="mt-4 max-h-80 overflow-y-auto scroll-thin divide-y divide-black/5 border-t border-black/5">
                        @foreach ($changed as $item)
                            <li class="flex items-center gap-3 px-5 py-2">
                                <span @class([
                                    'shrink-0 w-16 rounded-md px-2 py-0.5 text-center text-[11px] font-medium',
                                    'bg-success/10 text-success' => $item['change'] === 'baru',
                                    'bg-warning/10 text-warning' => $item['change'] === 'berubah',
                                    'bg-danger/10 text-danger' => $item['change'] === 'dihapus',
                                ])>{{ $item['change'] }}</span>
                                <span class="min-w-0 truncate font-mono text-xs text-body">{{ $item['file'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="m-5 mt-4 rounded-xl bg-soft px-4 py-3 text-sm text-body">Tidak ada perubahan, atau ini scan pertama.</p>
                @endif
            </section>
        @else
            <section class="grid min-h-[20rem] place-items-center rounded-2xl border border-dashed border-black/15 bg-white/60 p-8 text-center">
                <div class="max-w-sm">
                    <span class="mx-auto grid h-12 w-12 place-items-center rounded-xl bg-gradient-to-br from-lime-100 to-teal-100 text-success ring-1 ring-black/5" aria-hidden="true">
                        <svg class="h-6 w-6" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" /></svg>
                    </span>
                    <p class="mt-4 font-semibold text-ink">
                        {{ $run?->isActive() ? 'Scan sedang berjalan…' : 'Hasil scan akan tampil di sini' }}
                    </p>
                    <p class="mt-1 text-sm text-mute">
                        {{ $run?->isActive() ? 'Progres diperbarui setiap detik. Hasil muncul setelah semua file selesai dipindai.' : 'Tekan "Mulai scan" untuk memeriksa kode proyek.' }}
                    </p>
                </div>
            </section>
        @endif
    </div>
</div>
