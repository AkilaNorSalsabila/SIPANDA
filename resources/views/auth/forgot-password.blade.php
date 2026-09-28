<x-guest-layout title="Lupa Password">
    <div class="text-center mb-7">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Lupa Password?</h1>
        <p class="text-sm text-slate-500 mt-1.5 leading-relaxed">
            Masukkan email akunmu. Kami akan mengirim tautan untuk mengatur ulang password.
        </p>
    </div>

    @if (session('status'))
        <div class="mb-5 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs rounded-xl">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-5 p-3 bg-red-50 border border-red-200 text-red-600 text-xs rounded-xl">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <div>
            <label for="email" class="block text-xs font-semibold text-slate-700 mb-1.5">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus
                placeholder="nama@email.com"
                class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-800 placeholder-slate-400
                       focus:bg-white focus:outline-none focus:ring-2 focus:ring-teal-500/40 focus:border-teal-500 transition">
        </div>

        <button type="submit"
            class="w-full py-3 rounded-xl bg-gradient-to-r from-teal-600 to-emerald-600 hover:from-teal-700 hover:to-emerald-700
                   text-white text-sm font-semibold shadow-lg shadow-teal-600/25 active:scale-[0.99] transition">
            Kirim Tautan Reset
        </button>
    </form>

    <p class="text-center text-xs text-slate-500 mt-7">
        <a href="{{ route('login') }}" class="font-semibold text-teal-600 hover:text-teal-700 hover:underline">&larr; Kembali ke halaman masuk</a>
    </p>
</x-guest-layout>
