<div class="text-center">
    <span class="mx-auto flex w-12 h-12 items-center justify-center rounded-full bg-slate-100 text-slate-400">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m5-2a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
    </span>
    <p class="mt-3 text-sm font-medium text-slate-700">{{ $adaFilter ? 'Tidak ada riwayat yang cocok' : 'Belum ada riwayat' }}</p>
    <p class="mt-1 text-xs text-slate-500">{{ $adaFilter ? 'Coba ubah atau hapus sebagian filter.' : 'Aktivitas akan muncul di sini setelah ada perubahan data.' }}</p>
    @if ($adaFilter)
        <a href="{{ route('riwayat.index') }}" class="inline-block mt-4 px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold transition">Reset filter</a>
    @endif
</div>
