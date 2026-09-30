<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">Kegiatan</h2>
    </x-slot>

    <div class="py-8" x-data="{
            showAddModal: false,
            showEditModal: false,
            showDetailModal: false,
            editing: {},
            detail: {},
            jenisLabel: @js($jenisList),
            bulanMap: {
                Januari: 1, Februari: 2, Maret: 3, April: 4, Mei: 5, Juni: 6,
                Juli: 7, Agustus: 8, September: 9, Oktober: 10, November: 11, Desember: 12,
            },
            // Pecah teks periode ('Agustus 2026' atau 'Agustus 2026 - Desember 2026')
            // jadi bulan_mulai/tahun_mulai/bulan_selesai/tahun_selesai, supaya form
            // Edit otomatis terisi sesuai data yang sudah tersimpan.
            openEdit(k) {
                this.editing = { ...k };

                const bagian = (k.periode || '').split(' - ');
                const pecah = (teks) => {
                    const kata = teks.trim().split(' ');
                    return { bulan: this.bulanMap[kata[0]] ?? '', tahun: kata[1] ?? '' };
                };

                const mulai = bagian[0] ? pecah(bagian[0]) : { bulan: '', tahun: '' };
                const selesai = bagian[1] ? pecah(bagian[1]) : { bulan: '', tahun: '' };

                this.editing.bulan_mulai = mulai.bulan;
                this.editing.tahun_mulai = mulai.tahun;
                this.editing.bulan_selesai = selesai.bulan;
                this.editing.tahun_selesai = selesai.tahun;

                this.showEditModal = true;
            },
        }"
        x-init="
            @if (old('_form') === 'edit' && ($errors->any() || session('error')))
                editing = {
                    id: '{{ old('_kegiatan_id') }}',
                    kode_kegiatan: @js(old('_kode_kegiatan')),
                    nama_kegiatan: @js(old('nama_kegiatan')),
                    jenis: '{{ old('jenis') }}',
                    bulan_mulai: '{{ old('bulan_mulai') }}',
                    tahun_mulai: '{{ old('tahun_mulai') }}',
                    bulan_selesai: '{{ old('bulan_selesai') }}',
                    tahun_selesai: '{{ old('tahun_selesai') }}',
                };
                showEditModal = true;
            @endif
        ">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">

            @if (session('status'))
                <div class="p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-lg">
                    {{ session('status') }}
                </div>
            @endif

            @if (session('error'))
                <div class="p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            {{-- ================= TOOLBAR: PENCARIAN + TAMBAH ================= --}}
            <div class="flex flex-wrap items-center gap-2">
                <form method="GET" action="{{ route('kegiatan.index') }}" class="flex flex-wrap items-center gap-2">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari kode atau nama kegiatan"
                        class="w-64 max-w-full text-sm rounded-lg border-slate-300 focus:ring-teal-500 focus:border-teal-500">

                    <button type="submit"
                        class="px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-semibold transition">
                        Cari
                    </button>

                    @if (request()->filled('q'))
                        <a href="{{ route('kegiatan.index') }}"
                            class="px-3 py-2 rounded-lg text-slate-500 hover:text-slate-700 text-sm font-medium transition">
                            Reset
                        </a>
                    @endif
                </form>

                <button type="button" @click="showAddModal = true"
                    class="ms-auto px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold transition whitespace-nowrap">
                    + Tambah Kegiatan
                </button>
            </div>

            {{-- ================= TABEL ================= --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                            <tr>
                                <th class="px-4 py-3 text-center font-semibold w-12">No</th>
                                <th class="px-4 py-3 text-left font-semibold">Kode</th>
                                <th class="px-4 py-3 text-left font-semibold">Nama Kegiatan</th>
                                <th class="px-4 py-3 text-left font-semibold">Periode</th>
                                <th class="px-4 py-3 text-center font-semibold">Bangunan</th>
                                <th class="px-4 py-3 text-center font-semibold">Import</th>
                                <th class="px-4 py-3 text-center font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($kegiatan as $k)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="px-4 py-3 text-center text-slate-500">
                                        {{ $kegiatan->firstItem() + $loop->index }}
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap font-medium text-slate-700">{{ $k->kode_kegiatan }}</td>
                                    <td class="px-4 py-3">
                                        <button type="button" @click="detail = @js($k); showDetailModal = true"
                                            class="text-slate-800 hover:text-teal-700 font-medium text-left">
                                            {{ $k->nama_kegiatan }}
                                        </button>
                                    </td>
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-600">{{ $k->periode ?? '-' }}</td>
                                    <td class="px-4 py-3 text-center text-slate-600">{{ number_format($k->bangunan_count) }}</td>
                                    <td class="px-4 py-3 text-center text-slate-600">{{ number_format($k->import_batches_count) }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-1">
                                            {{-- Detail --}}
                                            <button type="button" @click="detail = @js($k); showDetailModal = true"
                                                title="Detail" aria-label="Detail"
                                                class="p-1.5 rounded-lg text-teal-600 hover:bg-teal-50 transition">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </button>

                                            {{-- Edit --}}
                                            <button type="button" @click="openEdit(@js($k))"
                                                title="Edit" aria-label="Edit"
                                                class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>

                                            {{-- Hapus --}}
                                            <form method="POST" action="{{ route('kegiatan.destroy', $k) }}"
                                                onsubmit="return confirm('Hapus kegiatan {{ e($k->nama_kegiatan) }}? Semua data bangunan yang terkait akan ikut terhapus.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" title="Hapus" aria-label="Hapus"
                                                    class="p-1.5 rounded-lg text-red-500 hover:bg-red-50 hover:text-red-600 transition">
                                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-8 text-center text-slate-400">
                                        Belum ada kegiatan. Klik "Tambah Kegiatan" untuk membuat yang pertama.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-3 border-t border-slate-100">
                    {{ $kegiatan->links() }}
                </div>
            </div>
        </div>

        @include('kegiatan.create')
        @include('kegiatan.edit')
        @include('kegiatan.show')
    </div>
</x-app-layout>