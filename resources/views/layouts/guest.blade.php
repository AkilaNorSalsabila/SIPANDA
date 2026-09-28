@php
    // Lebar kartu: <x-guest-layout size="lg"> untuk form yang lebih lebar (mis. register)
    $cardWidth = $attributes->get('size') === 'lg' ? 'max-w-xl' : 'max-w-md';
    $pageTitle = $attributes->get('title', 'SIPANDA');
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }} — SIPANDA</title>
    <link rel="icon" type="image/png" href="{{ asset('images/logo-sipanda.png') }}">

    {{-- Sesuaikan dengan setup Vite/Tailwind hasil `php artisan breeze:install blade` --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        /*
         * Membantu menyamarkan latar putih/abu-abu tipis pada logo
         * jika file PNG belum benar-benar transparan.
         * Aman dibiarkan walau logonya sudah transparan.
         */
        .logo-clean { mix-blend-mode: multiply; filter: brightness(1.06) contrast(1.04); }
    </style>
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-800">

    <div class="relative min-h-screen overflow-hidden flex items-center justify-center px-4 py-10">

        {{-- Dekorasi latar --}}
        <div class="pointer-events-none absolute -top-40 -left-40 h-[28rem] w-[28rem] rounded-full bg-teal-200/50 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-40 -right-40 h-[28rem] w-[28rem] rounded-full bg-emerald-200/50 blur-3xl"></div>
        <div class="pointer-events-none absolute inset-0 opacity-[0.35]"
             style="background-image: radial-gradient(#94a3b8 1px, transparent 1px); background-size: 22px 22px;
                    mask-image: radial-gradient(ellipse at center, black 20%, transparent 70%);
                    -webkit-mask-image: radial-gradient(ellipse at center, black 20%, transparent 70%);"></div>

        <main class="relative w-full {{ $cardWidth }}">

            <div class="bg-white rounded-3xl shadow-xl shadow-teal-900/5 ring-1 ring-slate-200/70 px-7 py-8 sm:px-10 sm:py-10">

                {{-- Logo (tengah) --}}
                <a href="/" class="block mb-6">
                    <img src="{{ asset('images/logo-sipanda.png') }}" alt="Logo SIPANDA"
                         class="logo-clean h-28 sm:h-32 w-auto mx-auto">
                </a>

                {{ $slot }}
            </div>

            <p class="mt-6 text-center text-[11px] text-slate-400">
                &copy; {{ date('Y') }} SIPANDA — Sistem Informasi Peta dan Data
            </p>
        </main>
    </div>

    <script>
        // Tombol tampilkan / sembunyikan password
        document.querySelectorAll('[data-toggle-password]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var input = document.getElementById(btn.dataset.togglePassword);
                if (!input) return;
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.classList.toggle('text-teal-600', show);
            });
        });
    </script>
</body>
</html>
