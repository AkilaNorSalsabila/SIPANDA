<x-guest-layout title="Daftar" size="lg">
    <div class="text-center mb-7">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Buat Akun Baru</h1>
        <p class="text-sm text-slate-500 mt-1.5">Lengkapi data diri untuk mengajukan akun.</p>
    </div>

    @if ($errors->any())
        <div class="mb-5 p-3 bg-red-50 border border-red-200 text-red-600 text-xs rounded-xl">
            <ul class="list-disc pl-4 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <label for="name" class="block text-xs font-semibold text-slate-700 mb-1.5">Nama Lengkap</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-800
                       focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 transition">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="username" class="block text-xs font-semibold text-slate-700 mb-1.5">Username</label>
                <input id="username" type="text" name="username" value="{{ old('username') }}" required
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-800
                           focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 transition">
            </div>

            <div>
                <label for="phone" class="block text-xs font-semibold text-slate-700 mb-1.5">
                    No. Telepon <span class="font-normal text-slate-400">(opsional)</span>
                </label>
                <input id="phone" type="tel" name="phone" value="{{ old('phone') }}"
                    class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-800
                           focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 transition">
            </div>
        </div>

        <div>
            <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-800
                       focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 transition">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5">Password</label>
                <x-password-input id="password" name="password" autocomplete="new-password" />
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1.5">Konfirmasi Password</label>
                <x-password-input id="password_confirmation" name="password_confirmation" autocomplete="new-password" />
            </div>
        </div>

        <div class="flex gap-2.5 p-3 bg-teal-50 border border-teal-100 text-teal-800 text-xs rounded-xl leading-relaxed">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 mt-0.5 shrink-0 text-teal-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 8h.01M11 12h1v4h1"/>
            </svg>
            <p>Akun yang didaftarkan berstatus <b>menunggu persetujuan</b>. Kamu baru bisa masuk setelah disetujui admin.</p>
        </div>

        <button type="submit"
            class="w-full py-3 rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700
                   text-white text-sm font-semibold shadow-lg shadow-teal-600/25 active:scale-[0.99] transition">
            Daftar Sekarang
        </button>
    </form>

    <p class="text-center text-xs text-slate-500 mt-7">
        Sudah punya akun?
        <a href="{{ route('login') }}" class="font-semibold text-teal-600 hover:text-teal-700 hover:underline">Masuk di sini</a>
    </p>
</x-guest-layout>
