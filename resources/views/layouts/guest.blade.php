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
    <body class="font-sans text-body antialiased bg-soft">
        <div class="min-h-screen flex flex-col">
            <header class="bg-black text-white h-16 flex items-center px-4 sm:px-6">
                <a href="/"><x-application-logo class="text-lg" /></a>
            </header>

            <main class="flex-1 flex flex-col items-center justify-center px-4 py-10">
                <div class="w-full sm:max-w-md">
                    <p class="text-xs font-bold uppercase tracking-wider text-mute">Agentic AI Workspace</p>
                    <h1 class="mt-1 mb-6 text-2xl font-bold text-ink">Prompt, konteks, dan memory dalam satu ruang kerja.</h1>

                    <div class="relative bg-white border border-hairline rounded-sm px-6 py-6">
                        <span class="absolute left-0 top-0 h-3 w-3 bg-brand" aria-hidden="true"></span>
                        {{ $slot }}
                    </div>

                    <p class="mt-4 text-xs text-mute">Akun demo: <span class="font-mono">demo@promptlab.test</span> / <span class="font-mono">password</span></p>
                </div>
            </main>

            <footer class="bg-black text-white/70 text-xs px-4 sm:px-6 py-4">PBKK ITS · Kelompok 1</footer>
        </div>
        @livewireScripts
    </body>
</html>
