<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'SIPANDA' }} — Sistem Informasi Peta dan Data</title>

    {{-- Sesuaikan dengan setup Vite/Tailwind hasil `php artisan breeze:install blade` --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-100">
    <div class="min-h-screen flex items-center justify-center p-4 sm:p-6">
        <div class="w-full max-w-5xl bg-white rounded-2xl shadow-xl overflow-hidden grid grid-cols-1 md:grid-cols-2">

            {{-- PANEL KIRI: identitas SIPANDA --}}
            <div class="hidden md:flex flex-col justify-between bg-gradient-to-br from-teal-700 via-teal-600 to-emerald-600 p-10 text-white">
                <div>
                    <div class="flex items-center gap-2">
                        <div class="w-10 h-10 rounded-xl bg-white/15 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                            </svg>
                        </div>
                        <span class="font-bold text-lg tracking-wide">SIPANDA</span>
                    </div>
                    <p class="mt-1 text-xs text-teal-100">Sistem Informasi Peta dan Data</p>
                </div>

                <div>
                    <h2 class="text-2xl font-semibold leading-snug">
                        Satu peta,<br>semua kegiatan statistik.
                    </h2>
                    <p class="mt-3 text-sm text-teal-100/90 leading-relaxed">
                        Kelola dan visualisasikan data SLS, bangunan, keluarga, dan usaha
                        untuk seluruh kegiatan — SE, SUSENAS, SAKERNAS, dan lainnya —
                        dalam satu platform terpadu.
                    </p>
                </div>

                <p class="text-[11px] text-teal-100/70">© {{ date('Y') }} SIPANDA. Seluruh hak cipta dilindungi.</p>
            </div>

            {{-- PANEL KANAN: form --}}
            <div class="p-8 sm:p-10 md:p-12 flex flex-col justify-center">
                <div class="md:hidden flex items-center gap-2 mb-6">
                    <div class="w-9 h-9 rounded-lg bg-teal-600 flex items-center justify-center text-white font-bold">S</div>
                    <span class="font-bold text-teal-700 text-lg">SIPANDA</span>
                </div>

                {{ $slot }}
            </div>

        </div>
    </div>
</body>
</html>
