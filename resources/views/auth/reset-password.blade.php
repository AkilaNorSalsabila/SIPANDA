<x-guest-layout title="Reset Password">
    <div class="text-center mb-7">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Atur Ulang Password</h1>
        <p class="text-sm text-slate-500 mt-1.5">Buat password baru untuk akun SIPANDA-mu.</p>
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

    <form method="POST" action="{{ route('password.store') }}" class="space-y-5">
        @csrf

        {{-- Token reset password --}}
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus
                autocomplete="username"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-800
                       focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 transition">
        </div>

        <div>
            <label for="password" class="block text-xs font-semibold text-slate-700 mb-1.5">Password Baru</label>
            <x-password-input id="password" name="password" autocomplete="new-password" />
        </div>

        <div>
            <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1.5">Konfirmasi Password Baru</label>
            <x-password-input id="password_confirmation" name="password_confirmation" autocomplete="new-password" />
        </div>

        <button type="submit"
            class="w-full py-3 rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700
                   text-white text-sm font-semibold shadow-lg shadow-teal-600/25 active:scale-[0.99] transition">
            Simpan Password Baru
        </button>
    </form>
</x-guest-layout>
