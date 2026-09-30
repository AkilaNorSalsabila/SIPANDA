{{-- ================= MODAL: EDIT ================= --}}
<div x-show="showEditModal" x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4">

    {{-- Overlay --}}
    <div @click="showEditModal = false" class="absolute inset-0 bg-black/50"></div>


    {{-- Modal 800 x 600 --}}
    <div @click.outside="showEditModal = false"
        x-show="showEditModal"
        x-transition
        class="relative bg-white rounded-xl shadow-xl
               w-[800px] h-[600px]
               max-w-[calc(100vw-2rem)]
               max-h-[calc(100vh-2rem)]
               overflow-hidden flex flex-col">


        {{-- ================= HEADER ================= --}}
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100 shrink-0">
            <h3 class="font-semibold text-slate-800 text-sm">Edit Kegiatan</h3>

            <button type="button" @click="showEditModal = false" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>


        {{-- ================= FORM ================= --}}
        <form method="POST"
            :action="'{{ url('kegiatan') }}/' + editing.id"
            class="flex flex-col flex-1 min-h-0">

            @csrf
            @method('PUT')
            <input type="hidden" name="_form" value="edit">
            <input type="hidden" name="_kegiatan_id" :value="editing.id">
            <input type="hidden" name="_kode_kegiatan" :value="editing.kode_kegiatan">


            {{-- ================= BODY ================= --}}
            <div class="px-5 py-4 space-y-4 overflow-y-auto">

                @if (old('_form') === 'edit' && ($errors->any() || session('error')))
                    <div class="p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-lg space-y-1">
                        <p class="font-semibold">Perubahan belum tersimpan:</p>
                        @if ($errors->any())
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $pesan)
                                    <li>{{ $pesan }}</li>
                                @endforeach
                            </ul>
                        @else
                            <p>{{ session('error') }}</p>
                        @endif
                    </div>
                @endif


                {{-- Kode Kegiatan --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">Kode Kegiatan</label>

                    <input type="text" readonly tabindex="-1"
                        :value="editing.kode_kegiatan"
                        class="w-full text-xs rounded-lg border-slate-300 bg-slate-100 text-slate-600 cursor-not-allowed focus:ring-0 focus:border-slate-300">

                    <p class="mt-1 text-[10px] text-slate-400">Kode tidak dapat diubah.</p>
                </div>


                {{-- Nama Kegiatan --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-1">
                        Nama Kegiatan <span class="text-red-500">*</span>
                    </label>

                    <input type="text" name="nama_kegiatan" required
                        :value="editing.nama_kegiatan"
                        placeholder="Contoh: Sensus Ekonomi 2026"
                        class="w-full text-xs rounded-lg border-slate-300 focus:ring-teal-500 focus:border-teal-500">
                </div>


                {{-- ================= PERIODE ================= --}}
                @php
                    $tahunOptions = range(now()->year - 1, now()->year + 4);
                @endphp

                <div>
                    <label class="block text-xs font-semibold text-slate-700 mb-2">
                        Periode Kegiatan <span class="text-red-500">*</span>
                    </label>

                    <div class="flex items-end gap-3">

                        {{-- Mulai --}}
                        <div class="flex-1">
                            <p class="text-[10px] font-medium text-slate-500 mb-1">Mulai</p>

                            <div class="grid grid-cols-2 gap-2">
                                <select name="bulan_mulai" required x-model.number="editing.bulan_mulai"
                                    class="w-full text-xs rounded-lg border-slate-300 focus:ring-teal-500 focus:border-teal-500">
                                    @foreach ($bulanList as $val => $label)
                                        <option value="{{ $val }}">{{ $label }}</option>
                                    @endforeach
                                </select>

                                <select name="tahun_mulai" required x-model.number="editing.tahun_mulai"
                                    class="w-full text-xs rounded-lg border-slate-300 focus:ring-teal-500 focus:border-teal-500">
                                    @foreach ($tahunOptions as $th)
                                        <option value="{{ $th }}">{{ $th }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>


                        {{-- Separator --}}
                        <div class="pb-2 text-slate-400 text-[10px] font-medium">sampai</div>


                        {{-- Selesai --}}
                        <div class="flex-1">
                            <p class="text-[10px] font-medium text-slate-500 mb-1">Selesai</p>

                            <div class="grid grid-cols-2 gap-2">
                                <select name="bulan_selesai" x-model="editing.bulan_selesai"
                                    class="w-full text-xs rounded-lg border-slate-300 focus:ring-teal-500 focus:border-teal-500">
                                    <option value="">-</option>
                                    @foreach ($bulanList as $val => $label)
                                        <option value="{{ $val }}">{{ $label }}</option>
                                    @endforeach
                                </select>

                                <select name="tahun_selesai" x-model="editing.tahun_selesai"
                                    class="w-full text-xs rounded-lg border-slate-300 focus:ring-teal-500 focus:border-teal-500">
                                    <option value="">-</option>
                                    @foreach ($tahunOptions as $th)
                                        <option value="{{ $th }}">{{ $th }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                    </div>

                    <p class="mt-2 text-[10px] text-slate-400">
                        Kosongkan "Selesai" jika kegiatan hanya berlangsung satu bulan.
                    </p>
                </div>

            </div>


            {{-- ================= FOOTER ================= --}}
            <div class="flex items-center justify-end gap-2 px-5 py-4 border-t border-slate-100 shrink-0">
                <button type="button" @click="showEditModal = false"
                    class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-semibold transition">
                    Batal
                </button>

                <button type="submit"
                    class="px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold transition">
                    Simpan Perubahan
                </button>
            </div>

        </form>
    </div>
</div>