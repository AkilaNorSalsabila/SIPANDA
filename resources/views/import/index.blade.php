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
                        sebelum import titik lokasi.
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
                        SLS yang sudah ada lewat kode SLS-nya.
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
                                <th class="px-4 py-2 text-left">Diupload oleh</th>
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
                                    <td class="px-4 py-3 text-slate-600">{{ $batch->uploader->name ?? '-' }}</td>
                                    <td class="px-4 py-3">
                                        @if ($batch->data_error > 0)
                                            <details class="text-xs">
                                                <summary class="cursor-pointer text-teal-600 font-semibold">Lihat detail</summary>
                                                <ul class="mt-2 space-y-1 max-h-48 overflow-y-auto pr-2">
                                                    @foreach ($batch->error_log as $err)
                                                        <li class="text-slate-500">
                                                            Baris {{ $err['baris'] }}: {{ $err['pesan'] }}
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </details>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-4 py-6 text-center text-slate-400">
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

        </div>
    </div>
</x-app-layout>
