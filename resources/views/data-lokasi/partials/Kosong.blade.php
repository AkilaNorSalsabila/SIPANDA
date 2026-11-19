<div class="text-center">
    <span class="mx-auto flex w-12 h-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
    </span>
    <p class="mt-3 text-sm font-medium text-slate-700">Tidak ada SLS yang cocok</p>
    <p class="mt-1 text-xs text-slate-500">{{ $adaFilter ? 'Coba ubah atau hapus sebagian filter.' : 'Data SLS belum tersedia.' }}</p>
    @if ($adaFilter)
        <a href="{{ route('data-lokasi.index') }}" class="inline-block mt-4 px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold transition">Reset filter</a>
    @endif
</div>