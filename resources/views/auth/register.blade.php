<x-guest-layout title="Daftar">
    <h1 class="text-xl font-bold text-slate-900">Daftar Akun SIPANDA</h1>
    <p class="text-xs text-slate-500 mt-1 mb-6">Lengkapi data diri untuk mengajukan akun.</p>

    @if ($errors->any())
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-600 text-xs rounded-lg">
            <ul class="list-disc pl-4 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('register') }}" class="space-y-3.5">
        @csrf

        <div>
            <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus
                class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm
                       focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label for="username" class="block text-xs font-semibold text-slate-700 mb-1">Username</label>
                <input id="username" type="text" name="username" value="{{ old('username') }}" required
                    class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm
                           focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            </div>

            <div>
                <label for="phone" class="block text-xs font-semibold text-slate-700 mb-1">No. Telepon (Opsional)</label>
                <input id="phone" type="tel" name="phone" value="{{ old('phone') }}"
                    class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm
                           focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            </div>
        </div>

        <div>
            <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required
                class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm
                       focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent">
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div>
                <label for="password" class="block text-xs font-semibold text-slate-700 mb-1">Password</label>
                <input id="password" type="password" name="password" required
                    class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm
                           focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            </div>

            <div>
                <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1">
                    Konfirmasi Password
                </label>
                <input id="password_confirmation" type="password" name="password_confirmation" required
                    class="w-full px-3.5 py-2 rounded-lg border border-slate-300 text-sm
                           focus:outline-none focus:ring-2 focus:ring-teal-500 focus:border-transparent">
            </div>
        </div>

        <div class="p-2.5 bg-teal-50 border border-teal-100 text-teal-700 text-[11px] rounded-lg leading-relaxed">
            Akun yang didaftarkan akan berstatus <b>menunggu persetujuan</b>. Anda belum dapat masuk
            sebelum akun disetujui oleh admin.
        </div>

        <button type="submit"
            class="w-full mt-1 py-2.5 rounded-lg bg-teal-600 hover:bg-teal-700 active:bg-teal-800
                   text-white text-sm font-semibold transition">
            Daftar Sekarang
        </button>

        <p class="text-center text-xs text-slate-600 pt-1">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="font-semibold text-teal-600 hover:underline">Masuk di sini</a>
        </p>
    </form>
</x-guest-layout>
