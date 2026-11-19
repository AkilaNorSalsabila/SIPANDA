{{-- Riwayat perubahan satu SLS (hanya ditampilkan ke admin). Butuh $riwayat (collection Riwayat). --}}
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    <div class="px-4 sm:px-5 py-3.5 border-b border-slate-100 flex items-center justify-between gap-3">
        <div>
            <h3 class="font-semibold text-slate-800 text-sm">Riwayat perubahan</h3>
            <p class="text-xs text-slate-500 mt-0.5">Perubahan status & catatan perbaikan SLS ini</p>
        </div>
        <a href="{{ route('riwayat.index', ['modul' => 'sls', 'q' => $sls->id_sls]) }}" class="text-xs font-semibold text-teal-700 hover:underline whitespace-nowrap">Lihat semua</a>
    </div>

    @forelse ($riwayat as $r)
        <div class="px-4 sm:px-5 py-4 {{ ! $loop->first ? 'border-t border-slate-100' : '' }}" x-data="{ buka: false }">
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                @include('riwayat.partials.badge-aksi', ['aksi' => $r->aksi])
                <p class="text-xs text-slate-500">
                    <span class="font-medium text-slate-700">{{ $r->nama_user ?? '-' }}</span>
                    · {{ $r->created_at->format('d/m/Y H:i') }}
                </p>
            </div>
            <p class="mt-1.5 text-sm text-slate-700">{{ $r->keterangan }}</p>
            @if ($r->data_lama || $r->data_baru)
                <button type="button" @click="buka = ! buka" class="mt-1.5 text-xs font-semibold text-teal-700 hover:underline">
                    <span x-text="buka ? 'Sembunyikan rincian' : 'Lihat rincian'"></span>
                </button>
                <div x-show="buka" x-cloak class="mt-2 p-3 rounded-xl bg-slate-50 border border-slate-200">
                    @include('riwayat.partials.perubahan', ['r' => $r])
                </div>
            @endif
        </div>
    @empty
        <p class="px-4 py-10 text-center text-sm text-slate-400">Belum ada riwayat perubahan untuk SLS ini.</p>
    @endforelse
</div>
