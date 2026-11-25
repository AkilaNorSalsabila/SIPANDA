<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">Kecamatan</h2>
    </x-slot>

    <div class="py-8" x-data="{
            showAddModal: {{ old('_form') === 'tambah' && $errors->any() ? 'true' : 'false' }},
            showEditModal: {{ old('_form') === 'edit' && $errors->any() ? 'true' : 'false' }},
            editing: @js(old('_form') === 'edit'
                ? ['id' => old('_id'), 'id_kecamatan' => old('_kode'), 'nama_kecamatan' => old('nama_kecamatan')]
                : new stdClass),
            openEdit(k) { this.editing = { ...k }; this.showEditModal = true; },
        }">
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

            {{-- ================= TOOLBAR ================= --}}
            <div class="flex flex-wrap items-center gap-2">
                <form method="GET" action="{{ route('kecamatan.index') }}" class="flex flex-wrap items-center gap-2">
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari kode atau nama kecamatan"
                        class="w-64 max-w-full text-sm rounded-lg border-slate-300 focus:ring-teal-500 focus:border-teal-500">
                    <button type="submit"
                        class="px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-semibold transition">Cari</button>
                    @if (request()->filled('q'))
                        <a href="{{ route('kecamatan.index') }}"
                            class="px-3 py-2 rounded-lg text-slate-500 hover:text-slate-700 text-sm font-medium transition">Reset</a>
                    @endif
                </form>

                <button type="button" @click="showAddModal = true"
                    class="ms-auto px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold transition whitespace-nowrap">
                    + Tambah Kecamatan
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
                                <th class="px-4 py-3 text-left font-semibold">Nama Kecamatan</th>
                                <th class="px-4 py-3 text-center font-semibold">Kelurahan</th>
                                <th class="px-4 py-3 text-center font-semibold">SLS</th>
                                <th class="px-4 py-3 text-center font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($kecamatan as $k)
                                <tr class="hover:bg-slate-50/70 transition">
                                    <td class="px-4 py-3 text-center text-slate-500">{{ $kecamatan->firstItem() + $loop->index }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap font-medium text-slate-700">{{ $k->kode_lengkap }}</td>
                                    <td class="px-4 py-3 font-medium text-slate-800">{{ $k->nama_kecamatan }}</td>
                                    <td class="px-4 py-3 text-center">
                                        <a href="{{ route('kelurahan.index', ['kecamatan_id' => $k->id]) }}"
                                            class="text-teal-700 hover:underline">{{ number_format($k->kelurahan_count) }}</a>
                                    </td>
                                    <td class="px-4 py-3 text-center text-slate-600">{{ number_format($k->sls_count) }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        <div class="flex items-center justify-center gap-1">
                                            <button type="button" title="Edit" aria-label="Edit"
                                                @click="openEdit(@js(['id' => $k->id, 'id_kecamatan' => $k->kode_lengkap, 'nama_kecamatan' => $k->nama_kecamatan]))"
                                                class="p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 hover:text-slate-700 transition">
                                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>

                                            <form method="POST" action="{{ route('kecamatan.destroy', $k) }}"
                                                onsubmit="return confirm('Hapus kecamatan {{ e($k->nama_kecamatan) }}?')">
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
                                    <td colspan="6" class="px-4 py-8 text-center text-slate-400">
                                        Belum ada kecamatan. Import batas SLS atau klik "Tambah Kecamatan".
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($kecamatan->hasPages())
                    <div class="px-4 py-3 border-t border-slate-100">{{ $kecamatan->links() }}</div>
                @endif
            </div>
        </div>

        {{-- ================= MODAL: TAMBAH ================= --}}
        <div x-show="showAddModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="showAddModal = false" class="absolute inset-0 bg-black/50"></div>
            <div x-show="showAddModal" x-transition class="relative bg-white rounded-xl shadow-xl w-[480px] max-w-[calc(100vw-2rem)]">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                    <h3 class="font-semibold text-slate-800 text-sm">Tambah Kecamatan</h3>
                    <button type="button" @click="showAddModal = false" class="text-slate-400 hover:text-slate-600" aria-label="Tutup">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('kecamatan.store') }}">
                    @csrf
                    <input type="hidden" name="_form" value="tambah">

                    <div class="px-5 py-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Kecamatan <span class="text-red-500">*</span></label>
                            <div class="flex items-center gap-2">
                                <span class="px-3 py-2 text-xs rounded-lg bg-slate-100 text-slate-600">{{ $idProvinsi }}{{ $idKabupaten }}</span>
                                <input type="text" name="id_kecamatan" required maxlength="3" inputmode="numeric" placeholder="010"
                                    value="{{ old('_form') === 'tambah' ? old('id_kecamatan') : '' }}"
                                    class="w-28 text-xs rounded-lg border-slate-300 focus:ring-teal-500 focus:border-teal-500">
                            </div>
                            <p class="mt-1 text-[10px] text-slate-400">3 digit kode kecamatan menurut BPS. Awalan {{ $idProvinsi }}{{ $idKabupaten }} = Kota Mojokerto.</p>
                            @if (old('_form') === 'tambah') @error('id_kecamatan')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror @endif
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Kecamatan <span class="text-red-500">*</span></label>
                            <input type="text" name="nama_kecamatan" required
                                value="{{ old('_form') === 'tambah' ? old('nama_kecamatan') : '' }}"
                                placeholder="Contoh: PRAJURITKULON"
                                class="w-full text-xs rounded-lg border-slate-300 focus:ring-teal-500 focus:border-teal-500">
                            @if (old('_form') === 'tambah') @error('nama_kecamatan')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror @endif
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-slate-100">
                        <button type="button" @click="showAddModal = false"
                            class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition">Batal</button>
                        <button type="submit"
                            class="px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold transition">Simpan</button>
                    </div>
                </form>
            </div>
        </div>

        {{-- ================= MODAL: EDIT (hanya nama) ================= --}}
        <div x-show="showEditModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="showEditModal = false" class="absolute inset-0 bg-black/50"></div>
            <div x-show="showEditModal" x-transition class="relative bg-white rounded-xl shadow-xl w-[480px] max-w-[calc(100vw-2rem)]">
                <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
                    <h3 class="font-semibold text-slate-800 text-sm">Edit Kecamatan</h3>
                    <button type="button" @click="showEditModal = false" class="text-slate-400 hover:text-slate-600" aria-label="Tutup">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form method="POST" :action="'{{ url('kecamatan') }}/' + editing.id">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="_form" value="edit">
                    <input type="hidden" name="_id" :value="editing.id">
                    <input type="hidden" name="_kode" :value="editing.id_kecamatan">

                    <div class="px-5 py-4 space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Kecamatan</label>
                            <input type="text" readonly tabindex="-1" :value="editing.id_kecamatan"
                                class="w-full text-xs rounded-lg border-slate-300 bg-slate-100 text-slate-600 cursor-not-allowed focus:ring-0 focus:border-slate-300">
                            <p class="mt-1 text-[10px] text-slate-400">Kode tidak bisa diubah karena menjadi bagian dari kode SLS.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Kecamatan <span class="text-red-500">*</span></label>
                            <input type="text" name="nama_kecamatan" required x-model="editing.nama_kecamatan"
                                class="w-full text-xs rounded-lg border-slate-300 focus:ring-teal-500 focus:border-teal-500">
                            @if (old('_form') === 'edit') @error('nama_kecamatan')<p class="mt-1 text-[10px] text-red-600">{{ $message }}</p>@enderror @endif
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-slate-100">
                        <button type="button" @click="showEditModal = false"
                            class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition">Batal</button>
                        <button type="submit"
                            class="px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold transition">Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
