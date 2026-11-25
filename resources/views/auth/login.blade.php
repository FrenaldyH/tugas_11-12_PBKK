<x-guest-layout>
    <h2 class="text-2xl font-semibold tracking-tight text-ink">Selamat datang kembali</h2>
    <p class="mt-1 text-sm text-mute">Masuk untuk membuka workspace dan playground API.</p>

    <!-- Session Status -->
    <x-auth-session-status class="mt-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-6 space-y-4">
        @csrf

        <div>
            <x-input-label for="email" :value="'Email'" />
            <x-text-input id="email" class="block mt-1.5 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="nama@contoh.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <div class="flex items-center justify-between">
                <x-input-label for="password" :value="'Password'" />
                @if (Route::has('password.request'))
                    <a class="text-xs font-medium text-mute hover:text-ink underline-offset-2 hover:underline" href="{{ route('password.request') }}">Lupa password?</a>
                @endif
            </div>
            <x-text-input id="password" class="block mt-1.5 w-full" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <label for="remember_me" class="inline-flex items-center gap-2">
            <input id="remember_me" type="checkbox" class="rounded border-black/20 text-black focus:ring-brand-dark" name="remember">
            <span class="text-sm text-body">Ingat saya</span>
        </label>

        <x-primary-button class="w-full">
            Masuk
            <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M3 10a.75.75 0 0 1 .75-.75h10.638L10.23 5.29a.75.75 0 1 1 1.04-1.08l5.5 5.25a.75.75 0 0 1 0 1.08l-5.5 5.25a.75.75 0 1 1-1.04-1.08l4.158-3.96H3.75A.75.75 0 0 1 3 10Z" clip-rule="evenodd" /></svg>
        </x-primary-button>
    </form>

    <p class="mt-6 border-t border-black/5 pt-5 text-center text-sm text-mute">
        Belum punya akun? <a href="{{ route('register') }}" class="font-semibold text-ink underline-offset-2 hover:underline">Daftar sekarang</a>
    </p>
</x-guest-layout>
