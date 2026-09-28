<x-guest-layout title="Masuk">
    <div class="text-center mb-7">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Selamat Datang</h1>
        <p class="text-sm text-slate-500 mt-1.5">Masuk untuk mengakses peta dan data kegiatan.</p>
    </div>

    {{-- Pesan status (mis. setelah registrasi berhasil) --}}
    @if (session('status'))
        <div class="mb-5 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs rounded-xl">
            {{ session('status') }}
        </div>
    @endif

    {{-- Pesan error validasi / gagal login --}}
    @if ($errors->any())
        <div class="mb-5 p-3 bg-red-50 border border-red-200 text-red-600 text-xs rounded-xl">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <div>
            <label for="login" class="block text-xs font-semibold text-slate-700 mb-1.5">Username atau Email</label>
            <input id="login" type="text" name="login" value="{{ old('login') }}" required autofocus
                autocomplete="username" placeholder="Masukkan username atau email"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-800 placeholder-slate-400
                       focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 transition">
        </div>

        <div>
            <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5">Password</label>
            <x-password-input id="password" name="password" autocomplete="current-password" placeholder="Masukkan password" />
        </div>

        <div class="flex items-center justify-between">
            <label class="flex items-center gap-2 text-xs text-slate-600 select-none cursor-pointer">
                <input type="checkbox" name="remember"
                    class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                Ingat saya
            </label>

            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="text-xs font-semibold text-teal-600 hover:text-teal-700 hover:underline">
                    Lupa password?
                </a>
            @endif
        </div>

        <button type="submit"
            class="w-full py-3 rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700
                   text-white text-sm font-semibold shadow-lg shadow-teal-600/25 active:scale-[0.99] transition">
            Masuk
        </button>
    </form>

    <p class="text-center text-xs text-slate-500 mt-7">
        Belum punya akun?
        <a href="{{ route('register') }}" class="font-semibold text-teal-600 hover:text-teal-700 hover:underline">Daftar di sini</a>
    </p>
</x-guest-layout>
