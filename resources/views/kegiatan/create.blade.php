
{{-- ================= MODAL: TAMBAH ================= --}}
<div x-show="showAddModal" x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4">

    {{-- Overlay --}}
    <div @click="showAddModal = false"
        class="absolute inset-0 bg-black/50"></div>

    {{-- Modal 800 x 600 --}}
    <div @click.outside="showAddModal = false"
        x-show="showAddModal"
        x-transition
        class="relative bg-white rounded-xl shadow-xl
               w-[800px] h-[600px]
               max-w-[calc(100vw-2rem)]
               max-h-[calc(100vh-2rem)]
               overflow-hidden flex flex-col">

        {{-- Header --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 shrink-0">

            <h3 class="font-semibold text-slate-800 text-sm">
                Tambah Kegiatan Baru
            </h3>

            <button type="button"
                @click="showAddModal = false"
                class="text-slate-400 hover:text-slate-600">

                <svg class="w-5 h-5"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="2">

                    <path stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

        </div>


        {{-- Form --}}
        <form method="POST"
            action="{{ route('kegiatan.store') }}"
            class="flex flex-col flex-1 min-h-0">

            @csrf

            <input type="hidden"
                name="_form"
                value="tambah">


            {{-- Body --}}
            <div class="px-5 py-4 space-y-4 overflow-y-auto">

                {{-- Error --}}
                @if ($errors->any() && old('_form') === 'tambah')
                    <div class="p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-lg">
                        Data belum bisa disimpan. Periksa kembali isian yang ditandai merah.
                    </div>
                @endif


                {{-- Kode --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Kode Kegiatan
                    </label>

                    <input type="text"
                        readonly
                        tabindex="-1"
                        value="{{ $kodeOtomatis }}"
                        class="w-full text-xs rounded-lg border-slate-300 bg-slate-100 text-slate-600 cursor-not-allowed focus:ring-0 focus:border-slate-300">

                    <p class="mt-1 text-[10px] text-emerald-600">
                        ✓ Kode dibuat otomatis saat disimpan.
                    </p>
                </div>


                {{-- Nama --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Nama Kegiatan
                        <span class="text-red-500">*</span>
                    </label>

                    <input type="text"
                        name="nama_kegiatan"
                        required
                        value="{{ old('nama_kegiatan') }}"
                        placeholder="Contoh: Sensus Ekonomi 2026"
                        class="w-full text-xs rounded-lg border-slate-300 focus:ring-teal-500 focus:border-teal-500">

                    @error('nama_kegiatan')
                        <p class="mt-1 text-[10px] text-red-600">
                            {{ $message }}
                        </p>
                    @enderror
                </div>



                    {{-- Periode --}}
                    @php
                        $tahunOptions = range(now()->year - 1, now()->year + 4);
                    @endphp

                    <div>

                        <label class="block text-xs font-semibold text-slate-700 mb-2">
                            Periode Kegiatan
                            <span class="text-red-500">*</span>
                        </label>

                        {{-- Periode dibuat menyamping --}}
                        <div class="flex items-end gap-3">

                            {{-- Mulai --}}
                            <div class="flex-1">

                                <p class="text-[10px] font-medium text-slate-500 mb-1">
                                    Mulai
                                </p>

                                <div class="grid grid-cols-2 gap-2">

                                    <select name="bulan_mulai"
                                        required
                                        class="w-full text-xs rounded-lg border-slate-300 focus:ring-teal-500 focus:border-teal-500">

                                        @foreach ($bulanList as $val => $label)
                                            <option value="{{ $val }}"
                                                @selected((int) old('bulan_mulai', now()->month) === $val)>
                                                {{ $label }}
                                            </option>
                                        @endforeach

                                    </select>

                                    <select name="tahun_mulai"
                                        required
                                        class="w-full text-xs rounded-lg border-slate-300 focus:ring-teal-500 focus:border-teal-500">

                                        @foreach ($tahunOptions as $th)
                                            <option value="{{ $th }}"
                                                @selected((int) old('tahun_mulai', now()->year) === $th)>
                                                {{ $th }}
                                            </option>
                                        @endforeach

                                    </select>

                                </div>

                            </div>


                            {{-- Separator --}}
                            <!-- <div class="pb-2 text-slate-400 text-xs font-medium">
                                sampai
                            </div> -->


                            {{-- Selesai --}}
                            <div class="flex-1">

                                <p class="text-[10px] font-medium text-slate-500 mb-1">
                                    Selesai
                                </p>

                                <div class="grid grid-cols-2 gap-2">

                                    <select name="bulan_selesai"
                                        class="w-full text-xs rounded-lg border-slate-300 focus:ring-teal-500 focus:border-teal-500">

                                        <option value="">
                                            -
                                        </option>

                                        @foreach ($bulanList as $val => $label)
                                            <option value="{{ $val }}"
                                                @selected((int) old('bulan_selesai') === $val)>
                                                {{ $label }}
                                            </option>
                                        @endforeach

                                    </select>


                                    <select name="tahun_selesai"
                                        class="w-full text-xs rounded-lg border-slate-300 focus:ring-teal-500 focus:border-teal-500">

                                        <option value="">
                                            -
                                        </option>

                                        @foreach ($tahunOptions as $th)
                                            <option value="{{ $th }}"
                                                @selected((int) old('tahun_selesai') === $th)>
                                                {{ $th }}
                                            </option>
                                        @endforeach

                                    </select>

                                </div>

                            </div>

                        </div>


                        <p class="mt-2 text-[10px] text-slate-400">
                            Kosongkan "Selesai" jika kegiatan hanya berlangsung satu bulan.
                        </p>


                        {{-- Error --}}
                        @error('bulan_mulai')
                            <p class="mt-1 text-[10px] text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                        @error('tahun_mulai')
                            <p class="mt-1 text-[10px] text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                        @error('bulan_selesai')
                            <p class="mt-1 text-[10px] text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                        @error('tahun_selesai')
                            <p class="mt-1 text-[10px] text-red-600">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


            </div>


            {{-- Footer --}}
            <div class="flex items-center justify-end gap-2
                        px-5 py-4
                        border-t border-slate-100
                        shrink-0">

                <button type="button"
                    @click="showAddModal = false"
                    class="px-4 py-2 rounded-lg
                           bg-slate-100 hover:bg-slate-200
                           text-slate-600 text-xs
                           font-semibold transition">

                    Batal

                </button>


                <button type="submit"
                    class="px-4 py-2 rounded-lg
                           bg-teal-600 hover:bg-teal-700
                           text-white text-xs
                           font-semibold transition">

                    Simpan Kegiatan

                </button>

            </div>

        </form>

    </div>
</div>

