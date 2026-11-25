<nav x-data="{ open: false }" class="nav-glow bg-black text-white shrink-0">
    <div class="px-4 sm:px-5">
        <div class="flex justify-between h-14">
            <div class="flex items-center gap-8">
                <a href="{{ route('workspace') }}" class="text-[17px]"><x-application-logo /></a>

                <div class="hidden sm:flex h-full gap-6">
                    <x-nav-link :href="route('workspace')" :active="request()->routeIs('workspace')">Workspace</x-nav-link>
                    <x-nav-link :href="route('scan')" :active="request()->routeIs('scan')">Scan Proyek</x-nav-link>
                    <x-nav-link :href="route('playground')" :active="request()->routeIs('playground')">Playground API</x-nav-link>
                </div>
            </div>

            <!-- Account menu -->
            <div class="hidden sm:flex sm:items-center">
                <x-dropdown align="right" width="w-60">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2.5 pl-1.5 pr-2.5 py-1.5 rounded-lg text-sm font-medium text-white hover:bg-white/10 transition">
                            <span class="grid h-7 w-7 place-items-center rounded-md bg-gradient-to-br from-lime-400 to-teal-500 text-black text-xs font-bold uppercase" aria-hidden="true">
                                {{ mb_substr(Auth::user()->name, 0, 1) }}
                            </span>
                            <span>{{ Auth::user()->name }}</span>
                            <svg class="h-4 w-4 text-white/60" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <div class="px-4 py-2.5 border-b border-black/5">
                            <p class="text-sm font-semibold text-ink truncate">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-mute truncate">{{ Auth::user()->email }}</p>
                        </div>
                        <x-dropdown-link :href="route('profile.edit')">Profil</x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault(); this.closest('form').submit();">
                                Keluar
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-lg text-white hover:bg-white/10" aria-label="Menu">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-white/15">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('workspace')" :active="request()->routeIs('workspace')">Workspace</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('scan')" :active="request()->routeIs('scan')">Scan Proyek</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('playground')" :active="request()->routeIs('playground')">Playground API</x-responsive-nav-link>
        </div>

        <div class="pt-4 pb-1 border-t border-white/15">
            <div class="px-4">
                <div class="font-semibold text-base">{{ Auth::user()->name }}</div>
                <div class="text-sm text-white/70">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">Profil</x-responsive-nav-link>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                        Keluar
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
