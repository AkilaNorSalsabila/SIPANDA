<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-slate-50">
            @include('layouts.navigation')

            <!-- Konten utama, digeser ke kanan seukuran sidebar di layar besar -->
            <div class="lg:ms-64">
                <!-- Page Heading -->
                @isset($header)
                    {{-- Tinggi tetap: h-16 (mobile, sama dengan topbar) dan lg:h-20 (desktop, sama dengan header logo sidebar) --}}
                    <header class="bg-white border-b border-slate-200">
                        <div class="h-16 lg:h-20 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex items-center">
                            {{ $header }}
                        </div>
                    </header>
                @endisset

                <!-- Page Content -->
                <main>
                    {{ $slot }}
                </main>
            </div>
        </div>
    </body>
</html>