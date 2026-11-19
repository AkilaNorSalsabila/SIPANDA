<!-- resources/views/layouts/navigation.blade.php -->
@php
    $authUser  = Auth::user();
    $isAdmin   = $authUser->isAdmin();
    // Viewer hanya boleh melihat Dashboard dan Peta.
    // Kegiatan, Import Data, dan Manajemen Akun khusus admin.
    $canImport   = $isAdmin;
    $canKegiatan = $isAdmin;

    // Menu Tagging: tersedia untuk semua peran. Viewer hanya bisa di SLS penugasannya.
    $halamanTagging = request()->routeIs('peta.index') && request()->query('mode') === 'tagging';
    $jumlahTugas    = $isAdmin ? null : \App\Models\PenugasanSls::where('user_id', $authUser->id)->count();
@endphp

<style>
    /*
     * Logo tanpa kotak putih di atas latar teal:
     * - grayscale + invert: logo gelap jadi terang, latar putih jadi hitam
     * - blend screen: hitam "hilang" ke warna latar, jadi tidak ada kotak
     * Aman untuk PNG transparan maupun PNG berlatar putih.
     */
    .logo-nav {
        height: 2.5rem; width: auto; max-width: 3rem; object-fit: contain; flex-shrink: 0;
        filter: grayscale(1) invert(1) contrast(1.15);
        mix-blend-mode: screen;
    }
</style>

