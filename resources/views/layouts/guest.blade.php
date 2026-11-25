<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Masuk · PromptLab</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700|jetbrains-mono:400,500&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles
    </head>
    <body class="font-sans text-body antialiased bg-white">
        <div class="min-h-screen lg:grid lg:grid-cols-[minmax(0,1.05fr)_minmax(0,1fr)]">
            {{-- Brand panel (large screens): dark gradient with a short product pitch --}}
            <aside class="bg-aurora-dark relative hidden lg:flex flex-col justify-between overflow-hidden px-12 py-10 text-white">
                <div class="dot-grid-dark pointer-events-none absolute inset-0" aria-hidden="true"></div>

                <a href="/" class="relative text-lg"><x-application-logo /></a>

                <div class="relative max-w-lg">
                    <p class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-white/80 ring-1 ring-white/15">
                        <span class="brand-dot h-1.5 w-1.5 rounded-full" aria-hidden="true"></span> Agentic AI Workspace · PBKK ITS
                    </p>
                    <h1 class="mt-6 text-[2.6rem] font-semibold leading-[1.1] tracking-tight">
                        Prompt, konteks, dan memory <span class="text-gradient-light">dalam satu ruang kerja.</span>
                    </h1>
                    <p class="mt-4 text-[15px] leading-relaxed text-white/65">
                        Tulis instruksi, lihat jawaban mengalir kata demi kata, periksa kodenya, dan pindai proyek Anda,
                        semua tersimpan aman di akun sendiri.
                    </p>

                    <ul class="mt-8 grid gap-3 text-sm">
                        @foreach ([
                            ['Jawaban real-time', 'Streaming dari Ollama lokal, otomatis pindah ke Senopati AI ITS bila mati.'],
                            ['Panel kode', 'Blok kode dari AI tampil berwarna, siap disalin.'],
                            ['Scan workspace', 'Sidik jari SHA-256 dan pencarian kredensial yang bocor.'],
                        ] as [$title, $text])
                            <li class="flex gap-3 rounded-xl bg-white/[0.04] px-4 py-3 ring-1 ring-white/10">
                                <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-gradient-to-br from-lime-300 to-teal-400" aria-hidden="true"></span>
                                <span><span class="font-semibold text-white">{{ $title }}</span> <span class="text-white/60">· {{ $text }}</span></span>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <p class="relative text-xs text-white/45">Kelompok 1 · Pemrograman Berbasis Kerangka Kerja</p>
            </aside>

            {{-- Form side --}}
            <main class="bg-aurora relative flex min-h-screen flex-col">
                <header class="nav-glow bg-black px-5 h-14 flex items-center text-white lg:hidden">
                    <a href="/" class="text-[17px]"><x-application-logo /></a>
                </header>

                <div class="flex flex-1 items-center justify-center px-5 py-12">
                    <div class="w-full max-w-sm">
                        <div class="rounded-2xl bg-white/90 p-7 shadow-[0_24px_60px_-28px_rgba(0,0,0,0.45)] ring-1 ring-black/[0.06] backdrop-blur">
                            {{ $slot }}
                        </div>
                        <p class="mt-5 text-center text-xs text-mute">
                            Akun demo: <span class="font-mono text-body">demo@promptlab.test</span> / <span class="font-mono text-body">password</span>
                        </p>
                    </div>
                </div>
            </main>
        </div>
        @livewireScripts
    </body>
</html>
