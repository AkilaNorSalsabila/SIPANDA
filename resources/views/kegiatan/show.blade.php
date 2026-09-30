{{-- ================= MODAL: DETAIL ================= --}}
<div x-show="showDetailModal" x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4">

    {{-- Overlay --}}
    <div @click="showDetailModal = false"
        class="absolute inset-0 bg-black/50"></div>


    {{-- Modal 800 x 600, sama seperti modal Edit --}}
    <div @click.outside="showDetailModal = false"
        x-show="showDetailModal"
        x-transition
        class="relative bg-white rounded-xl shadow-xl
               w-[800px] h-[600px]
               max-w-[calc(100vw-2rem)]
               max-h-[calc(100vh-2rem)]
               overflow-hidden flex flex-col">


        {{-- ================= HEADER ================= --}}
        <div class="flex items-center justify-between
                    px-5 py-4
                    border-b border-slate-100
                    shrink-0">

            <div>
                <p class="text-[10px] text-slate-500" x-text="detail.kode_kegiatan"></p>
                <h3 class="font-semibold text-slate-800 text-sm" x-text="detail.nama_kegiatan"></h3>
            </div>

            <button type="button"
                @click="showDetailModal = false"
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


        {{-- ================= BODY ================= --}}
        <div class="px-5 py-4 space-y-4 overflow-y-auto flex-1">

            <div class="grid grid-cols-2 gap-4">

                <div>
                    <p class="text-xs font-semibold text-slate-700 mb-1">Jenis Kegiatan</p>
                    <p class="text-xs text-slate-800" x-text="jenisLabel[detail.jenis] || detail.jenis"></p>
                </div>

                <div>
                    <p class="text-xs font-semibold text-slate-700 mb-1">Periode</p>
                    <p class="text-xs text-slate-800" x-text="detail.periode || '-'"></p>
                </div>

            </div>

            <div class="grid grid-cols-2 gap-4 pt-2">

                <div class="bg-slate-50 rounded-lg p-4">
                    <p class="text-xs font-semibold text-slate-500 mb-1">Bangunan</p>
                    <p class="text-lg font-semibold text-slate-800" x-text="detail.bangunan_count"></p>
                </div>

                <div class="bg-slate-50 rounded-lg p-4">
                    <p class="text-xs font-semibold text-slate-500 mb-1">Riwayat Import</p>
                    <p class="text-lg font-semibold text-slate-800" x-text="detail.import_batches_count"></p>
                </div>

            </div>

        </div>


        {{-- ================= FOOTER ================= --}}
        <div class="flex items-center justify-end gap-2
                    px-5 py-4
                    border-t border-slate-100
                    shrink-0">

            {{-- Tutup --}}
            <button type="button"
                @click="showDetailModal = false"
                class="px-4 py-2 rounded-lg
                       bg-slate-100
                       hover:bg-slate-200
                       text-slate-600
                       text-xs
                       font-semibold
                       transition">

                Tutup

            </button>

            {{-- Edit --}}
            <button type="button"
                @click="showDetailModal = false; openEdit(detail)"
                class="px-4 py-2 rounded-lg
                       bg-teal-600
                       hover:bg-teal-700
                       text-white
                       text-xs
                       font-semibold
                       transition">

                Edit Kegiatan Ini

            </button>

        </div>

    </div>
</div>