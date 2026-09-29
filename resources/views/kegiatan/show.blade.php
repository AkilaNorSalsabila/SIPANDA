{{-- ================= MODAL: DETAIL ================= --}}
<div x-show="showDetailModal" x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div @click="showDetailModal = false" class="absolute inset-0 bg-black/50"></div>

    <div @click.outside="showDetailModal = false"
        x-show="showDetailModal"
        x-transition
        class="relative bg-white rounded-xl shadow-xl w-full max-w-md p-6 max-h-[85vh] overflow-y-auto">

        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="text-xs text-slate-500" x-text="detail.kode_kegiatan"></p>
                <h3 class="font-semibold text-slate-800" x-text="detail.nama_kegiatan"></h3>
            </div>
            <button type="button" @click="showDetailModal = false" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <dl class="space-y-3 text-sm">
            <div>
                <dt class="text-xs font-semibold text-slate-500">Periode</dt>
                <dd class="text-slate-800" x-text="detail.periode || '-'"></dd>
            </div>
            <div>
                <dt class="text-xs font-semibold text-slate-500">Deskripsi</dt>
                <dd class="text-slate-800 whitespace-pre-line" x-text="detail.deskripsi || '-'"></dd>
            </div>
            <div class="grid grid-cols-2 gap-3 pt-1">
                <div class="bg-slate-50 rounded-lg p-3">
                    <dt class="text-xs font-semibold text-slate-500">Bangunan</dt>
                    <dd class="text-lg font-semibold text-slate-800" x-text="detail.bangunan_count"></dd>
                </div>
                <div class="bg-slate-50 rounded-lg p-3">
                    <dt class="text-xs font-semibold text-slate-500">Riwayat Import</dt>
                    <dd class="text-lg font-semibold text-slate-800" x-text="detail.import_batches_count"></dd>
                </div>
            </div>
        </dl>

        <div class="flex items-center gap-2 pt-5">
            <button type="button"
                @click="editing = detail; showDetailModal = false; showEditModal = true"
                class="px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold transition">
                Edit kegiatan ini
            </button>
            <button type="button" @click="showDetailModal = false"
                class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-600 text-sm font-semibold transition">
                Tutup
            </button>
        </div>
    </div>
</div>