<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">Import Data</h2>
    </x-slot>

    <div class="py-8">
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

            @if ($errors->has('file'))
                <div class="p-3 bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg">
                    {{ $errors->first('file') }}
                </div>
            @endif

            @if (empty($kegiatanList) || $kegiatanList->isEmpty())
                <div class="p-4 bg-amber-50 border border-amber-200 text-amber-700 text-sm rounded-lg">
                    Belum ada data <b>Kegiatan</b>. Buat minimal satu kegiatan dulu
                    (lewat halaman Kegiatan, atau sementara lewat <code>php artisan tinker</code>)
                    sebelum bisa import data.
                </div>
            @endif

            {{-- ================= FORM IMPORT ================= --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                {{-- Import Batas SLS --}}
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-2 h-2 rounded-full bg-teal-600"></span>
                        <h3 class="font-semibold text-slate-800">1. Import Batas SLS</h3>
                    </div>
                    <p class="text-xs text-slate-500 mb-4">
                        Upload <code>batas_sls.geojson</code>. Lakukan ini <b>terlebih dahulu</b>,
                        sebelum import titik lokasi. Batas SLS tersimpan <b>per kegiatan</b>, jadi tidak
                        mengubah batas kegiatan lain.
                        Kalau batasnya sama dengan kegiatan lain, pakai <b>Salin SLS</b> di bawah.
                    </p>

                    <form method="POST" action="{{ route('import.batas-sls') }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kegiatan</label>
                            <select name="kegiatan_id" required
                                class="w-full text-sm rounded-lg border-slate-300 focus:ring-teal-500 focus:border-teal-500">
                                <option value="">-- pilih kegiatan --</option>
                                @foreach ($kegiatanList as $k)
                                    <option value="{{ $k->id }}">{{ $k->nama_kegiatan }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">File (.geojson)</label>
                            <input type="file" name="file" accept=".json,.geojson" required
                                class="w-full text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg
                                       file:border-0 file:bg-teal-50 file:text-teal-700 file:text-xs file:font-semibold">
                        </div>

                        <button type="submit"
                            class="w-full py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold transition">
                            Upload &amp; Import Batas SLS
                        </button>
                    </form>
                </div>

                {{-- Import Titik Lokasi --}}
                <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-2 h-2 rounded-full bg-emerald-600"></span>
                        <h3 class="font-semibold text-slate-800">2. Import Titik Lokasi</h3>
                    </div>
                    <p class="text-xs text-slate-500 mb-4">
                        Upload <code>titik_lokasi.geojson</code>. Tiap titik dicocokkan ke
                        SLS <b>kegiatan yang sama</b> lewat kode SLS-nya.
                    </p>

                    <form method="POST" action="{{ route('import.titik-lokasi') }}" enctype="multipart/form-data" class="space-y-3">
                        @csrf

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kegiatan</label>
                            <select name="kegiatan_id" required
                                class="w-full text-sm rounded-lg border-slate-300 focus:ring-emerald-500 focus:border-emerald-500">
                                <option value="">-- pilih kegiatan --</option>
                                @foreach ($kegiatanList as $k)
                                    <option value="{{ $k->id }}">{{ $k->nama_kegiatan }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">File (.geojson)</label>
                            <input type="file" name="file" accept=".json,.geojson" required
                                class="w-full text-sm text-slate-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg
                                       file:border-0 file:bg-emerald-50 file:text-emerald-700 file:text-xs file:font-semibold">
                        </div>

                       

                        <button type="submit"
                            class="w-full py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold transition">
                            Upload &amp; Import Titik Lokasi
                        </button>
                    </form>
                </div>
            </div>

            {{-- ================= SALIN SLS ANTAR KEGIATAN ================= --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
                <div class="flex items-center gap-2 mb-1">
                    <span class="w-2 h-2 rounded-full bg-sky-600"></span>
                    <h3 class="font-semibold text-slate-800">Salin SLS dari kegiatan lain</h3>
                </div>
                <p class="text-xs text-slate-500 mb-4">
                    Untuk kegiatan baru yang memakai batas SLS yang sama dengan kegiatan sebelumnya. Seluruh SLS beserta
                    polygonnya disalin menjadi milik kegiatan tujuan, sehingga perubahan batas di satu kegiatan tidak
                    memengaruhi kegiatan lain. SLS yang sudah ada di kegiatan tujuan tidak ditimpa, kecuali polygon
                    yang masih kosong (atau semua polygon kalau &quot;timpa polygon&quot; dicentang). Penugasan dan titik tidak berubah.
                </p>

                <form method="POST" action="{{ route('import.salin-sls') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3 items-end"
                      onsubmit="return confirm(this.timpa_polygon.checked ? 'Salin SLS dan GANTI polygon SLS yang sudah ada di kegiatan tujuan dengan polygon kegiatan asal?' : 'Salin SLS dari kegiatan asal ke kegiatan tujuan?')">
                    @csrf

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Salin dari kegiatan</label>
                        <select name="dari_kegiatan_id" required
                            class="w-full text-sm rounded-lg border-slate-300 focus:ring-teal-500 focus:border-teal-500">
                            <option value="">-- kegiatan asal --</option>
                            @foreach ($kegiatanList as $k)
                                <option value="{{ $k->id }}">{{ $k->nama_kegiatan }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Ke kegiatan</label>
                        <select name="ke_kegiatan_id" required
                            class="w-full text-sm rounded-lg border-slate-300 focus:ring-teal-500 focus:border-teal-500">
                            <option value="">-- kegiatan tujuan --</option>
                            @foreach ($kegiatanList as $k)
                                <option value="{{ $k->id }}">{{ $k->nama_kegiatan }}</option>
                            @endforeach
                        </select>
                    </div>

                    <label class="sm:col-span-3 flex items-start gap-2 text-xs text-slate-600">
                        <input type="checkbox" name="timpa_polygon" value="1"
                            class="mt-0.5 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                        <span><b>Timpa polygon</b> SLS yang sudah ada di kegiatan tujuan (polygon lama disimpan sebagai versi sebelumnya).</span>
                    </label>

                    <button type="submit"
                        class="py-2 rounded-lg bg-sky-600 hover:bg-sky-700 text-white text-sm font-semibold transition">
                        Salin SLS
                    </button>
                </form>
                @error('ke_kegiatan_id') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('dari_kegiatan_id') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>

            {{-- ================= RIWAYAT IMPORT ================= --}}
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100">
                    <h3 class="font-semibold text-slate-800">Riwayat Import</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                            <tr>
                                <th class="px-4 py-2 text-left">Tanggal</th>
                                <th class="px-4 py-2 text-left">Kegiatan</th>
                                <th class="px-4 py-2 text-left">Jenis</th>
                                <th class="px-4 py-2 text-left">File</th>
                                <th class="px-4 py-2 text-center">Total</th>
                                <th class="px-4 py-2 text-center">Valid</th>
                                <th class="px-4 py-2 text-center">Bermasalah</th>
                                <th class="px-4 py-2 text-center">Dilewati</th>
                                <th class="px-4 py-2 text-left">Diupload oleh</th>
                                <th class="px-4 py-2"></th>
                                <th class="px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($riwayat as $batch)
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-slate-600">
                                        {{ $batch->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-4 py-3">{{ $batch->kegiatan->nama_kegiatan ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                            {{ $batch->jenis_data === 'batas_sls' ? 'bg-teal-50 text-teal-700' : 'bg-emerald-50 text-emerald-700' }}">
                                            {{ $batch->jenis_data === 'batas_sls' ? 'Batas SLS' : 'Titik Lokasi' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">{{ $batch->nama_file }}</td>
                                    <td class="px-4 py-3 text-center">{{ $batch->total_data }}</td>
                                    <td class="px-4 py-3 text-center text-emerald-600 font-semibold">{{ $batch->data_valid }}</td>
                                    <td class="px-4 py-3 text-center {{ $batch->data_error > 0 ? 'text-red-600 font-semibold' : 'text-slate-400' }}">
                                        {{ $batch->data_error }}
                                    </td>
                                    <td class="px-4 py-3 text-center {{ $batch->data_dilewati > 0 ? 'text-amber-600 font-semibold' : 'text-slate-400' }}">
                                        {{ $batch->data_dilewati }}
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">
                                        {{ $batch->uploader->name ?? '-' }}
                                        @if ($batch->status === 'failed')
                                            <span class="ml-1 px-1.5 py-0.5 rounded bg-red-50 text-red-600 text-[10px] font-semibold uppercase">gagal</span>
                                        @elseif ($batch->status === 'processing')
                                            <span class="ml-1 px-1.5 py-0.5 rounded bg-amber-50 text-amber-600 text-[10px] font-semibold uppercase">diproses</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @if (! empty($batch->error_log))
                                            <details class="text-xs">
                                                <summary class="cursor-pointer text-teal-600 font-semibold">Lihat detail</summary>
                                                <ul class="mt-2 space-y-1 max-h-48 overflow-y-auto pr-2">
                                                    @foreach ($batch->error_log as $err)
                                                        <li class="{{ ($err['tingkat'] ?? 'galat') === 'info' ? 'text-sky-600' : 'text-slate-500' }}">
                                                            @if (is_numeric($err['baris']))Baris {{ $err['baris'] }}: @endif{{ $err['pesan'] }}
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </details>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        @php
                                            $n = $jejak[$batch->id] ?? 0;
                                            $pesan = $n === 0
                                                ? 'Hapus catatan import ini? Import ini tidak terlacak, jadi data hasil import tidak ikut dihapus.'
                                                : ($batch->jenis_data === 'batas_sls'
                                                    ? "Hapus import ini? Polygon {$n} SLS akan dikembalikan ke versi sebelumnya (kalau ada, mis. sebelum impor ulang), selain itu dikosongkan. SLS dan penugasannya tetap ada."
                                                    : "Hapus import ini? {$n} titik yang DIBUAT oleh import ini beserta usaha dan KK-nya akan dihapus. Titik yang sudah ada sebelumnya dan hanya diperbarui, titik hasil koreksi, dan titik hasil tagging petugas tidak ikut dihapus.");
                                        @endphp
                                        <form method="POST" action="{{ route('import.destroy', $batch) }}" class="inline"
                                              onsubmit="return confirm({{ Js::from($pesan) }})">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-lg text-red-500 hover:bg-red-50" title="Hapus import ini">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="px-4 py-6 text-center text-slate-400">
                                        Belum ada riwayat import.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-3 border-t border-slate-100">
                    {{ $riwayat->links() }}
                </div>
            </div>


            {{-- ===== Batas SLS yang sudah diimpor: hapus polygon per SLS ===== --}}
            <div class="bg-white rounded-2xl ring-1 ring-slate-200/70 shadow-sm overflow-hidden mt-6">
                <div class="px-4 py-3 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-slate-800">Batas SLS yang sudah diimpor</h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Salah impor? Hapus polygon SLS yang salah di sini satu per satu, lalu impor ulang yang benar.
                            SLS, penugasan, dan titiknya tidak ikut terhapus.
                            Total: <b>{{ number_format($slsBerpolygon->total()) }}</b> SLS berpolygon.
                        </p>
                    </div>
                    <form method="GET" class="flex items-center gap-2">
                        <input type="search" name="q_sls" value="{{ $cariSls }}" placeholder="Cari ID SLS / nama / kelurahan…"
                            class="text-sm rounded-lg border-slate-200 bg-white focus:ring-teal-500 focus:border-teal-500 w-64">
                        <button class="px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold">Cari</button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
                            <tr>
                                <th class="px-4 py-2 text-left">SLS</th>
                                <th class="px-4 py-2 text-left">Kegiatan</th>
                                <th class="px-4 py-2 text-left">Wilayah</th>
                                <th class="px-4 py-2"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($slsBerpolygon as $s)
                                <tr class="hover:bg-slate-50/60">
                                    <td class="px-4 py-3">
                                        <p class="font-semibold text-slate-800">{{ $s->id_sls }}</p>
                                        <p class="text-xs text-slate-400">{{ $s->nama_sls }}</p>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-slate-600">{{ $s->kegiatan->nama_kegiatan ?? '-' }}</td>
                                    <td class="px-4 py-3 text-xs text-slate-500">{{ $s->desa_kelurahan }}, {{ $s->kecamatan }}</td>
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <form method="POST" action="{{ route('import.polygon.destroy', $s) }}" class="inline"
                                              onsubmit="return confirm({{ Js::from('Hapus polygon SLS ' . $s->id_sls . '? SLS, penugasan, dan titiknya tetap ada, tapi tagging di SLS ini berhenti sampai polygon diimpor lagi.') }})">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-1.5 rounded-lg text-red-500 hover:bg-red-50" title="Hapus polygon SLS ini">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-6 text-center text-slate-400">Belum ada SLS yang punya polygon.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($slsBerpolygon->hasPages())
                    <div class="px-4 py-3 border-t border-slate-100">{{ $slsBerpolygon->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
