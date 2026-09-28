<x-guest-layout title="Masuk">
    <h1 class="text-xl font-bold text-slate-900">Masuk ke SIPANDA</h1>
    <p class="text-xs text-slate-500 mt-1 mb-6">Silakan masuk untuk mengakses peta dan data kegiatan.</p>

    {{-- Pesan status (mis. setelah registrasi berhasil) --}}
    @if (session('status'))
        <div class="mb-4 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs rounded-lg">
            {{ session('status') }}
        </div>
    @endif

    {{-- Pesan error validasi / gagal login --}}
    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-600 text-xs rounded-lg">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <label for="login" class="block text-xs font-semibold text-slate-700 mb-1">
                Username atau Email
            </label>
            <input id="login" type="text" name="login" value="{{ old('login') }}" required autofocus
                class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm text-slate-800
                       focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent">
        </div>

        <div>
            <label for="password" class="block text-xs font-semibold text-slate-700 mb-1">
                Password
            </label>
            <input id="password" type="password" name="password" required
                class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm text-slate-800
                       focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent">
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-xs text-slate-600 select-none">
                <input type="checkbox" name="remember"
                    class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                Ingat saya
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-xs font-semibold text-teal-600 hover:underline">
                    Lupa password?
                </a>
            @endif
        </div>

        <button type="submit"
            class="w-full py-2.5 rounded-lg bg-teal-600 hover:bg-teal-700 active:bg-teal-800
                   text-white text-sm font-semibold transition">
            Masuk
        </button>

        <p class="text-center text-xs text-slate-600 pt-2">
            Belum punya akun?
            <a href="{{ route('register') }}" class="font-semibold text-teal-600 hover:underline">
                Daftar di sini
            </a>
        </p>
    </form>
</x-guest-layout>