<div x-data="{ mobileOpen: false }">

    <!-- ============ SIDEBAR (desktop) ============ -->
    <aside class="hidden lg:flex lg:flex-col lg:fixed lg:inset-y-0 lg:w-64 bg-teal-700">
        <!-- Logo (tinggi h-20 = sama dengan header konten di app.blade.php) -->
        <div class="h-20 flex items-center gap-2.5 px-5 border-b border-teal-600/60">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                <img src="{{ asset('images/logo-sipanda.png') }}" alt="Logo SIPANDA" class="logo-nav">
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

            {{-- Kegiatan: khusus admin --}}
            @if ($canKegiatan)
                <a href="{{ route('kegiatan.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                           {{ request()->routeIs('kegiatan.*')
                                ? 'bg-white text-teal-700 shadow-sm'
                                : 'text-teal-50 hover:bg-teal-600/60' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5a3 3 0 006 0" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 13l2 2 4-4M9 18h6" />
                    </svg>
                    Kegiatan
                </a>
            @endif

            @if ($canImport)
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
            @endif

            <a href="{{ route('data-lokasi.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                       {{ request()->routeIs('data-lokasi.*') ? 'bg-white text-teal-700 shadow-sm' : 'text-teal-50 hover:bg-teal-600/60' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18M10 3v18M5 3h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2z" />
                </svg>
                Data Lokasi
            </a>

            <a href="{{ route('peta.index') }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                       {{ request()->routeIs('peta.*') && ! $halamanTagging
                            ? 'bg-white text-teal-700 shadow-sm'
                            : 'text-teal-50 hover:bg-teal-600/60' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7" />
                </svg>
                Peta
            </a>

            {{-- Tagging: admin & viewer (viewer hanya di SLS penugasan) --}}
            <a href="{{ route('peta.index', ['mode' => 'tagging']) }}"
                class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                       {{ $halamanTagging ? 'bg-white text-teal-700 shadow-sm' : 'text-teal-50 hover:bg-teal-600/60' }}">
                <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v6m-3-3h6" />
                </svg>
                Tagging
                @if (! $isAdmin)
                    <span class="ms-auto min-w-[1.25rem] text-center text-[10px] font-bold px-1.5 py-0.5 rounded-full {{ $jumlahTugas ? 'bg-amber-400 text-teal-900' : 'bg-teal-800 text-teal-200' }}">
                        {{ $jumlahTugas }} SLS
                    </span>
                @endif
            </a>

            @unless ($isAdmin)
                <a href="{{ route('penugasan.saya') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                           {{ request()->routeIs('penugasan.saya') ? 'bg-white text-teal-700 shadow-sm' : 'text-teal-50 hover:bg-teal-600/60' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2M9 5h6M9 12h6M9 16h4" />
                    </svg>
                    Penugasan Saya
                </a>
            @endunless

            {{-- ===== Administrasi (khusus admin) ===== --}}
            @if ($isAdmin)
                <p class="px-3 pt-5 mb-2 text-[10px] font-semibold uppercase tracking-wider text-teal-200/70">
                    Administrasi
                </p>

                <a href="{{ route('kecamatan.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                           {{ request()->routeIs('kecamatan.*') ? 'bg-white text-teal-700 shadow-sm' : 'text-teal-50 hover:bg-teal-600/60' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Kecamatan
                </a>

                <a href="{{ route('kelurahan.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                           {{ request()->routeIs('kelurahan.*') ? 'bg-white text-teal-700 shadow-sm' : 'text-teal-50 hover:bg-teal-600/60' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Kelurahan
                </a>

                <a href="{{ route('admin.penugasan.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                           {{ request()->routeIs('admin.penugasan.*') ? 'bg-white text-teal-700 shadow-sm' : 'text-teal-50 hover:bg-teal-600/60' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2M9 5h6M9 14l2 2 4-4" />
                    </svg>
                    Penugasan Mitra
                </a>

                <a href="{{ route('admin.progres.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                           {{ request()->routeIs('admin.progres.*') ? 'bg-white text-teal-700 shadow-sm' : 'text-teal-50 hover:bg-teal-600/60' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Progres Mitra
                </a>

                <a href="{{ route('admin.users.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                           {{ request()->routeIs('admin.users.*')
                                ? 'bg-white text-teal-700 shadow-sm'
                                : 'text-teal-50 hover:bg-teal-600/60' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                    Manajemen Akun
                    @php $pendingCount = \App\Models\User::where('status', 'pending')->count(); @endphp
                    @if ($pendingCount > 0)
                        <span class="ms-auto min-w-[1.25rem] text-center text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-amber-400 text-teal-900">
                            {{ $pendingCount }}
                        </span>
                    @endif
                </a>

                <a href="{{ route('riwayat.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition
                           {{ request()->routeIs('riwayat.*') ? 'bg-white text-teal-700 shadow-sm' : 'text-teal-50 hover:bg-teal-600/60' }}">
                    <svg class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m5-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Riwayat Aktivitas
                </a>
            @endif
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
            <img src="{{ asset('images/logo-sipanda.png') }}" alt="Logo SIPANDA" class="logo-nav">
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
                <div class="flex items-center gap-2.5">
                    <img src="{{ asset('images/logo-sipanda.png') }}" alt="Logo SIPANDA" class="logo-nav">
                    <span class="text-white font-bold text-sm">SIPANDA</span>
                </div>
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

                @if ($canKegiatan)
                    <a href="{{ route('kegiatan.index') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                               {{ request()->routeIs('kegiatan.*') ? 'bg-white text-teal-700' : 'text-teal-50 hover:bg-teal-600/60' }}">
                        Kegiatan
                    </a>
                @endif

                <a href="{{ route('data-lokasi.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                           {{ request()->routeIs('data-lokasi.*') ? 'bg-white text-teal-700' : 'text-teal-50 hover:bg-teal-600/60' }}">
                    Data Lokasi
                </a>

                <a href="{{ route('peta.index') }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                           {{ request()->routeIs('peta.*') && ! $halamanTagging ? 'bg-white text-teal-700' : 'text-teal-50 hover:bg-teal-600/60' }}">
                    Peta
                </a>

                <a href="{{ route('peta.index', ['mode' => 'tagging']) }}"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                           {{ $halamanTagging ? 'bg-white text-teal-700' : 'text-teal-50 hover:bg-teal-600/60' }}">
                    Tagging
                    @if (! $isAdmin)
                        <span class="ms-auto text-[10px] font-bold px-1.5 py-0.5 rounded-full {{ $jumlahTugas ? 'bg-amber-400 text-teal-900' : 'bg-teal-800 text-teal-200' }}">{{ $jumlahTugas }} SLS</span>
                    @endif
                </a>

                @unless ($isAdmin)
                    <a href="{{ route('penugasan.saya') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                               {{ request()->routeIs('penugasan.saya') ? 'bg-white text-teal-700' : 'text-teal-50 hover:bg-teal-600/60' }}">
                        Penugasan Saya
                    </a>
                @endunless

                @if ($canImport)
                    <a href="{{ route('import.index') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                               {{ request()->routeIs('import.*') ? 'bg-white text-teal-700' : 'text-teal-50 hover:bg-teal-600/60' }}">
                        Import Data
                    </a>
                @endif

                @if ($isAdmin)
                    <a href="{{ route('kecamatan.index') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                               {{ request()->routeIs('kecamatan.*') ? 'bg-white text-teal-700' : 'text-teal-50 hover:bg-teal-600/60' }}">
                        Kecamatan
                    </a>

                    <a href="{{ route('kelurahan.index') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                               {{ request()->routeIs('kelurahan.*') ? 'bg-white text-teal-700' : 'text-teal-50 hover:bg-teal-600/60' }}">
                        Kelurahan
                    </a>

                    <a href="{{ route('admin.penugasan.index') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                               {{ request()->routeIs('admin.penugasan.*') ? 'bg-white text-teal-700' : 'text-teal-50 hover:bg-teal-600/60' }}">
                        Penugasan Mitra
                    </a>

                    <a href="{{ route('admin.progres.index') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                               {{ request()->routeIs('admin.progres.*') ? 'bg-white text-teal-700' : 'text-teal-50 hover:bg-teal-600/60' }}">
                        Progres Mitra
                    </a>

                    <a href="{{ route('admin.users.index') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                               {{ request()->routeIs('admin.users.*') ? 'bg-white text-teal-700' : 'text-teal-50 hover:bg-teal-600/60' }}">
                        Manajemen Akun
                        @if (($pendingCount ?? 0) > 0)
                            <span class="ms-auto text-[10px] font-bold px-1.5 py-0.5 rounded-full bg-amber-400 text-teal-900">{{ $pendingCount }}</span>
                        @endif
                    </a>

                    <a href="{{ route('riwayat.index') }}"
                        class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium
                               {{ request()->routeIs('riwayat.*') ? 'bg-white text-teal-700' : 'text-teal-50 hover:bg-teal-600/60' }}">
                        Riwayat Aktivitas
                    </a>
                @endif
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