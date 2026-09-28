<!-- resources/views/layouts/navigation.blade.php -->
<div x-data="{ mobileOpen: false }">

    <!-- ============ SIDEBAR (desktop) ============ -->
    <aside class="hidden lg:flex lg:flex-col lg:fixed lg:inset-y-0 lg:w-64 bg-teal-700">
        <!-- Logo -->
        <div class="h-16 flex items-center gap-2.5 px-5 border-b border-teal-600/60">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                <div class="w-8 h-8 rounded-lg bg-white/15 flex items-center justify-center shrink-0">
                    <x-application-logo class="h-5 w-5 fill-current text-white" />
                </div>
                <span class="text-white font-bold text-sm tracking-wide">SIPANDA</span>
            </a>
        </div>

        <!-- Menu -->
        <nav class="flex-1 px-3 py-5 space-y-1 overflow-y-auto">
            <p class="px-3 mb-2 text-[10px] font-semibold uppercase tracking-wider text-teal-200/70">
                Menu Utama
            </p>

            <a href="{{ route('dashboard') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                       {{ request()->routeIs('dashboard')
                            ? 'bg-white text-teal-700 shadow-sm'
                            : 'text-teal-50 hover:bg-teal-600/60' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7m-9-2v10a1 1 0 001 1h3m-4-11l-2 2m-2 2v10a1 1 0 001 1h3m10-10v10a1 1 0 01-1 1h-3m4-11l2 2m-6 9v-6a1 1 0 00-1-1h-2a1 1 0 00-1 1v6" />
                </svg>
                Dashboard
            </a>

            <a href="{{ route('peta.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                       {{ request()->routeIs('peta.*')
                            ? 'bg-white text-teal-700 shadow-sm'
                            : 'text-teal-50 hover:bg-teal-600/60' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                </svg>
                Peta
            </a>

            <a href="{{ route('import.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                       {{ request()->routeIs('import.*')
                            ? 'bg-white text-teal-700 shadow-sm'
                            : 'text-teal-50 hover:bg-teal-600/60' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3" />
                </svg>
                Import Data
            </a>
        </nav>

        <!-- User panel (bottom) -->
        <div class="border-t border-teal-600/60 p-3" x-data="{ open: false }">
            <button @click="open = !open"
                class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg hover:bg-teal-600/60 transition">
                <div class="w-8 h-8 rounded-full bg-white/15 flex items-center justify-center text-white text-xs font-bold shrink-0">
                    {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 1)) }}
                </div>
                <div class="text-left overflow-hidden">
                    <p class="text-xs font-semibold text-white truncate">{{ Auth::user()->name }}</p>
                    <p class="text-[10px] text-teal-200/70 truncate">{{ Auth::user()->email }}</p>
                </div>
                <svg class="w-4 h-4 ms-auto text-teal-200 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
            </button>

            <div x-show="open" @click.outside="open = false" x-cloak
                class="mt-1 bg-teal-800 rounded-lg overflow-hidden shadow-lg">
                <a href="{{ route('profile.edit') }}"
                    class="block px-4 py-2.5 text-xs text-teal-50 hover:bg-teal-700 transition">
                    {{ __('Profile') }}
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <a href="{{ route('logout') }}"
                        onclick="event.preventDefault(); this.closest('form').submit();"
                        class="block px-4 py-2.5 text-xs text-teal-50 hover:bg-teal-700 transition cursor-pointer">
                        {{ __('Log Out') }}
                    </a>
                </form>
            </div>
        </div>
    </aside>

    <!-- ============ TOPBAR (mobile) ============ -->
    <div class="lg:hidden sticky top-0 z-40 bg-teal-700 h-16 flex items-center justify-between px-4">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-white/15 flex items-center justify-center">
                <x-application-logo class="h-5 w-5 fill-current text-white" />
            </div>
            <span class="text-white font-bold text-sm">SIPANDA</span>
        </a>

        <button @click="mobileOpen = true" class="text-white p-1">
            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16" />
            </svg>
        </button>
    </div>

    <!-- ============ SIDEBAR (mobile, slide-over) ============ -->
    <div x-show="mobileOpen" x-cloak class="lg:hidden fixed inset-0 z-50" style="display:none">
        <div @click="mobileOpen = false" x-show="mobileOpen"
            x-transition:enter="transition-opacity ease-linear duration-200"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            x-transition:leave="transition-opacity ease-linear duration-150"
            x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
            class="fixed inset-0 bg-black/50"></div>

        <div x-show="mobileOpen"
            x-transition:enter="transition ease-in-out duration-200"
            x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in-out duration-150"
            x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full"
            class="relative flex flex-col w-64 h-full bg-teal-700">

            <div class="h-16 flex items-center justify-between px-5 border-b border-teal-600/60">
                <span class="text-white font-bold text-sm">SIPANDA</span>
                <button @click="mobileOpen = false" class="text-teal-100">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <nav class="flex-1 px-3 py-5 space-y-1 overflow-y-auto">
                <a href="{{ route('dashboard') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                           {{ request()->routeIs('dashboard') ? 'bg-white text-teal-700' : 'text-teal-50 hover:bg-teal-600/60' }}">
                    Dashboard
                </a>
                <a href="{{ route('peta.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                           {{ request()->routeIs('peta.*') ? 'bg-white text-teal-700' : 'text-teal-50 hover:bg-teal-600/60' }}">
                    Peta
                </a>
                <a href="{{ route('import.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                           {{ request()->routeIs('import.*') ? 'bg-white text-teal-700' : 'text-teal-50 hover:bg-teal-600/60' }}">
                    Import Data
                </a>
            </nav>

            <div class="border-t border-teal-600/60 p-4">
                <p class="text-xs font-semibold text-white truncate">{{ Auth::user()->name }}</p>
                <p class="text-[10px] text-teal-200/70 truncate mb-3">{{ Auth::user()->email }}</p>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <a href="{{ route('logout') }}"
                        onclick="event.preventDefault(); this.closest('form').submit();"
                        class="block text-center py-2 rounded-lg bg-teal-800 text-teal-50 text-xs font-semibold cursor-pointer">
                        {{ __('Log Out') }}
                    </a>
                </form>
            </div>
        </div>
    </div>
</div>