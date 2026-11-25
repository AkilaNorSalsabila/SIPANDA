<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">Peta</h2>
    </x-slot>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    {{-- Clustering titik (titik berdekatan digabung, bisa menyebar saat diklik) --}}
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

    <style>
        .marker-nomor {
            display: flex; align-items: center; justify-content: center;
            min-width: 26px; height: 26px; padding: 0 3px; border-radius: 13px;
            border: 2px solid #fff; box-shadow: 0 1px 4px rgba(0,0,0,.5);
            color: #fff; font-size: 11px; font-weight: 700; font-family: sans-serif;
            box-sizing: border-box;
        }

        /* Peta memenuhi sisa tinggi layar (desktop); di HP tingginya 75% layar */
        .wadah-peta-map { position: relative; min-height: 75vh; }
        @media (min-width: 1024px) { .wadah-peta-map { min-height: 0; } }
        #peta { position: absolute; inset: 0; }

        .leaflet-popup-content { margin: 12px 14px; }
        .pc-badges { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 8px; }
        .pc-badge {
            padding: 3px 10px; border-radius: 9999px; font-size: 10px;
            font-weight: 700; font-family: sans-serif; white-space: nowrap;
        }
        .pc-badge-usaha  { background: #ecfdf5; color: #059669; }
        .pc-badge-ok     { background: #eff6ff; color: #2563eb; }
        .pc-badge-warn   { background: #fee2e2; color: #dc2626; }
        .pc-badge-muted  { background: #f1f5f9; color: #475569; }
        .pc-badge-perlu  { background: #fee2e2; color: #b91c1c; }
        .pc-badge-sudah  { background: #fef3c7; color: #b45309; }
        .pc-badge-verif  { background: #dcfce7; color: #15803d; }
        .pc-koreksi-box  { margin: 8px 0; padding: 8px 10px; border-radius: 8px; background: #f8fafc; border: 1px solid #e2e8f0; font-size: 12px; color: #334155; line-height: 1.5; font-family: sans-serif; }
        .pc-koreksi-box b { color: #0f172a; }
        .pc-title { font-size: 14px; font-weight: 800; color: #0f172a; margin-bottom: 6px; font-family: sans-serif; }
        .pc-row { font-size: 12px; color: #334155; margin-bottom: 3px; font-family: sans-serif; }
        .pc-row b { color: #0f172a; }
        .pc-hr { margin: 8px 0; border: none; border-top: 1px solid #e2e8f0; }
        .pc-sub {
            margin-top: 6px; padding: 6px 8px; background: #f8fafc;
            border-left: 3px solid #0d9488; border-radius: 4px;
        }
        .pc-sub-title { font-size: 10px; font-weight: 800; color: #0d9488; text-transform: uppercase; margin-bottom: 3px; font-family: sans-serif; }
        .pc-warn-box {
            margin-top: 8px; padding: 8px 10px; background: #fef2f2;
            border: 1px solid #fecaca; border-radius: 8px; font-size: 11px;
            color: #7f1d1d; font-family: sans-serif;
        }
        .pc-warn-title { font-weight: 800; color: #b91c1c; margin-bottom: 5px; font-size: 11px; }
        .pc-warn-row { margin-bottom: 3px; }
        .pc-warn-row b { color: #7f1d1d; }
        .pc-reco { margin-top: 6px; font-style: italic; color: #9f1239; }
        .pc-info-box {
            margin-top: 8px; padding: 8px 10px; background: #f8fafc;
            border: 1px solid #e2e8f0; border-radius: 8px; font-size: 11px;
            color: #475569; font-family: sans-serif;
        }
        .pc-actions { margin-top: 10px; display: flex; gap: 8px; }
        .pc-btn {
            flex: 1; text-align: center; padding: 7px 0; border-radius: 6px; border: 0; cursor: pointer;
            font-size: 11px; font-weight: 700; text-decoration: none; font-family: sans-serif;
        }
        .pc-btn-primary   { background: #0d9488; color: #fff; }
        .pc-btn-secondary { background: #f1f5f9; color: #0f172a; }

        .st-title { font-size: 14px; font-weight: 800; color: #2563eb; text-transform: uppercase; line-height: 1.25; font-family: sans-serif; }
        .st-sub { font-size: 11px; color: #64748b; text-transform: uppercase; margin-top: 2px; font-family: sans-serif; }
        .st-id { font-size: 11px; color: #64748b; margin-bottom: 8px; font-family: sans-serif; }
        .st-row { display: flex; justify-content: space-between; font-size: 12px; font-weight: 600; margin-bottom: 4px; font-family: sans-serif; }
        .st-row span:last-child { font-weight: 800; }
        .c-hitam { color: #0f172a; } .c-teal { color: #14b8a6; } .c-biru { color: #3b82f6; } .c-ungu { color: #8b5cf6; }

        /* ===== Toggle QC ===== */
        .qc-switch { position: relative; width: 36px; height: 20px; flex-shrink: 0; }
        .qc-switch input { position: absolute; inset: 0; width: 100%; height: 100%; margin: 0; opacity: 0; cursor: pointer; z-index: 1; }
        .qc-switch span { position: absolute; inset: 0; background: #cbd5e1; border-radius: 9999px; transition: .15s; }
        .qc-switch span::after {
            content: ''; position: absolute; top: 2px; left: 2px; width: 16px; height: 16px;
            background: #fff; border-radius: 50%; transition: .15s;
        }
        .qc-switch input:checked + span { background: #0d9488; }
        .qc-switch input:checked + span::after { transform: translateX(16px); }
        .qc-switch input:disabled { cursor: not-allowed; }
        .qc-row-disabled { opacity: .45; }

        /* ===== Tagging ===== */
        .mode-tagging, .mode-tagging .leaflet-interactive { cursor: crosshair !important; }
        .btn-tugas {
            display: block; width: 100%; text-align: left; padding: 7px 10px; margin-top: 4px;
            border-radius: 8px; border: 1px solid #99f6e4; background: #fff; font-size: 12px; color: #134e4a;
        }
        .btn-tugas:hover { background: #f0fdfa; }
        .btn-tugas.aktif { background: #0d9488; border-color: #0d9488; color: #fff; }
        .btn-tugas.aktif small { color: #ccfbf1; }
        .btn-tugas small { display: block; font-size: 10px; color: #64748b; }
        .btn-tugas:disabled { opacity: .5; cursor: not-allowed; }
        #toastTag {
            position: fixed; left: 50%; bottom: 24px; transform: translateX(-50%); z-index: 3000;
            max-width: 90vw; padding: 10px 16px; border-radius: 10px; font-size: 13px; font-weight: 600;
            color: #fff; box-shadow: 0 6px 20px rgba(0,0,0,.25);
        }

        /* Panel pengambilan lokasi GPS (melayang di bawah layar) */
        #gpsBar {
            position: fixed; left: 50%; bottom: 20px; transform: translateX(-50%); z-index: 1900;
            width: min(92vw, 360px); background: #fff; border: 1px solid #99f6e4; border-radius: 14px;
            padding: 12px 14px; box-shadow: 0 8px 28px rgba(0,0,0,.28); font-size: 13px;
        }
        #gpsBar .gps-baris { display: flex; justify-content: space-between; align-items: baseline; }
        #gpsBar .gps-angka { font-size: 20px; font-weight: 700; }
        #gpsBar .gps-status { margin-top: 4px; font-size: 12px; color: #475569; line-height: 1.35; }
        #gpsBar .gps-aksi { margin-top: 10px; display: flex; gap: 8px; justify-content: flex-end; }
        #gpsBar .gps-btn { padding: 8px 14px; border-radius: 9px; font-size: 12px; font-weight: 600; }
        #gpsBar .gps-btn-abu { background: #f1f5f9; color: #334155; }
        #gpsBar .gps-btn-utama { background: #0d9488; color: #fff; }
        #gpsBar .gps-btn-utama:disabled { opacity: .45; cursor: not-allowed; }

        /* Cincin merah pada cluster yang berisi titik di luar batas SLS */
        .marker-cluster-qc > div { box-shadow: 0 0 0 4px rgba(239,68,68,.75); }
    </style>

    <div class="py-6">
        <div class="max-w-[1600px] mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div id="wadahPeta" class="grid grid-cols-1 lg:grid-cols-[280px_1fr]">

                    {{-- ================= SIDEBAR FILTER ================= --}}
                    <div class="border-b lg:border-b-0 lg:border-r border-slate-200 p-5 space-y-6 lg:overflow-y-auto">

                        {{-- ================= TAGGING ================= --}}
                        @php $halamanTagging = request()->query('mode') === 'tagging'; @endphp
                        <div id="panelTagging" class="rounded-xl border border-teal-200 bg-teal-50/60 p-3">
                            <p class="text-xs font-bold text-teal-800 mb-1">{{ $halamanTagging ? 'Tagging Titik' : 'Unduh Data' }}</p>

                            {{-- Bagian tagging hanya tampil di menu Tagging (?mode=tagging). Tetap ada di HTML (disembunyikan) supaya JS tidak error. --}}
                            <div id="blokTagging" class="{{ $halamanTagging ? '' : 'hidden' }}">
                            <div id="daftarPenugasan" class="text-[11px] text-slate-500">Memuat penugasan…</div>

                            <label class="flex items-center justify-between text-sm text-slate-700 mt-3">
                                <span class="font-semibold">Mode Tagging</span>
                                <span class="qc-switch"><input type="checkbox" id="modeTagging" disabled><span></span></span>
                            </label>
                            <p id="infoTagging" class="mt-1.5 text-[11px] text-slate-500"></p>

                            <button type="button" id="btnLokasiSaya" disabled
                                class="mt-2 w-full py-1.5 rounded-lg bg-white border border-teal-200 text-teal-700 hover:bg-teal-50 disabled:opacity-50 disabled:cursor-not-allowed text-xs font-semibold transition">
                                📍 Ambil Lokasi Saya (GPS)
                            </button>
                            </div>{{-- /#blokTagging --}}

                            <div class="{{ $halamanTagging ? 'mt-3 pt-3 border-t border-teal-200/70' : 'mt-2' }}">
                                <a id="btnExport" href="{{ route('tagging.export') }}"
                                   class="block w-full py-1.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold text-center transition">
                                    ⬇ Unduh GeoJSON (QGIS)
                                </a>
                                @if (auth()->user()->isAdmin())
                                    <label class="mt-2 block text-[11px] text-slate-600">
                                        Cakupan unduhan
                                        <select id="exportCakupan" class="mt-1 w-full text-xs rounded-lg border-slate-300 focus:ring-teal-500 focus:border-teal-500">
                                            <option value="tagging">Hanya titik hasil tagging</option>
                                            <option value="koreksi">Hanya titik yang dikoreksi (untuk memperbarui sumber data)</option>
                                            <option value="semua">Semua titik, termasuk hasil import</option>
                                        </select>
                                    </label>
                                @endif
                                <p class="mt-1.5 text-[10px] leading-snug text-slate-400">
                                    Field sama dengan file QGIS: id_assignment, idsls, no_bang, kode_bang_value, btt, bku, flag_repair, kk, usaha.
                                    Mengikuti kegiatan &amp; SLS yang sedang dipilih.
                                </p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Kegiatan</label>
                            <select id="filterKegiatan"
                                class="w-full text-sm rounded-lg border-slate-300 focus:ring-teal-500 focus:border-teal-500">
                                <option value="">-- pilih kegiatan --</option>
                                @foreach ($kegiatanList as $k)
                                    <option value="{{ $k->id }}">{{ $k->nama_kegiatan }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- ================= FILTER WILAYAH: KECAMATAN -> KELURAHAN ================= --}}
                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Kecamatan</label>
                                <select id="filterKecamatan" disabled
                                    class="w-full text-sm rounded-lg border-slate-300 focus:ring-teal-500 focus:border-teal-500 disabled:bg-slate-100 disabled:text-slate-400">
                                    <option value="">-- Semua Kecamatan --</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 mb-1">Kelurahan / Desa</label>
                                <select id="filterKelurahan" disabled
                                    class="w-full text-sm rounded-lg border-slate-300 focus:ring-teal-500 focus:border-teal-500 disabled:bg-slate-100 disabled:text-slate-400">
                                    <option value="">-- Semua Kelurahan --</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                Filter ID SLS
                                <span class="font-normal text-slate-400">(<span id="jumlahSls">0</span> SLS)</span>
                            </label>
                            <div class="relative">
                                <input id="filterSls" type="text" disabled autocomplete="off"
                                    placeholder="-- Semua SLS -- (ketik untuk cari)"
                                    class="w-full text-sm rounded-lg border-slate-300 focus:ring-teal-500 focus:border-teal-500 disabled:bg-slate-100 disabled:text-slate-400">
                                <ul id="slsList"
                                    class="hidden absolute left-0 right-0 top-full mt-1 z-[1500] max-h-64 overflow-y-auto bg-white border border-slate-200 rounded-lg shadow-lg text-xs"></ul>
                            </div>
                            <p id="infoSls" class="mt-1 text-[11px] text-slate-400">Pilih kegiatan dulu untuk mengaktifkan filter ini.</p>
                            <button id="resetSls" type="button"
                                class="mt-2 w-full py-1.5 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">
                                Reset Filter
                            </button>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-slate-700 mb-2">Tampilan Peta</p>
                            <label class="flex items-center gap-2 text-sm text-slate-600 mb-1.5">
                                <input type="radio" name="basemap" value="satelit" checked
                                    class="border-slate-300 text-teal-600 focus:ring-teal-500">
                                Citra Satelit
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-600">
                                <input type="radio" name="basemap" value="jalan"
                                    class="border-slate-300 text-teal-600 focus:ring-teal-500">
                                Peta Jalan
                            </label>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-slate-700 mb-2">Layer</p>
                            <label class="flex items-center gap-2 text-sm text-slate-600 mb-1.5">
                                <input type="checkbox" id="layerSls" checked class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                                Batas SLS
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-600 mb-1.5">
                                <input type="checkbox" id="layerLabel" checked class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                                Label Jalan (saat satelit)
                            </label>
                            <label class="flex items-center gap-2 text-sm text-slate-600">
                                <input type="checkbox" id="layerBangunan" checked class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                                Titik Bangunan
                            </label>
                        </div>

                        {{-- ================= QC SPASIAL ================= --}}
                        <div>
                            <p class="text-xs font-semibold text-slate-700 mb-2">Kualitas Spasial (QC)</p>

                            <label class="flex items-center justify-between text-sm text-slate-600 mb-2">
                                <span>Sorot Titik Luar Batas</span>
                                <span class="qc-switch"><input type="checkbox" id="qcSorotLuar"><span></span></span>
                            </label>
                            <label class="flex items-center justify-between text-sm text-slate-600 mb-2">
                                <span>Hanya Titik Luar Batas</span>
                                <span class="qc-switch"><input type="checkbox" id="qcHanyaLuar"><span></span></span>
                            </label>
                            <label class="flex items-center justify-between text-sm text-slate-600 mb-2">
                                <span>Sorot Titik Bergerombol</span>
                                <span class="qc-switch"><input type="checkbox" id="qcSorotGerombol"><span></span></span>
                            </label>
                            <label class="flex items-center justify-between text-sm text-slate-600">
                                <span>Hanya Titik Bergerombol</span>
                                <span class="qc-switch"><input type="checkbox" id="qcHanyaGerombol"><span></span></span>
                            </label>
                            <label class="flex items-center justify-between text-sm text-slate-600 mt-2">
                                <span>Hanya Perlu Perbaikan / Menunggu Verifikasi</span>
                                <span class="qc-switch"><input type="checkbox" id="qcHanyaPerbaikan"><span></span></span>
                            </label>

                            <p id="infoQc" class="mt-2 text-[11px] text-slate-400"></p>
                        </div>

                        <div>
                            <p class="text-xs font-semibold text-slate-700 mb-2">Legenda Titik</p>
                            <div class="space-y-1.5 text-xs text-slate-600">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-blue-500 inline-block"></span>
                                    Normal (di dalam SLS)
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-red-500 inline-block"></span>
                                    Di luar batas SLS
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-amber-500 inline-block"></span>
                                    Kode SLS tidak ditemukan
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-slate-500 inline-block"></span>
                                    Belum bisa dicek (batas SLS belum ada)
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-blue-500 inline-block" style="box-shadow:0 0 0 2px #7c3aed"></span>
                                    Ditambahkan lewat tagging (tepi ungu)
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-blue-500 inline-block" style="box-shadow:0 0 0 2px #ef4444"></span>
                                    Perlu perbaikan (cincin merah)
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-blue-500 inline-block" style="box-shadow:0 0 0 2px #f59e0b"></span>
                                    Sudah diperbaiki, menunggu verifikasi (cincin kuning)
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-blue-500 inline-block" style="box-shadow:0 0 0 2px #16a34a"></span>
                                    Terverifikasi admin (cincin hijau)
                                </div>
                            </div>
                        </div>

                        <div id="ringkasan" class="hidden pt-4 border-t border-slate-100 text-xs text-slate-600 space-y-1">
                            <p><b id="rTotal">0</b> total titik</p>
                            <p><b id="rNormal">0</b> normal</p>
                            <p><b id="rLuar">0</b> di luar SLS</p>
                            <p><b id="rTanpa">0</b> kode SLS tidak ditemukan</p>
                            <p><b id="rBelum">0</b> belum bisa dicek</p>
                            <p><b id="rGerombol">0</b> titik bergerombol</p>
                            <p><b id="rPerlu">0</b> perlu perbaikan</p>
                            <p><b id="rSudah">0</b> menunggu verifikasi</p>
                        </div>
                    </div>

                    {{-- ================= PETA ================= --}}
                    <div class="wadah-peta-map">
                        <div id="peta"></div>
                    </div>

                </div>
            </div>
        </div>
    </div>

    {{-- ================= MODAL DETAIL STATISTIK ================= --}}
    <div id="modalStatistik" class="hidden fixed inset-0 z-[2000] flex items-center justify-center p-4 bg-black/50">
        <div class="bg-white rounded-xl shadow-xl w-full max-w-5xl max-h-[85vh] flex flex-col">
            <div class="flex items-start justify-between px-5 py-4 border-b border-slate-200">
                <div>
                    <h3 id="modalJudul" class="font-bold text-slate-800 text-sm"></h3>
                    <p id="modalSub" class="text-xs text-slate-500"></p>
                </div>
                <button id="modalTutup" class="text-slate-400 hover:text-slate-700 text-xl leading-none">&times;</button>
            </div>
            <div class="overflow-auto">
                <table class="w-full text-xs">
                    <thead class="bg-slate-50 text-slate-500 uppercase sticky top-0">
                        <tr>
                            <th class="px-3 py-2 text-left">No. Urut Bangunan</th>
                            <th class="px-3 py-2 text-left">No. Urut Rumah Tangga</th>
                            <th class="px-3 py-2 text-left">Nama Usaha</th>
                            <th class="px-3 py-2 text-left">Nama Kepala Keluarga (KK)</th>
                            <th class="px-3 py-2 text-left">Nama Kepala Rumah Tangga (KRT)</th>
                        </tr>
                    </thead>
                    <tbody id="modalBody" class="divide-y divide-slate-100 text-slate-700"></tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ================= MODAL FORM TAGGING ================= --}}
    <div id="modalTag" class="hidden fixed inset-0 z-[2000] flex items-center justify-center p-4 bg-black/50">
        <form id="formTag" class="bg-white rounded-xl shadow-xl w-full max-w-lg max-h-[92vh] overflow-y-auto" autocomplete="off">
            <div class="px-5 pt-4 pb-3 border-b border-slate-200">
                <h3 id="tagJudul" class="font-bold text-slate-800 text-sm">Tambah Titik Bangunan</h3>
                <p id="tagInfo" class="text-xs text-slate-500 mt-0.5"></p>
            </div>

            <div class="p-5 space-y-4">
                <div id="tagError" class="hidden p-2.5 bg-red-50 border border-red-200 text-red-600 text-xs rounded-lg"></div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="tagNomor" class="block text-xs font-semibold text-slate-700 mb-1">No. Bangunan <span class="font-normal text-slate-400">(no_bang)</span></label>
                        <input id="tagNomor" type="text" inputmode="numeric" maxlength="6" required
                            class="w-full text-sm rounded-lg border-slate-300 focus:ring-teal-500 focus:border-teal-500">
                    </div>
                    <div>
                        <label for="tagKode" class="block text-xs font-semibold text-slate-700 mb-1">Kode Jenis <span class="font-normal text-slate-400">(kode_bang_value)</span></label>
                        <input id="tagKode" type="text" maxlength="10" placeholder="1 / 2 / 3 …"
                            class="w-full text-sm rounded-lg border-slate-300 focus:ring-teal-500 focus:border-teal-500">
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" id="tagBtt" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                        Bangunan tempat tinggal <span class="text-xs text-slate-400">(btt)</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" id="tagBku" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                        Bangunan khusus usaha <span class="text-xs text-slate-400">(bku)</span>
                    </label>
                    <label class="flex items-center gap-2 text-sm text-slate-700">
                        <input type="checkbox" id="tagRepair" class="rounded border-slate-300 text-teal-600 focus:ring-teal-500">
                        Perlu perbaikan <span class="text-xs text-slate-400">(flag_repair)</span>
                    </label>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label for="tagKk" class="block text-xs font-semibold text-slate-700 mb-1">Jumlah KK <span class="font-normal text-slate-400">(kk)</span></label>
                        <input id="tagKk" type="number" min="0" max="999" value="0" required
                            class="w-full text-sm rounded-lg border-slate-300 focus:ring-teal-500 focus:border-teal-500">
                    </div>
                    <div>
                        <label for="tagUsaha" class="block text-xs font-semibold text-slate-700 mb-1">Jumlah Usaha <span class="font-normal text-slate-400">(usaha)</span></label>
                        <input id="tagUsaha" type="number" min="0" max="999" value="0" required
                            class="w-full text-sm rounded-lg border-slate-300 focus:ring-teal-500 focus:border-teal-500">
                    </div>
                </div>

                {{-- Detail usaha (opsional) --}}
                <div class="rounded-lg border border-slate-200 p-3">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-slate-700">Daftar Usaha <span class="font-normal text-slate-400">(jika ada)</span></p>
                        <button type="button" id="tagTambahUsaha" class="text-xs font-semibold text-teal-700 hover:underline">+ Tambah usaha</button>
                    </div>
                    <div id="tagUsahaList" class="mt-2 space-y-2"></div>
                    <p class="mt-1 text-[11px] text-slate-400">Jumlah Usaha mengikuti daftar ini bila diisi.</p>
                </div>

                {{-- Detail keluarga / rumah tangga (opsional) --}}
                <div class="rounded-lg border border-slate-200 p-3">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold text-slate-700">Daftar Keluarga (KK) <span class="font-normal text-slate-400">(jika ada)</span></p>
                        <button type="button" id="tagTambahKk" class="text-xs font-semibold text-teal-700 hover:underline">+ Tambah KK</button>
                    </div>
                    <div id="tagKkList" class="mt-2 space-y-2"></div>
                    <p class="mt-1 text-[11px] text-slate-400">Jumlah KK mengikuti daftar ini bila diisi. Nomor KK ditampilkan tersamar di peta.</p>
                </div>
            </div>

            <div class="px-5 pb-5 flex items-center gap-2">
                <button type="button" id="tagHapus" class="hidden px-3 py-2 rounded-lg bg-white border border-red-200 text-red-600 hover:bg-red-50 text-xs font-semibold transition">Hapus</button>
                <span class="flex-1"></span>
                <button type="button" id="tagBatal" class="px-4 py-2 rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold transition">Batal</button>
                <button type="submit" id="tagSimpan" class="px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 disabled:opacity-60 text-white text-xs font-semibold transition">Simpan</button>
            </div>
        </form>
    </div>

    {{-- ================= PANEL GPS ================= --}}
    <div id="gpsBar" class="hidden">
        <div class="gps-baris">
            <span style="font-weight:600;color:#0f172a">Akurasi GPS</span>
            <span id="gpsAkurasi" class="gps-angka">…</span>
        </div>
        <div id="gpsStatus" class="gps-status">Mencari sinyal GPS…</div>
        <div class="gps-aksi">
            <button type="button" id="gpsBatal" class="gps-btn gps-btn-abu">Batal</button>
            <button type="button" id="gpsGunakan" class="gps-btn gps-btn-utama" disabled>Gunakan lokasi ini</button>
        </div>
    </div>

    <div id="toastTag" class="hidden"></div>

    <script>
        const urlDataSls = @json(route('peta.data.sls'));
        const urlDaftarSls = @json(route('peta.data.sls.daftar'));
        const urlDataBangunan = @json(route('peta.data.bangunan'));
        const urlDetailBangunan = @json(url('/peta/bangunan'));
        const urlStatistikSls = @json(url('/peta/sls'));

        function esc(nilai) {
            return String(nilai ?? '').replace(/[&<>"']/g, (c) => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
            }[c]));
        }

        const map = L.map('peta').setView([-2.5, 118], 5);

        // Tinggi wadah = sisa tinggi layar di bawah judul halaman (desktop)
        function aturTinggiPeta() {
            const wadah = document.getElementById('wadahPeta');

            if (window.innerWidth >= 1024) {
                const atas = wadah.getBoundingClientRect().top + window.scrollY;
                wadah.style.height = Math.max(480, window.innerHeight - atas - 24) + 'px';
            } else {
                wadah.style.height = 'auto';
            }
            map.invalidateSize();
        }
        window.addEventListener('resize', aturTinggiPeta);
        aturTinggiPeta();

        // ================= BASEMAP =================
        const basemapSatelit = L.tileLayer(
            'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            { maxZoom: 20, maxNativeZoom: 18, keepBuffer: 4, updateWhenZooming: false, attribution: 'Tiles &copy; Esri — Source: Esri, Maxar, Earthstar Geographics, and the GIS User Community' }
        );

        const labelJalan = L.tileLayer(
            'https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}',
            { maxZoom: 20, maxNativeZoom: 18, keepBuffer: 4, updateWhenZooming: false, attribution: 'Labels &copy; Esri' }
        );

        const basemapJalan = L.tileLayer(
            'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            { maxZoom: 20, maxNativeZoom: 19, keepBuffer: 4, updateWhenZooming: false, attribution: '&copy; OpenStreetMap contributors' }
        );

        basemapSatelit.addTo(map);
        labelJalan.addTo(map);

        function terapkanBasemap(mode) {
            map.removeLayer(basemapSatelit);
            map.removeLayer(basemapJalan);
            map.removeLayer(labelJalan);

            if (mode === 'satelit') {
                basemapSatelit.addTo(map);
                if (document.getElementById('layerLabel').checked) labelJalan.addTo(map);
            } else {
                basemapJalan.addTo(map);
            }
        }

        document.querySelectorAll('input[name="basemap"]').forEach(r => {
            r.addEventListener('change', (e) => terapkanBasemap(e.target.value));
        });

        document.getElementById('layerLabel').addEventListener('change', (e) => {
            const modeSatelit = document.querySelector('input[name="basemap"]:checked').value === 'satelit';
            if (! modeSatelit) return;
            e.target.checked ? labelJalan.addTo(map) : map.removeLayer(labelJalan);
        });

        // ================= WARNA STATUS TITIK =================
        const warnaStatus = {
            normal: '#3b82f6',
            di_luar_sls: '#ef4444',
            tanpa_sls: '#f59e0b',
            belum_dicek: '#64748b',
        };

        // Status perbaikan titik (kolom bangunan.status_perbaikan): cincin penanda di peta
        const warnaPerbaikan = { perlu_perbaikan: '#ef4444', sudah_diperbaiki: '#f59e0b', terverifikasi: '#16a34a' };
        const labelPerbaikan = { perlu_perbaikan: 'PERLU PERBAIKAN', sudah_diperbaiki: 'SUDAH DIPERBAIKI', terverifikasi: 'TERVERIFIKASI' };

        function ringStatus(feature) {
            const w = warnaPerbaikan[feature.properties.status_perbaikan];
            return w ? `0 0 0 3px ${w},` : '';
        }

        let modeGeser = null;   // {id, sls, nomor} saat titik sedang digeser

        // ================= BUKA POPUP TITIK =================
        // Dipakai klik titik langsung DAN klik polygon yang tepat mengenai titik,
        // supaya popup titik selalu sama di semua kegiatan (apa pun status polygon SLS-nya).
        function bukaPopupTitik(feature, layer) {
            if (modeGeser) return;                 // saat menggeser titik, klik dipakai untuk memilih posisi
            if (layer._memuatDetail) return;   // cegah dobel kalau 2 event datang sekaligus
            layer._memuatDetail = true;

            fetch(`${urlDetailBangunan}/${feature.properties.id}`)
                .then(res => res.json())
                .then(detail => layer.bindPopup(popupBangunan(detail), { maxWidth: 320 }).openPopup())
                .finally(() => { layer._memuatDetail = false; });
        }

        // Titik bangunan (lingkaran kecil di canvas) terdekat dari posisi klik, dalam radius piksel.
        function titikDekatKlik(latlng, batasPx = 14) {
            if (! bangunanLayer || ! map.hasLayer(bangunanLayer)) return null;

            const p = map.latLngToContainerPoint(latlng);
            let terdekat = null, jarakMin = batasPx;

            bangunanLayer.eachLayer(l => {
                if (! (l instanceof L.CircleMarker) || ! l.feature) return;
                const j = p.distanceTo(map.latLngToContainerPoint(l.getLatLng()));
                if (j <= jarakMin) { jarakMin = j; terdekat = l; }
            });
            return terdekat;
        }

        // ================= BATAS SLS =================
        const styleSls = { color: '#ef4444', weight: 3, fillOpacity: 0.05 };

        let slsLayer = null;
        let slsGeojson = null;     // polygon SLS dari server (hanya yang punya area)
        let slsTerpilih = null;    // id SLS yang dipilih (null = semua)
        const slsIndex = {};
        let dataDetailSls = null;
        let daftarSls = [];        // daftar SLS untuk dropdown
        let kecamatanTerpilih = '';   // filter wilayah: kecamatan ('' = semua)
        let kelurahanTerpilih = '';   // filter wilayah: kelurahan/desa ('' = semua)

        function kegiatanDipilih() {
            return document.getElementById('filterKegiatan').value;
        }

        function popupStatistikSls(res) {
            const s = res.sls;
            const t = res.statistik;

            return `
                <div style="min-width:230px; max-width:260px">
                    <div class="st-title">${esc(s.nama_sls)}</div>
                    <div class="st-sub">${esc(s.desa_kelurahan)}, ${esc(s.kecamatan)}</div>
                    <div class="st-id">ID SLS: ${esc(s.id_sls)}</div>
                    <hr class="pc-hr">
                    <div class="st-row c-hitam"><span>Total Bangunan:</span><span>${t.total_bangunan}</span></div>
                    <div class="st-row c-teal"><span>Jumlah Usaha:</span><span>${t.jumlah_usaha}</span></div>
                    <div class="st-row c-biru"><span>Titik Keluarga (KK):</span><span>${t.keluarga_kk}</span></div>
                    <div class="pc-actions">
                        <button type="button" class="pc-btn pc-btn-primary" onclick="bukaDetailStatistik()">📊 Detail Statistik</button>
                    </div>
                </div>
            `;
        }

        function bukaDetailStatistik() {
            if (! dataDetailSls) return;

            const { sls, statistik, daftar } = dataDetailSls;

            document.getElementById('modalJudul').textContent = sls.nama_sls;
            document.getElementById('modalSub').textContent =
                `${sls.desa_kelurahan}, ${sls.kecamatan} • ID SLS ${sls.id_sls} • ${statistik.total_bangunan} bangunan`;

            document.getElementById('modalBody').innerHTML = daftar.length
                ? daftar.map(r => `
                    <tr>
                        <td class="px-3 py-2 font-semibold">${esc(r.nomor_bangunan)}</td>
                        <td class="px-3 py-2">${esc(r.nomor_urut_rt)}</td>
                        <td class="px-3 py-2">${esc(r.nama_usaha)}</td>
                        <td class="px-3 py-2">${esc(r.nama_keluarga)}</td>
                        <td class="px-3 py-2">${esc(r.nama_rumah_tangga)}</td>
                    </tr>`).join('')
                : `<tr><td colspan="5" class="px-3 py-6 text-center text-slate-400">Belum ada bangunan di SLS ini.</td></tr>`;

            document.getElementById('modalStatistik').classList.remove('hidden');
        }

        document.getElementById('modalTutup').addEventListener('click', () => {
            document.getElementById('modalStatistik').classList.add('hidden');
        });
        document.getElementById('modalStatistik').addEventListener('click', (e) => {
            if (e.target.id === 'modalStatistik') e.target.classList.add('hidden');
        });

        function tampilStatistikSls(id, latlng) {
            const kegiatanId = kegiatanDipilih();
            const qs = kegiatanId ? `?kegiatan_id=${kegiatanId}` : '';

            fetch(`${urlStatistikSls}/${id}/statistik${qs}`)
                .then(res => res.json())
                .then(res => {
                    dataDetailSls = res;
                    L.popup({ maxWidth: 300 })
                        .setLatLng(latlng)
                        .setContent(popupStatistikSls(res))
                        .openOn(map);
                });
        }

        function renderSls() {
            if (slsLayer) {
                map.removeLayer(slsLayer);
                slsLayer = null;
            }
            Object.keys(slsIndex).forEach(k => delete slsIndex[k]);

            if (! kegiatanDipilih() || ! slsGeojson) return;

            const ids = idSlsWilayah();

            slsLayer = L.geoJSON(slsGeojson, {
                filter: (feature) =>
                    // SLS adalah snapshot per kegiatan: gambar hanya polygon milik kegiatan terpilih.
                    String(feature.properties.kegiatan_id) === String(kegiatanDipilih())
                    && (! ids || ids.has(feature.properties.id))
                    && (! slsTerpilih || feature.properties.id == slsTerpilih),
                style: styleSls,
                onEachFeature: (feature, layer) => {
                    slsIndex[feature.properties.id] = layer;
                    layer.on('click', (e) => {
                        if (modeTaggingAktif() || modeGeser) return;   // saat tagging/geser, klik di polygon = menaruh titik (ditangani klik peta)

                        // Titik bangunan berada DI DALAM polygon. Kalau klik ini mengenai sebuah titik,
                        // tampilkan popup titik itu (sama seperti kegiatan lain), bukan statistik SLS.
                        const titik = titikDekatKlik(e.latlng);
                        if (titik) {
                            L.DomEvent.stopPropagation(e);
                            bukaPopupTitik(titik.feature, titik);
                            return;
                        }
                        tampilStatistikSls(feature.properties.id, e.latlng);
                    });
                },
            });

            if (document.getElementById('layerSls').checked) slsLayer.addTo(map);
        }

        // Zoom ke polygon SLS terpilih; kalau tidak ada polygon, zoom ke titik bangunan.
        function zoomKeSls() {
            const punyaPoly = slsLayer && slsLayer.getLayers().length && slsLayer.getBounds().isValid();
            const punyaTitik = bangunanLayer && bangunanLayer.getLayers().length;

            if (slsTerpilih && punyaPoly) {
                map.fitBounds(slsLayer.getBounds(), { maxZoom: 19, padding: [30, 30] });
            } else if (punyaTitik) {
                map.fitBounds(bangunanLayer.getBounds(), { maxZoom: 19, padding: [30, 30] });
            } else if (punyaPoly) {
                map.fitBounds(slsLayer.getBounds(), { maxZoom: 18, padding: [30, 30] });
            }
        }

        // Pilih 1 SLS (id) atau null untuk semua SLS
        function pilihSls(id) {
            slsTerpilih = id;
            map.closePopup();
            renderSls();
            renderBangunan();
            zoomKeSls();
            aturModeTagging();
        }

        // Saat kegiatan diganti
        function gantiKegiatan() {
            const ada = !! kegiatanDipilih();
            const input = document.getElementById('filterSls');

            slsTerpilih = null;
            kecamatanTerpilih = '';
            kelurahanTerpilih = '';
            input.disabled = ! ada;
            input.value = '';
            document.getElementById('infoSls').classList.toggle('hidden', ada);
            document.getElementById('slsList').classList.add('hidden');

            map.closePopup();
            muatSls();   // [#9] memuat polygon kegiatan ini (sekaligus mengosongkan polygon kegiatan lama)
            muatDaftarSls();
            muatBangunan();   // zoom dilakukan setelah titik selesai dimuat
            aturModeTagging();
        }

        // Polygon SLS dari server (hanya untuk digambar). [#9] Dimuat per kegiatan supaya ringan.
        let permintaanSls = 0;   // respons dari permintaan lama diabaikan kalau kegiatan sudah berganti
        function muatSls() {
            const kegiatanId = kegiatanDipilih();
            const nomor = ++permintaanSls;

            slsGeojson = null;
            renderSls();   // kosongkan polygon kegiatan sebelumnya
            if (! kegiatanId) return;

            fetch(`${urlDataSls}?kegiatan_id=${encodeURIComponent(kegiatanId)}`, { headers: { 'Accept': 'application/json' } })
                .then(res => res.json())
                .then(geojson => {
                    if (nomor !== permintaanSls || ! geojson || ! geojson.features) return;
                    slsGeojson = geojson;
                    renderSls();
                    if (slsTerpilih) zoomKeSls();
                    aturModeTagging();   // status "SLS punya polygon" baru diketahui di sini
                });
        }

        // Daftar SLS untuk dropdown, dari database, tidak bergantung polygon
        function muatDaftarSls() {
            daftarSls = [];
            isiListSls();
            isiDropdownKecamatan();   // mengosongkan dropdown wilayah
            document.getElementById('jumlahSls').textContent = 0;

            const kegiatanId = kegiatanDipilih();
            if (! kegiatanId) return;

            fetch(`${urlDaftarSls}?kegiatan_id=${kegiatanId}`)
                .then(res => res.json())
                .then(list => {
                    daftarSls = list;
                    document.getElementById('jumlahSls').textContent = list.length;
                    isiDropdownKecamatan();
                    isiListSls();
                });
        }

        // ================= FILTER WILAYAH (KECAMATAN -> KELURAHAN) =================
        function unik(arr) {
            return [...new Set(arr.filter(Boolean))].sort((a, b) => a.localeCompare(b));
        }

        function isiDropdownKecamatan() {
            const el = document.getElementById('filterKecamatan');
            el.innerHTML = '<option value="">-- Semua Kecamatan --</option>' +
                unik(daftarSls.map(s => s.kecamatan))
                    .map(k => `<option value="${esc(k)}">${esc(k)}</option>`).join('');
            el.value = kecamatanTerpilih;
            el.disabled = daftarSls.length === 0;
            isiDropdownKelurahan();
        }

        function isiDropdownKelurahan() {
            const el = document.getElementById('filterKelurahan');
            const sumber = daftarSls.filter(s => s.kecamatan === kecamatanTerpilih);
            el.innerHTML = '<option value="">-- Semua Kelurahan --</option>' +
                unik(sumber.map(s => s.desa_kelurahan))
                    .map(k => `<option value="${esc(k)}">${esc(k)}</option>`).join('');
            el.value = kelurahanTerpilih;
            el.disabled = ! kecamatanTerpilih;
        }

        function cocokWilayah(s) {
            return (! kecamatanTerpilih || s.kecamatan === kecamatanTerpilih)
                && (! kelurahanTerpilih || s.desa_kelurahan === kelurahanTerpilih);
        }

        // null = tidak ada filter wilayah (tampilkan semua, termasuk titik tanpa SLS)
        function idSlsWilayah() {
            if (! kecamatanTerpilih) return null;
            return new Set(daftarSls.filter(cocokWilayah).map(s => s.id));
        }

        function terapkanWilayah() {
            slsTerpilih = null;
            document.getElementById('filterSls').value = '';
            map.closePopup();
            isiListSls();
            renderSls();
            renderBangunan();
            zoomKeSls();
            aturModeTagging();
        }

        function isiListSls(cari = '') {
            const q = cari.trim().toLowerCase();
            const items = daftarSls.filter(cocokWilayah).filter(p =>
                `${p.id_sls} ${p.nama_sls} ${p.desa_kelurahan}`.toLowerCase().includes(q));

            document.getElementById('slsList').innerHTML =
                `<li data-id="" class="px-3 py-2 cursor-pointer hover:bg-teal-50 font-semibold">-- Semua SLS --</li>` +
                items.map(p => `
                    <li data-id="${p.id}" class="px-3 py-2 cursor-pointer hover:bg-teal-50 border-t border-slate-100">
                        <b>${esc(p.id_sls)}</b> — ${esc(p.nama_sls)}
                        <span class="text-slate-400">(${esc(p.desa_kelurahan)}) • ${p.jumlah_bangunan} bgn${p.punya_polygon ? '' : ' • tanpa polygon'}</span>
                    </li>`).join('');
        }

        // ================= POPUP 1 BANGUNAN =================
        const cacheDetail = {};

        function popupBangunan(d) {
            cacheDetail[d.id] = d;
            const adaUsaha = d.usaha && d.usaha.length > 0;
            const adaRt = d.rumah_tangga && d.rumah_tangga.length > 0;

            const judul = adaUsaha
                ? d.usaha[0].nama_usaha
                : (adaRt && d.rumah_tangga[0].nama_kepala_keluarga
                    ? d.rumah_tangga[0].nama_kepala_keluarga
                    : `Bangunan #${d.nomor_bangunan ?? '-'}`);

            let badges = '';
            if (adaUsaha) {
                const jenis = d.usaha[0].jenis_usaha ? ': ' + d.usaha[0].jenis_usaha.toUpperCase() : '';
                badges += `<span class="pc-badge pc-badge-usaha">${esc('USAHA' + jenis)}</span>`;
            } else if (d.flag_bku) {
                badges += `<span class="pc-badge pc-badge-usaha">BANGUNAN USAHA</span>`;
            }

            if (d.status_perbaikan && d.status_perbaikan !== 'normal') {
                const kelas = { perlu_perbaikan: 'pc-badge-perlu', sudah_diperbaiki: 'pc-badge-sudah', terverifikasi: 'pc-badge-verif' }[d.status_perbaikan] ?? 'pc-badge-muted';
                badges += `<span class="pc-badge ${kelas}">${esc(labelPerbaikan[d.status_perbaikan] ?? d.status_perbaikan)}</span>`;
            } else if (d.diedit_manual_at) {
                badges += `<span class="pc-badge pc-badge-muted" title="Dikoreksi admin lewat peta; tidak ditimpa import ulang selama posisinya di file tidak berubah">DIEDIT MANUAL</span>`;
            }

            if (d.status_kondisi === 'normal') {
                badges += `<span class="pc-badge pc-badge-ok">DALAM BATAS SLS</span>`;
            } else if (d.status_kondisi === 'di_luar_sls') {
                badges += `<span class="pc-badge pc-badge-warn">LUAR BATAS SLS</span>`;
            } else if (d.status_kondisi === 'belum_dicek') {
                badges += `<span class="pc-badge pc-badge-muted">BATAS SLS BELUM TERSEDIA</span>`;
            } else {
                badges += `<span class="pc-badge pc-badge-warn">SLS TIDAK DITEMUKAN</span>`;
            }

            const wilayah = d.sls_tercatat && d.sls_tercatat.nama_sls
                ? `${d.sls_tercatat.nama_sls}${d.sls_tercatat.desa_kelurahan ? ', ' + d.sls_tercatat.desa_kelurahan : ''}`
                : '-';

            // ---- 5 standar informasi ----
            const blokRt = adaRt
                ? d.rumah_tangga.map(rt => `
                    <div class="pc-sub">
                        <div class="pc-sub-title">Rumah Tangga</div>
                        <div class="pc-row">No. Urut Rumah Tangga: <b>${esc(rt.nomor_urut_rumah_tangga)}</b></div>
                        <div class="pc-row">Nama Kepala Keluarga (KK): <b>${esc(rt.nama_kepala_keluarga)}</b></div>
                        <div class="pc-row">Nama Kepala Rumah Tangga (KRT): <b>${esc(rt.nama_kepala_rumah_tangga)}</b></div>
                    </div>`).join('')
                : `<div class="pc-sub">
                        <div class="pc-sub-title">Rumah Tangga</div>
                        <div class="pc-row">No. Urut Rumah Tangga: <b>-</b></div>
                        <div class="pc-row">Nama Kepala Keluarga (KK): <b>-</b></div>
                        <div class="pc-row">Nama Kepala Rumah Tangga (KRT): <b>-</b></div>
                   </div>`;

            // ---- 5 standar informasi: blok Usaha (Nama Usaha) ----
            const blokUsaha = adaUsaha
                ? d.usaha.map(u => `
                    <div class="pc-sub">
                        <div class="pc-sub-title">Usaha</div>
                        <div class="pc-row">Nama Usaha: <b>${esc(u.nama_usaha)}</b></div>
                    </div>`).join('')
                : `<div class="pc-sub">
                        <div class="pc-sub-title">Usaha</div>
                        <div class="pc-row">Nama Usaha: <b>-</b></div>
                   </div>`;

            let kotak = '';

            if (d.status_kondisi === 'di_luar_sls') {
                const aktual = d.sls_aktual
                    ? `${d.sls_aktual.nama_sls} (ID: ${d.sls_aktual.id_sls})`
                    : 'tidak ada SLS manapun yang cocok';

                kotak = `
                    <div class="pc-warn-box">
                        <div class="pc-warn-title">⚠ PERINGATAN: POSISI DI LUAR BATAS SLS</div>
                        <div class="pc-warn-row">Tercatat di data: <b>${esc(d.sls_tercatat?.nama_sls ?? '-')} (ID: ${esc(d.sls_tercatat?.id_sls ?? '-')})</b></div>
                        <div class="pc-warn-row">Lokasi GPS riil berada di: <b>${esc(aktual)}</b></div>
                        <div class="pc-warn-row">Jarak deviasi pergeseran: <b>~${esc(d.jarak_meter ?? '?')} meter</b></div>
                        <div class="pc-reco">Saran rekomendasi: lakukan geotagging ulang di lokasi ini.</div>
                    </div>`;
            } else if (d.status_kondisi === 'tanpa_sls') {
                kotak = `
                    <div class="pc-warn-box">
                        <div class="pc-warn-title">⚠ PERINGATAN: KODE SLS TIDAK DITEMUKAN</div>
                        <div class="pc-warn-row">Kode SLS pada data: <b>${esc(d.sls_tercatat?.id_sls ?? '-')}</b></div>
                        <div class="pc-warn-row">Kode ini belum ada di database SLS.</div>
                        <div class="pc-reco">Saran rekomendasi: cek kode SLS pada data sumber.</div>
                    </div>`;
            } else if (d.status_kondisi === 'belum_dicek') {
                kotak = `
                    <div class="pc-info-box">
                        Polygon batas SLS ini belum diimpor, jadi posisi titik belum bisa
                        diperiksa. Pemeriksaan berjalan otomatis setelah batas SLS diimpor.
                    </div>`;
            }

            let koreksi = '';
            if (['sudah_diperbaiki', 'terverifikasi'].includes(d.status_perbaikan) || d.latitude_asal != null) {
                const asal = d.latitude_asal != null
                    ? `<div>Koordinat awal (file): <b>${esc(Number(d.latitude_asal).toFixed(6))}, ${esc(Number(d.longitude_asal).toFixed(6))}</b></div>` : '';
                const oleh = d.dikoreksi_oleh
                    ? `<div>Dikoreksi oleh: <b>${esc(d.dikoreksi_oleh)}</b>${d.dikoreksi_at ? ' &middot; ' + esc(new Date(d.dikoreksi_at).toLocaleString('id-ID')) : ''}</div>` : '';
                const status = d.status_perbaikan === 'terverifikasi' ? 'Terverifikasi admin'
                    : (d.status_perbaikan === 'sudah_diperbaiki' ? 'Menunggu verifikasi admin' : 'Belum diverifikasi');
                koreksi = `<div class="pc-koreksi-box"><div class="pc-sub-title">Koreksi</div>${oleh}${asal}<div>Status: <b>${status}</b></div></div>`;
            }

            // Catatan admin saat mengembalikan koreksi: petugas perlu tahu apa yang harus diperbaiki.
            const alasanTolak = d.alasan_tolak
                ? `<div class="pc-koreksi-box" style="border-color:#fca5a5;background:#fef2f2"><div class="pc-sub-title" style="color:#b91c1c">Dikembalikan admin</div><div>${esc(d.alasan_tolak)}</div></div>`
                : '';

            const gmaps = `https://www.google.com/maps?q=${encodeURIComponent(d.latitude + ',' + d.longitude)}`;
            const gnav = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(d.latitude + ',' + d.longitude)}`;

            return `
                <div style="min-width:250px; max-width:290px; max-height:360px; overflow-y:auto">
                    <div class="pc-badges">${badges}</div>
                    <div class="pc-title">${esc(judul)}</div>
                    <div class="pc-row">Nomor Bangunan: <b>${esc(d.nomor_bangunan ?? '-')}</b></div>
                    <div class="pc-row">Wilayah (SLS): <b>${esc(wilayah)}</b></div>
                    <div class="pc-row">ID SLS Tercatat: <b>${esc(d.sls_tercatat?.id_sls ?? '-')}</b></div>
                    <div class="pc-row">Jumlah KK: <b>${esc(d.jumlah_kk)}</b> &nbsp;|&nbsp; Jumlah Usaha: <b>${esc(d.jumlah_usaha)}</b></div>
                    ${blokRt}
                    ${blokUsaha}
                    ${kotak}
                    ${koreksi}
                    ${alasanTolak}
                    ${d.sumber === 'tagging' ? `
                        <div class="pc-row" style="margin-top:6px">Ditag lewat aplikasi oleh: <b>${esc(d.ditag_oleh ?? '-')}</b></div>
                        <div class="pc-row">Metode: <b>${d.sumber_tag === 'gps' ? 'GPS di lokasi' : 'Klik peta (admin)'}</b>${d.akurasi_gps != null ? ` &nbsp;|&nbsp; Akurasi: <b>±${esc(Number(d.akurasi_gps).toFixed(1))} m</b>` : ''}</div>
                        ${d.ditandai_at ? `<div class="pc-row">Waktu: <b>${esc(new Date(d.ditandai_at).toLocaleString('id-ID'))}</b></div>` : ''}` : ''}
                    ${d.dapat_diubah ? `
                    <div class="pc-actions">
                        <button type="button" class="pc-btn pc-btn-secondary" onclick="bukaFormUbah(${Number(d.id)})">✏️ Ubah</button>
                        ${d.sls_id ? `<button type="button" class="pc-btn pc-btn-secondary" onclick="mulaiGeser(${Number(d.id)})">📍 Ambil ulang lokasi</button>` : ''}
                        ${d.dapat_dihapus ? `<button type="button" class="pc-btn pc-btn-secondary" style="color:#dc2626" onclick="hapusTag(${Number(d.id)})">🗑 Hapus</button>` : ''}
                    </div>` : ''}
                    ${d.dapat_verifikasi ? `
                    <div class="pc-actions">
                        <button type="button" class="pc-btn pc-btn-primary" onclick="verifikasiTitik(${Number(d.id)})">✅ Verifikasi</button>
                        <button type="button" class="pc-btn pc-btn-secondary" style="color:#dc2626" onclick="kembalikanTitik(${Number(d.id)})">↩ Kembalikan</button>
                    </div>` : ''}
                    <div class="pc-actions">
                        <a href="${gmaps}" target="_blank" rel="noopener" class="pc-btn pc-btn-secondary">📍 Lihat Maps</a>
                        <a href="${gnav}" target="_blank" rel="noopener" class="pc-btn pc-btn-primary">🚗 Navigasi</a>
                    </div>
                </div>
            `;
        }

        // ================= QC SPASIAL =================
        const JARAK_GEROMBOL_M = 15;
        const MIN_GEROMBOL = 3;

        function qcOn(id) {
            return document.getElementById(id).checked;
        }

        function qcFlag(feature) {
            const p = feature.properties;
            return {
                luar: p.status_kondisi === 'di_luar_sls',
                gerombol: !! p._gerombol,
                perbaikan: ['perlu_perbaikan', 'sudah_diperbaiki'].includes(p.status_perbaikan),
            };
        }


        function hitungBergerombol(features) {
            const sel = 0.00015;   // ± 16 meter
            const grid = new Map();
            const kunci = (x, y) => x + ',' + y;

            features.forEach(f => {
                if (! f.geometry?.coordinates) { f.properties._gerombol = false; return; }
                const [lng, lat] = f.geometry.coordinates;
                const k = kunci(Math.floor(lng / sel), Math.floor(lat / sel));
                if (! grid.has(k)) grid.set(k, []);
                grid.get(k).push(f);
            });

            features.forEach(f => {
                if (! f.geometry?.coordinates) { f.properties._gerombol = false; return; }
                const [lng, lat] = f.geometry.coordinates;
                const cx = Math.floor(lng / sel), cy = Math.floor(lat / sel);
                const mPerLng = 111320 * Math.cos(lat * Math.PI / 180);
                let dekat = 0;

                for (let dx = -1; dx <= 1; dx++) {
                    for (let dy = -1; dy <= 1; dy++) {
                        (grid.get(kunci(cx + dx, cy + dy)) || []).forEach(g => {
                            const [glng, glat] = g.geometry.coordinates;
                            const jarak = Math.hypot((glng - lng) * mPerLng, (glat - lat) * 111320);
                            if (jarak <= JARAK_GEROMBOL_M) dekat++;
                        });
                    }
                }
                f.properties._gerombol = dekat >= MIN_GEROMBOL;
            });
        }

        function ringQc(feature) {
            const q = qcFlag(feature);
            const ring = [];
            if (qcOn('qcSorotGerombol') && q.gerombol) ring.push('0 0 0 3px #8b5cf6');
            if (qcOn('qcSorotLuar') && q.luar) ring.push(`0 0 0 ${ring.length ? 6 : 3}px #ef4444`);
            return ring.length ? ring.join(',') + ',' : '';
        }

        // Mode QC aktif = ada toggle QC yang menyala (toggle luar batas yang nonaktif tidak dihitung)
        function qcAktif() {
            const sorotLuarAktif = qcOn('qcSorotLuar') && ! document.getElementById('qcSorotLuar').disabled;
            return qcOn('qcSorotGerombol') || qcOn('qcHanyaGerombol') || qcOn('qcHanyaLuar') || qcOn('qcHanyaPerbaikan') || sorotLuarAktif;
        }

        // Toggle "luar batas" otomatis aktif kalau sudah ada titik di_luar_sls
        function aturToggleQc(hitung) {
            const luarAda = !! hitung && hitung.di_luar_sls > 0;

            ['qcSorotLuar', 'qcHanyaLuar'].forEach(id => {
                const el = document.getElementById(id);
                el.disabled = ! luarAda;
                el.closest('label').classList.toggle('qc-row-disabled', ! luarAda);
            });
            if (! luarAda) document.getElementById('qcHanyaLuar').checked = false;

            const info = document.getElementById('infoQc');
            if (! hitung) {
                info.textContent = 'Pilih kegiatan dulu untuk memakai filter QC.';
            } else if (hitung.belum_dicek > 0) {
                info.textContent = `${hitung.belum_dicek} titik belum bisa dicek luar batas (menunggu batas SLS diimpor).`;
            } else if (hitung.di_luar_sls === 0) {
                info.textContent = 'Tidak ada titik di luar batas SLS.';
            } else {
                info.textContent = '';
            }
        }

        // ================= IKON =================
        function iconBernomor(feature) {
            const p = feature.properties;
            const warna = warnaStatus[p.status_kondisi] ?? '#64748b';
            return L.divIcon({
                className: '',
                html: `<div class="marker-nomor" style="background:${warna};${p.sumber === 'tagging' ? 'border-color:#7c3aed;' : ''}box-shadow:${ringStatus(feature)}${ringQc(feature)}0 1px 4px rgba(0,0,0,.5)">${esc(p.nomor_bangunan ?? '?')}</div>`,
                iconSize: [26, 26],
                iconAnchor: [13, 13],
                popupAnchor: [0, -13],
            });
        }

        function iconTitikKecil(feature) {
            const warna = warnaStatus[feature.properties.status_kondisi] ?? '#64748b';
            return L.divIcon({
                className: '',
                html: `<div style="width:12px;height:12px;border-radius:50%;background:${warna};border:2px solid ${feature.properties.sumber === 'tagging' ? '#7c3aed' : '#fff'};box-shadow:${ringStatus(feature)}${ringQc(feature)}0 1px 3px rgba(0,0,0,.5)"></div>`,
                iconSize: [12, 12],
                iconAnchor: [6, 6],
                popupAnchor: [0, -6],
            });
        }


        function iconCluster(cluster) {
            const anak = cluster.getAllChildMarkers();
            const n = anak.length;
            const ukuran = n < 10 ? 'small' : (n < 100 ? 'medium' : 'large');
            const adaLuar = qcOn('qcSorotLuar') && anak.some(m => qcFlag(m.feature).luar);

            return L.divIcon({
                html: `<div><span>${n}</span></div>`,
                className: `marker-cluster marker-cluster-${ukuran}${adaLuar ? ' marker-cluster-qc' : ''}`,
                iconSize: L.point(40, 40),
            });
        }

        // ================= TITIK BANGUNAN =================
        let bangunanLayer = null;
        let dataBangunanGeojson = null;
        const canvasTitik = L.canvas({ padding: 0.5 });

        function muatBangunan() {
            dataBangunanGeojson = null;
            renderBangunan();

            const kegiatanId = kegiatanDipilih();
            if (! kegiatanId) return;

            fetch(`${urlDataBangunan}?kegiatan_id=${kegiatanId}`)
                .then(res => res.json())
                .then(geojson => {
                    hitungBergerombol(geojson.features);
                    dataBangunanGeojson = geojson;
                    renderBangunan();
                    zoomKeSls();
                });
        }

        // Titik berdekatan otomatis digabung (cluster) dan menyebar saat diklik/di-zoom.
        function renderBangunan() {
            if (bangunanLayer) {
                map.removeLayer(bangunanLayer);
                bangunanLayer = null;
            }

            document.getElementById('ringkasan').classList.add('hidden');
            if (! dataBangunanGeojson) {
                aturToggleQc(null);
                return;
            }

            // Saring per wilayah (kecamatan/kelurahan) lalu per SLS terpilih
            const ids = idSlsWilayah();
            const semua = dataBangunanGeojson.features.filter(f =>
                (! ids || ids.has(f.properties.sls_id))
                && (! slsTerpilih || f.properties.sls_id == slsTerpilih));

            // Ringkasan dihitung dari semua titik (sebelum filter "Hanya ...")
            const hitung = { normal: 0, di_luar_sls: 0, tanpa_sls: 0, belum_dicek: 0 };
            let jumlahGerombol = 0, jumlahPerlu = 0, jumlahSudah = 0;
            semua.forEach(f => {
                const status = f.properties.status_kondisi;
                hitung[status] = (hitung[status] ?? 0) + 1;
                if (f.properties._gerombol) jumlahGerombol++;
                if (f.properties.status_perbaikan === 'perlu_perbaikan') jumlahPerlu++;
                if (f.properties.status_perbaikan === 'sudah_diperbaiki') jumlahSudah++;
            });

            const total = hitung.normal + hitung.di_luar_sls + hitung.tanpa_sls + hitung.belum_dicek;
            document.getElementById('rTotal').textContent = total;
            document.getElementById('rNormal').textContent = hitung.normal;
            document.getElementById('rLuar').textContent = hitung.di_luar_sls;
            document.getElementById('rTanpa').textContent = hitung.tanpa_sls;
            document.getElementById('rBelum').textContent = hitung.belum_dicek;
            document.getElementById('rGerombol').textContent = jumlahGerombol;
            document.getElementById('rPerlu').textContent = jumlahPerlu;
            document.getElementById('rSudah').textContent = jumlahSudah;
            document.getElementById('ringkasan').classList.remove('hidden');

            aturToggleQc(hitung);   // harus sebelum membaca toggle "Hanya ..."

            // Filter "Hanya ...":
            const hanyaLuar = qcOn('qcHanyaLuar');
            const hanyaGerombol = qcOn('qcHanyaGerombol');
            const hanyaPerbaikan = qcOn('qcHanyaPerbaikan');
            const fitur = (hanyaLuar || hanyaGerombol || hanyaPerbaikan)
                ? semua.filter(f => {
                    const q = qcFlag(f);
                    return (hanyaLuar && q.luar) || (hanyaGerombol && q.gerombol) || (hanyaPerbaikan && q.perbaikan);
                })
                : semua;

            const klikTitik = (feature, layer) => {
                layer.on('click', (ev) => {
                    if (modeGeser) return;            // biarkan klik naik ke peta (memilih posisi baru)
                    L.DomEvent.stopPropagation(ev);   // klik titik tidak boleh ikut menaruh titik baru
                    bukaPopupTitik(feature, layer);
                });
            };

            if (! qcAktif()) {
                // Mode biasa: titik apa adanya, tanpa cluster
                bangunanLayer = L.geoJSON({ type: 'FeatureCollection', features: fitur }, {
                    pointToLayer: (feature, latlng) => {
                        const status = feature.properties.status_kondisi;

                        if (! slsTerpilih) {
                            const wp = warnaPerbaikan[feature.properties.status_perbaikan];   // cincin status perbaikan
                            return L.circleMarker(latlng, {
                                renderer: canvasTitik, radius: wp ? 6 : 5,
                                weight: wp ? 3 : (feature.properties.sumber === 'tagging' ? 2 : 1),
                                color: wp ?? (feature.properties.sumber === 'tagging' ? '#7c3aed' : '#fff'),
                                fillColor: warnaStatus[status] ?? '#64748b', fillOpacity: 0.9,
                            });
                        }
                        return L.marker(latlng, { icon: iconBernomor(feature) });
                    },
                    onEachFeature: klikTitik,
                });
            } else {
                // Mode QC: titik disorot dan digabung (cluster) supaya yang numpuk bisa diklik
                const geoLayer = L.geoJSON({ type: 'FeatureCollection', features: fitur }, {
                    pointToLayer: (feature, latlng) => L.marker(latlng, {
                        icon: slsTerpilih ? iconBernomor(feature) : iconTitikKecil(feature),
                        riseOnHover: true,
                    }),
                    onEachFeature: klikTitik,
                });

                bangunanLayer = L.markerClusterGroup({
                    chunkedLoading: true,
                    maxClusterRadius: slsTerpilih ? 32 : 40,
                    spiderfyOnMaxZoom: true,
                    spiderfyDistanceMultiplier: 1.6,
                    showCoverageOnHover: false,
                    iconCreateFunction: iconCluster,
                });
                bangunanLayer.addLayer(geoLayer);
            }

            if (document.getElementById('layerBangunan').checked) bangunanLayer.addTo(map);
        }

        // ================= TAGGING =================
        const urlTagging = @json(url('/tagging'));
        const urlPenugasan = @json(route('tagging.penugasan'));
        const urlNomor = @json(route('tagging.nomor'));
        const urlExport = @json(route('tagging.export'));
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

        let tagBebas = @json(auth()->user()->isAdmin());   // admin: boleh di SLS mana pun
        let penugasanSaya = [];                            // viewer: SLS yang ditugaskan admin
        let markerSementara = null;
        let formTag = null;                                // {mode: 'tambah'|'ubah', ...}
        const AKURASI_MAKS = @json(config('tagging.akurasi_maks_m'));   // meter
        let gpsWatch = null, gpsMarker = null, gpsCircle = null, gpsTerakhir = null;

        const el = (id) => document.getElementById(id);

        function toast(pesan, galat = false) {
            const t = el('toastTag');
            t.textContent = pesan;
            t.style.background = galat ? '#dc2626' : '#0f766e';
            t.classList.remove('hidden');
            clearTimeout(toast._t);
            toast._t = setTimeout(() => t.classList.add('hidden'), 3500);
        }

        function modeTaggingAktif() {
            return el('modeTagging').checked && ! el('modeTagging').disabled;
        }

        function slsPunyaPolygon(id) {
            return !! slsGeojson && slsGeojson.features.some(f => f.properties.id == id);
        }

        function bolehTaggingDi(kegiatan, sls) {
            if (! kegiatan || ! sls) return false;
            return tagBebas || penugasanSaya.some(p => p.kegiatan_id == kegiatan && p.sls_id == sls);
        }

        // Titik di dalam polygon SLS? true/false; null = SLS tidak punya polygon.
        function titikDalamSls(latlng, slsId) {
            const f = slsGeojson?.features.find(x => x.properties.id == slsId);
            if (! f) return null;

            const polys = f.geometry.type === 'Polygon' ? [f.geometry.coordinates]
                : (f.geometry.type === 'MultiPolygon' ? f.geometry.coordinates : []);

            const dalamCincin = (ring) => {
                let dalam = false;
                for (let i = 0, j = ring.length - 1; i < ring.length; j = i++) {
                    const [xi, yi] = ring[i], [xj, yj] = ring[j];
                    if ((yi > latlng.lat) !== (yj > latlng.lat)
                        && latlng.lng < (xj - xi) * (latlng.lat - yi) / (yj - yi) + xi) dalam = ! dalam;
                }
                return dalam;
            };

            return polys.some(poly => dalamCincin(poly[0]) && ! poly.slice(1).some(dalamCincin));
        }

        // Aktif/nonaktifkan toggle sesuai kegiatan + SLS yang sedang dipilih
        function aturModeTagging() {
            const toggle = el('modeTagging');
            const kegiatan = Number(kegiatanDipilih()) || null;
            const sls = slsTerpilih ? Number(slsTerpilih) : null;

            let boleh = bolehTaggingDi(kegiatan, sls);
            let pesan;

            if (! kegiatan) {
                pesan = tagBebas ? 'Pilih kegiatan, lalu satu SLS.' : 'Pilih SLS penugasan Anda di atas.';
            } else if (! sls) {
                pesan = tagBebas
                    ? 'Pilih satu SLS (filter di bawah) untuk mulai tagging.'
                    : (penugasanSaya.length ? 'Pilih salah satu SLS penugasan Anda di atas.' : 'Belum ada SLS yang ditugaskan kepada Anda. Hubungi admin.');
            } else if (! boleh) {
                pesan = 'SLS ini tidak ditugaskan kepada Anda, tagging dinonaktifkan.';
            } else if (! slsPunyaPolygon(sls) && ! tagBebas) {
                boleh = false;
                pesan = slsGeojson ? 'SLS ini belum punya batas polygon, tagging belum bisa dilakukan. Hubungi admin.' : 'Memuat batas SLS…';
            } else {
                pesan = null;
            }

            toggle.disabled = ! boleh;
            if (! boleh) toggle.checked = false;
            toggle.closest('label').classList.toggle('qc-row-disabled', ! boleh);

            el('infoTagging').textContent = pesan ?? (toggle.checked
                ? (tagBebas
                    ? 'Aktif: klik di dalam batas SLS, atau pakai GPS.'
                    : 'Aktif: datangi bangunan, lalu tekan "Ambil Lokasi Saya".')
                : 'Nyalakan Mode Tagging untuk mulai.');
            if (! toggle.checked) gpsBerhenti();

            el('btnLokasiSaya').disabled = ! toggle.checked;
            map.getContainer().classList.toggle('mode-tagging', toggle.checked);

            document.querySelectorAll('.btn-tugas').forEach(b => {
                b.classList.toggle('aktif', Number(b.dataset.k) === kegiatan && Number(b.dataset.s) === sls);
            });
        }

        // ----- Panel "Penugasan Saya" -----
        function renderPenugasan() {
            const box = el('daftarPenugasan');

            if (tagBebas) {
                box.textContent = 'Admin dapat tagging di SLS mana pun: pilih kegiatan dan SLS di bawah.';
                return;
            }
            if (! penugasanSaya.length) {
                box.textContent = 'Belum ada SLS yang ditugaskan kepada Anda. Hubungi admin.';
                return;
            }

            const grup = {};
            penugasanSaya.forEach(p => (grup[p.nama_kegiatan] ??= []).push(p));

            box.innerHTML = '<p class="font-semibold text-slate-700 mb-0.5">SLS penugasan Anda</p>'
                + Object.entries(grup).map(([nama, items]) => `
                    <div class="mt-2">
                        <p class="text-[10px] uppercase tracking-wide text-slate-400">${esc(nama)}</p>
                        ${items.map(p => `
                            <button type="button" class="btn-tugas" data-k="${p.kegiatan_id}" data-s="${p.sls_id}" ${p.punya_polygon ? '' : 'disabled title="SLS ini belum punya batas polygon"'}>
                                <b>${esc(p.id_sls)}</b> — ${esc(p.nama_sls)}
                                <small>${esc(p.desa_kelurahan)} • ${p.jumlah_titik} titik (${p.titik_saya} oleh Anda)${p.punya_polygon ? '' : ' • tanpa polygon'}</small>
                            </button>`).join('')}
                    </div>`).join('');
        }

        function mulaiTugas(kegiatanId, slsId) {
            const item = penugasanSaya.find(p => p.kegiatan_id == kegiatanId && p.sls_id == slsId);
            if (! item) return;

            const pilih = el('filterKegiatan');
            if (String(pilih.value) !== String(kegiatanId)) {
                pilih.value = String(kegiatanId);
                gantiKegiatan();
            }

            // Penugasan bisa SLS yang belum punya titik: jangan tersaring oleh filter wilayah.
            kecamatanTerpilih = '';
            kelurahanTerpilih = '';
            isiDropdownKecamatan();

            el('filterSls').disabled = false;
            el('filterSls').value = item.id_sls;
            pilihSls(Number(slsId));

            if (! el('modeTagging').disabled) {
                el('modeTagging').checked = true;
                aturModeTagging();
            }
        }

        function muatPenugasan() {
            fetch(urlPenugasan, { headers: { 'Accept': 'application/json' } })
                .then(res => res.json())
                .then(j => {
                    tagBebas = !! j.bebas;
                    penugasanSaya = j.penugasan ?? [];
                    renderPenugasan();
                    aturModeTagging();

                    // Dibuka dari menu "Tagging": arahkan pandangan ke panel tagging (penting di layar kecil)
                    if (new URLSearchParams(location.search).get('mode') === 'tagging') {
                        el('panelTagging')?.scrollIntoView({ block: 'nearest' });
                    }

                    // Viewer: langsung mulai di SLS penugasan pertama (kecuali dibuka dari dashboard dengan ?kegiatan=)
                    const slsAwal = new URLSearchParams(location.search).get('sls');
                    if (! tagBebas && slsAwal && kegiatanAwal
                        && penugasanSaya.some(p => p.kegiatan_id == kegiatanAwal && p.sls_id == slsAwal)) {
                        mulaiTugas(Number(kegiatanAwal), Number(slsAwal));   // dibuka dari "Penugasan Saya"
                    } else if (! tagBebas && ! kegiatanAwal) {
                        const pertama = penugasanSaya.find(p => p.punya_polygon);
                        if (pertama) mulaiTugas(pertama.kegiatan_id, pertama.sls_id);
                    }
                })
                .catch(() => { el('daftarPenugasan').textContent = 'Gagal memuat penugasan.'; });
        }

        el('daftarPenugasan').addEventListener('click', (e) => {
            const b = e.target.closest('.btn-tugas');
            if (b && ! b.disabled) mulaiTugas(Number(b.dataset.k), Number(b.dataset.s));
        });

        el('modeTagging').addEventListener('change', () => {
            if (! el('modeTagging').checked) hapusMarkerSementara();
            aturModeTagging();
        });

        // ----- Form tambah / ubah -----
        function hapusMarkerSementara() {
            if (markerSementara) { map.removeLayer(markerSementara); markerSementara = null; }
        }

        function tampilGalat(pesan) {
            const box = el('tagError');
            box.textContent = pesan || '';
            box.classList.toggle('hidden', ! pesan);
        }

        function infoSlsAktif() {
            const s = daftarSlsLengkap().find(x => x.id == slsTerpilih);
            return s ? `SLS ${s.id_sls} — ${s.nama_sls}` : `SLS #${slsTerpilih}`;
        }

        // Info SLS untuk judul form: dari penugasan (viewer) atau daftar SLS kegiatan (admin)
        function daftarSlsLengkap() {
            return [
                ...penugasanSaya.map(p => ({ id: p.sls_id, id_sls: p.id_sls, nama_sls: p.nama_sls })),
                ...daftarSls,
            ];
        }

        function isiForm(nilai) {
            el('tagNomor').value = nilai.nomor ?? '';
            el('tagKode').value = nilai.kode ?? '';
            el('tagBtt').checked = !! nilai.btt;
            el('tagBku').checked = !! nilai.bku;
            el('tagRepair').checked = !! nilai.repair;
            el('tagKk').value = nilai.kk ?? 0;
            el('tagUsaha').value = nilai.usaha ?? 0;

            el('tagUsahaList').innerHTML = '';
            el('tagKkList').innerHTML = '';
            (nilai.usahaList ?? []).forEach(tambahBarisUsaha);
            (nilai.keluargaList ?? []).forEach(tambahBarisKeluarga);
            sinkronJumlah();
            tampilGalat('');
        }

        // ----- Daftar usaha & keluarga di form tagging -----
        const kelasInput = 'w-full text-xs rounded-lg border-slate-300 focus:ring-teal-500 focus:border-teal-500';

        function tambahBarisUsaha(v = {}) {
            const div = document.createElement('div');
            div.className = 'baris-usaha flex items-start gap-2';
            div.innerHTML = `
                <div class="flex-1 grid grid-cols-2 gap-2">
                    <input data-f="nama_usaha" placeholder="Nama usaha" value="${esc(v.nama_usaha ?? '')}" maxlength="255" class="${kelasInput}">
                    <input data-f="jenis_usaha" placeholder="Jenis usaha (opsional)" value="${esc(v.jenis_usaha ?? '')}" maxlength="255" class="${kelasInput}">
                </div>
                <button type="button" data-hapus class="px-2 py-1 text-red-500 hover:bg-red-50 rounded-lg text-sm" title="Hapus">✕</button>`;
            el('tagUsahaList').appendChild(div);
            sinkronJumlah();
        }

        function tambahBarisKeluarga(v = {}) {
            const div = document.createElement('div');
            div.className = 'baris-kk flex items-start gap-2';
            div.innerHTML = `
                <div class="flex-1 grid grid-cols-2 gap-2">
                    <input data-f="nama_kepala_keluarga" placeholder="Nama kepala keluarga" value="${esc(v.nama_kepala_keluarga ?? '')}" maxlength="255" class="${kelasInput} col-span-2">
                    <input data-f="nomor_kk" inputmode="numeric" placeholder="Nomor KK (opsional)" value="${esc(v.nomor_kk ?? '')}" maxlength="20" class="${kelasInput}">
                    <input data-f="jumlah_anggota" type="number" min="1" max="99" placeholder="Jml anggota" value="${esc(v.jumlah_anggota ?? '')}" class="${kelasInput}">
                    <input data-f="nomor_urut_rumah_tangga" type="number" min="1" max="999" placeholder="No. urut rumah tangga" value="${esc(v.nomor_urut_rumah_tangga ?? '')}" class="${kelasInput}">
                    <input data-f="nama_kepala_rumah_tangga" placeholder="Kepala rumah tangga" value="${esc(v.nama_kepala_rumah_tangga ?? '')}" maxlength="255" class="${kelasInput}">
                </div>
                <button type="button" data-hapus class="px-2 py-1 text-red-500 hover:bg-red-50 rounded-lg text-sm" title="Hapus">✕</button>`;
            el('tagKkList').appendChild(div);
            sinkronJumlah();
        }

        // Baris yang sama sekali kosong dibuang; selebihnya dikirim apa adanya (server yang memvalidasi).
        function bacaBaris(wadahId) {
            return [...el(wadahId).children].map(baris => {
                const nilai = {};
                baris.querySelectorAll('[data-f]').forEach(i => { nilai[i.dataset.f] = i.value.trim() === '' ? null : i.value.trim(); });
                return nilai;
            }).filter(n => Object.values(n).some(v => v !== null));
        }

        // Kalau ada daftar rinci, jumlah mengikuti daftar (dikunci); kalau kosong, jumlah boleh diisi manual.
        function sinkronJumlah() {
            [['tagUsahaList', 'tagUsaha'], ['tagKkList', 'tagKk']].forEach(([daftar, jumlah]) => {
                const n = el(daftar).children.length;
                const input = el(jumlah);
                if (n > 0) { input.value = n; }
                input.readOnly = n > 0;
                input.classList.toggle('bg-slate-100', n > 0);
            });
        }

        el('tagTambahUsaha').addEventListener('click', () => tambahBarisUsaha());
        el('tagTambahKk').addEventListener('click', () => tambahBarisKeluarga());
        ['tagUsahaList', 'tagKkList'].forEach(id => el(id).addEventListener('click', (e) => {
            const b = e.target.closest('[data-hapus]');
            if (b) { b.parentElement.remove(); sinkronJumlah(); }
        }));

        function bukaModalTag() {
            el('modalTag').classList.remove('hidden');
            setTimeout(() => el('tagNomor').focus(), 50);
        }

        function tutupModalTag() {
            el('modalTag').classList.add('hidden');
            hapusMarkerSementara();
            formTag = null;
        }

        async function bukaFormTambah(latlng, opsi = { sumber: 'peta', akurasi: null }) {
            const kegiatan = Number(kegiatanDipilih()) || null;
            const sls = slsTerpilih ? Number(slsTerpilih) : null;

            if (! bolehTaggingDi(kegiatan, sls)) {
                toast('Pilih SLS penugasan Anda terlebih dulu.', true);
                return;
            }
            if (titikDalamSls(latlng, sls) === false) {
                toast('Titik di luar batas SLS. Letakkan titik di dalam batas SLS yang dipilih.', true);
                return;
            }

            hapusMarkerSementara();
            markerSementara = L.marker(latlng, {
                interactive: false,
                icon: L.divIcon({
                    className: '',
                    html: '<div style="width:18px;height:18px;border-radius:50%;background:#7c3aed;border:3px solid #fff;box-shadow:0 0 0 2px #7c3aed,0 2px 6px rgba(0,0,0,.5)"></div>',
                    iconSize: [18, 18], iconAnchor: [9, 9],
                }),
            }).addTo(map);

            formTag = { mode: 'tambah', kegiatan, sls, lat: latlng.lat, lng: latlng.lng, sumber: opsi.sumber, akurasi: opsi.akurasi };

            el('tagJudul').textContent = 'Tambah Titik Bangunan';
            el('tagInfo').textContent = `${infoSlsAktif()} • ${latlng.lat.toFixed(7)}, ${latlng.lng.toFixed(7)}`
                + (opsi.sumber === 'gps' && opsi.akurasi != null ? ` • GPS ±${opsi.akurasi} m` : ' • dari klik peta');
            el('tagHapus').classList.add('hidden');
            isiForm({ nomor: '', btt: false, bku: false, repair: false, kk: 0, usaha: 0 });
            bukaModalTag();

            // Usulan nomor berikutnya (nomor terbesar di SLS + 1); tidak menimpa kalau sudah diketik
            try {
                const res = await fetch(`${urlNomor}?kegiatan_id=${kegiatan}&sls_id=${sls}`, { headers: { 'Accept': 'application/json' } });
                const j = await res.json();
                if (formTag && formTag.mode === 'tambah' && ! el('tagNomor').value && j.nomor) el('tagNomor').value = j.nomor;
            } catch (e) { /* usulan nomor opsional */ }
        }

        function bukaFormUbah(id) {
            const d = cacheDetail[id];
            if (! d) return;

            map.closePopup();
            formTag = { mode: 'ubah', id };

            el('tagJudul').textContent = 'Ubah Titik Bangunan';
            el('tagInfo').textContent = `${d.sls_tercatat?.id_sls ? 'SLS ' + d.sls_tercatat.id_sls : ''} • ${Number(d.latitude).toFixed(7)}, ${Number(d.longitude).toFixed(7)}`
                + (d.akurasi_gps != null ? ` • GPS ±${Number(d.akurasi_gps).toFixed(1)} m` : '');
            el('tagHapus').classList.toggle('hidden', ! d.dapat_dihapus);   // titik import tidak bisa dihapus dari sini
            isiForm({
                nomor: d.nomor_bangunan, kode: d.kode_bang_value, btt: d.flag_btt, bku: d.flag_bku,
                repair: d.flag_repair, kk: d.jumlah_kk, usaha: d.jumlah_usaha,
                usahaList: d.usaha_form, keluargaList: d.keluarga_form,
            });
            bukaModalTag();
        }

        async function kirimJson(url, method, body) {
            const res = await fetch(url, {
                method,
                headers: {
                    'Content-Type': 'application/json', 'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken, 'X-Requested-With': 'XMLHttpRequest',
                },
                body: body ? JSON.stringify(body) : undefined,
            });

            let json = {};
            try { json = await res.json(); } catch (e) { /* bukan JSON (mis. sesi habis) */ }

            if (! res.ok) {
                const pertama = json.errors ? Object.values(json.errors)[0]?.[0] : null;
                throw new Error(pertama || json.message || 'Gagal menyimpan. Muat ulang halaman lalu coba lagi.');
            }
            return json;
        }

        // Sinkronkan data titik di peta tanpa memuat ulang seluruh kegiatan
        function terapkanFitur(fitur) {
            if (! dataBangunanGeojson) return;
            const arr = dataBangunanGeojson.features;
            const i = arr.findIndex(x => x.properties.id === fitur.properties.id);
            if (i >= 0) arr[i] = fitur; else arr.push(fitur);
            hitungBergerombol(arr);
            renderBangunan();
        }

        el('formTag').addEventListener('submit', async (ev) => {
            ev.preventDefault();
            if (! formTag) return;

            const atribut = {
                nomor_bangunan: el('tagNomor').value.trim(),
                kode_bang_value: el('tagKode').value.trim() || null,
                flag_btt: el('tagBtt').checked,
                flag_bku: el('tagBku').checked,
                flag_repair: el('tagRepair').checked,
                jumlah_kk: Number(el('tagKk').value),
                jumlah_usaha: Number(el('tagUsaha').value),
                usaha: bacaBaris('tagUsahaList'),
                keluarga: bacaBaris('tagKkList'),
            };

            el('tagSimpan').disabled = true;
            try {
                const hasil = formTag.mode === 'tambah'
                    ? await kirimJson(urlTagging, 'POST', {
                        kegiatan_id: formTag.kegiatan, sls_id: formTag.sls,
                        latitude: formTag.lat, longitude: formTag.lng,
                        sumber_tag: formTag.sumber, akurasi_gps: formTag.akurasi, ...atribut,
                    })
                    : await kirimJson(`${urlTagging}/${formTag.id}`, 'PATCH', atribut);

                terapkanFitur(hasil.fitur);
                if (formTag.mode === 'ubah') delete cacheDetail[formTag.id];   // popup memuat ulang detail terbaru
                if (penugasanSaya.length && formTag.mode === 'tambah') {
                    const p = penugasanSaya.find(x => x.kegiatan_id == formTag.kegiatan && x.sls_id == formTag.sls);
                    if (p) { p.jumlah_titik++; p.titik_saya++; renderPenugasan(); }
                }
                tutupModalTag();
                aturModeTagging();
                toast(hasil.pesan);
            } catch (e) {
                tampilGalat(e.message);
            } finally {
                el('tagSimpan').disabled = false;
            }
        });

        async function hapusTag(id) {
            if (! confirm('Hapus titik bangunan ini? Tindakan ini tidak bisa dibatalkan.')) return;

            try {
                const hasil = await kirimJson(`${urlTagging}/${id}`, 'DELETE');
                if (dataBangunanGeojson) {
                    dataBangunanGeojson.features = dataBangunanGeojson.features.filter(x => x.properties.id !== id);
                    hitungBergerombol(dataBangunanGeojson.features);
                }
                const d = cacheDetail[id];
                if (d && penugasanSaya.length) {
                    const p = penugasanSaya.find(x => x.kegiatan_id == d.kegiatan_id && x.sls_id == d.sls_id);
                    if (p) { p.jumlah_titik = Math.max(0, p.jumlah_titik - 1); p.titik_saya = Math.max(0, p.titik_saya - 1); renderPenugasan(); }
                }
                tutupModalTag();
                map.closePopup();
                renderBangunan();
                aturModeTagging();
                toast(hasil.pesan);
            } catch (e) {
                toast(e.message, true);
            }
        }

        el('tagBatal').addEventListener('click', tutupModalTag);
        el('tagHapus').addEventListener('click', () => { if (formTag?.mode === 'ubah') hapusTag(formTag.id); });
        document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && ! el('modalTag').classList.contains('hidden')) tutupModalTag(); });

        // Klik peta (saat Mode Tagging menyala) = titik baru di posisi itu
        // Klik peta: hanya admin (koreksi/penempatan manual). Mitra wajib memakai GPS di lokasi.
        map.on('click', (e) => {
            if (modeGeser) {
                if (tagBebas) konfirmasiGeserAdmin(e.latlng);   // petugas wajib GPS di lokasi, bukan klik peta
                return;
            }
            if (! modeTaggingAktif()) return;
            if (tagBebas) bukaFormTambah(e.latlng, { sumber: 'peta', akurasi: null });
            else toast('Titik harus diambil lewat GPS di lokasi bangunan. Tekan "Ambil Lokasi Saya".', true);
        });

        // ================= AMBIL ULANG LOKASI (koreksi koordinat) =================
        // Petugas: datang lagi ke bangunan lalu ambil GPS. Admin: klik posisi yang benar di peta. Server memeriksa posisi baru
        // harus di dalam batas SLS, menyimpan koordinat awal dari file, dan menghitung ulang status luar batas.
        function batalGeser() {
            modeGeser = null;
            hapusMarkerSementara();
            gpsBerhenti();
            map.getContainer().classList.remove('mode-tagging');
        }

        function mulaiGeser(id) {
            const d = cacheDetail[id];
            if (! d) return;
            map.closePopup();
            batalGeser();
            modeGeser = { id, sls: Number(d.sls_id), nomor: d.nomor_bangunan };

            if (tagBebas) {
                map.getContainer().classList.add('mode-tagging');
                toast(`Klik posisi yang benar untuk bangunan no. ${d.nomor_bangunan ?? '-'} di dalam batas SLS. Tekan Esc untuk batal.`);
            } else {
                toast(`Datangi bangunan no. ${d.nomor_bangunan ?? '-'}, tunggu akurasi GPS baik, lalu tekan "Gunakan lokasi ini".`);
                gpsMulai();
            }
        }

        function konfirmasiGeserAdmin(latlng) {
            if (! modeGeser) return;
            if (titikDalamSls(latlng, modeGeser.sls) === false) {
                toast('Lokasi ini di luar batas SLS titik ini. Klik di dalam batas SLS.', true);
                return;
            }

            hapusMarkerSementara();
            markerSementara = L.marker(latlng, {
                interactive: false,
                icon: L.divIcon({
                    className: '',
                    html: '<div style="width:18px;height:18px;border-radius:50%;background:#7c3aed;border:3px solid #fff;box-shadow:0 0 0 2px #7c3aed,0 2px 6px rgba(0,0,0,.5)"></div>',
                    iconSize: [18, 18], iconAnchor: [9, 9],
                }),
            }).addTo(map);

            setTimeout(() => {
                if (modeGeser && confirm(`Simpan posisi ini sebagai lokasi bangunan no. ${modeGeser.nomor ?? '-'}?`)) kirimGeser(latlng, 'peta', null);
                else hapusMarkerSementara();
            }, 50);
        }

        async function kirimGeser(latlng, sumber, akurasi) {
            const g = modeGeser;
            if (! g) return;

            try {
                const hasil = await kirimJson(`${urlTagging}/${g.id}/geser`, 'PATCH', {
                    latitude: latlng.lat, longitude: latlng.lng, sumber_tag: sumber, akurasi_gps: akurasi,
                });
                delete cacheDetail[g.id];
                terapkanFitur(hasil.fitur);
                batalGeser();
                toast(hasil.pesan);
            } catch (e) {
                if (! tagBebas) batalGeser();    // GPS sudah berhenti: ulangi dari tombol Ambil ulang lokasi
                else hapusMarkerSementara();
                toast(e.message, true);
            }
        }

        async function ubahStatusKoreksi(id, aksi) {
            let body;
            if (aksi === 'tolak') {
                // prompt() mengembalikan null kalau dibatalkan; string kosong = tanpa alasan.
                const alasan = prompt('Kembalikan titik ini ke status "perlu perbaikan"?\n\nTulis alasan / apa yang harus diperbaiki (opsional, terlihat oleh petugas):', '');
                if (alasan === null) return;
                body = { alasan: alasan.trim() };
            }
            try {
                const hasil = await kirimJson(`${urlTagging}/${id}/${aksi}`, 'POST', body);
                delete cacheDetail[id];
                map.closePopup();
                terapkanFitur(hasil.fitur);
                toast(hasil.pesan);
            } catch (e) {
                toast(e.message, true);
            }
        }
        const verifikasiTitik = (id) => ubahStatusKoreksi(id, 'verifikasi');
        const kembalikanTitik = (id) => ubahStatusKoreksi(id, 'tolak');

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && modeGeser) { batalGeser(); toast('Pengambilan ulang lokasi dibatalkan.'); }
        });

        // ----- Ambil lokasi GPS (live) + catat akurasi -----
        function warnaAkurasi(m) {
            return m <= 10 ? '#16a34a' : (m <= AKURASI_MAKS ? '#d97706' : '#dc2626');
        }

        function gpsBerhenti() {
            if (gpsWatch !== null) { navigator.geolocation.clearWatch(gpsWatch); gpsWatch = null; }
            if (gpsMarker) { map.removeLayer(gpsMarker); gpsMarker = null; }
            if (gpsCircle) { map.removeLayer(gpsCircle); gpsCircle = null; }
            gpsTerakhir = null;
            el('gpsBar').classList.add('hidden');
        }

        function gpsMulai() {
            if (! navigator.geolocation) { toast('Perangkat ini tidak mendukung GPS.', true); return; }
            if (! window.isSecureContext) {
                toast('GPS hanya bisa dipakai lewat HTTPS (atau localhost). Buka aplikasi lewat alamat HTTPS.', true);
                return;
            }

            gpsBerhenti();
            el('gpsAkurasi').textContent = '…';
            el('gpsAkurasi').style.color = '#0f172a';
            el('gpsStatus').textContent = 'Mencari sinyal GPS… tunggu hingga akurasi cukup baik.';
            el('gpsGunakan').disabled = true;
            el('gpsBar').classList.remove('hidden');

            gpsWatch = navigator.geolocation.watchPosition(gpsTerima, gpsGalat, {
                enableHighAccuracy: true, maximumAge: 0, timeout: 30000,
            });
        }

        function gpsTerima(pos) {
            const akurasi = pos.coords.accuracy;
            const ll = L.latLng(pos.coords.latitude, pos.coords.longitude);

            if (! gpsMarker) {
                gpsMarker = L.circleMarker(ll, { radius: 7, color: '#fff', weight: 2, fillColor: '#2563eb', fillOpacity: 1, interactive: false }).addTo(map);
                gpsCircle = L.circle(ll, { radius: akurasi, color: '#2563eb', weight: 1, fillColor: '#2563eb', fillOpacity: .12, interactive: false }).addTo(map);
                map.setView(ll, Math.max(map.getZoom(), 19));
            } else {
                gpsMarker.setLatLng(ll);
                gpsCircle.setLatLng(ll);
                gpsCircle.setRadius(akurasi);
            }

            gpsTerakhir = { ll, akurasi };

            const sls = modeGeser ? modeGeser.sls : (slsTerpilih ? Number(slsTerpilih) : null);
            const dalam = titikDalamSls(ll, sls);   // true / false / null (tanpa polygon)
            const cukupAkurat = akurasi <= AKURASI_MAKS;

            let pesan;
            if (! cukupAkurat) pesan = `Sinyal masih kasar (maksimal ±${AKURASI_MAKS} m). Tunggu sebentar atau pindah ke area terbuka.`;
            else if (dalam === false) pesan = 'Posisi Anda berada di luar batas SLS yang dipilih. Pastikan Anda berada di dalam SLS tersebut.';
            else pesan = 'Siap. Pastikan Anda berdiri di titik bangunan, lalu tekan "Gunakan lokasi ini".';

            el('gpsAkurasi').textContent = `±${akurasi.toFixed(1)} m`;
            el('gpsAkurasi').style.color = warnaAkurasi(akurasi);
            el('gpsStatus').textContent = pesan;
            el('gpsGunakan').disabled = ! (cukupAkurat && dalam !== false);
        }

        function gpsGalat(err) {
            const pesan = {
                1: 'Akses lokasi ditolak. Izinkan lokasi untuk situs ini di pengaturan browser.',
                2: 'Lokasi tidak tersedia. Aktifkan GPS/lokasi pada perangkat.',
                3: 'Waktu habis saat mencari sinyal GPS. Coba lagi di area terbuka.',
            }[err.code] ?? 'Lokasi tidak bisa dibaca.';
            gpsBerhenti();
            toast(pesan, true);
        }

        function gpsGunakan() {
            if (! gpsTerakhir) return;
            const { ll, akurasi } = gpsTerakhir;
            gpsBerhenti();
            if (modeGeser) { kirimGeser(ll, 'gps', Math.round(akurasi * 10) / 10); return; }
            bukaFormTambah(ll, { sumber: 'gps', akurasi: Math.round(akurasi * 10) / 10 });
        }

        el('btnLokasiSaya').addEventListener('click', () => { modeGeser = null; gpsMulai(); });
        el('gpsBatal').addEventListener('click', () => { if (modeGeser) batalGeser(); else gpsBerhenti(); });
        el('gpsGunakan').addEventListener('click', gpsGunakan);

        // Unduh GeoJSON sesuai kegiatan & SLS yang sedang dipilih
        el('btnExport').addEventListener('click', (e) => {
            const p = new URLSearchParams();
            if (kegiatanDipilih()) p.set('kegiatan_id', kegiatanDipilih());
            if (slsTerpilih) p.set('sls_id', slsTerpilih);
            if (el('exportCakupan')) p.set('cakupan', el('exportCakupan').value);
            e.currentTarget.href = `${urlExport}${p.toString() ? '?' + p : ''}`;
        });

        // ================= EVENT-EVENT =================
        document.getElementById('filterKegiatan').addEventListener('change', gantiKegiatan);

        // Filter wilayah
        document.getElementById('filterKecamatan').addEventListener('change', (e) => {
            kecamatanTerpilih = e.target.value;
            kelurahanTerpilih = '';
            isiDropdownKelurahan();
            terapkanWilayah();
        });

        document.getElementById('filterKelurahan').addEventListener('change', (e) => {
            kelurahanTerpilih = e.target.value;
            terapkanWilayah();
        });

        function pilihDariList(id, label) {
            document.getElementById('filterSls').value = label;
            document.getElementById('slsList').classList.add('hidden');
            pilihSls(id);
        }

        const inputSls = document.getElementById('filterSls');

        inputSls.addEventListener('focus', () => {
            isiListSls();
            document.getElementById('slsList').classList.remove('hidden');
        });
        inputSls.addEventListener('input', (e) => {
            isiListSls(e.target.value);
            document.getElementById('slsList').classList.remove('hidden');
        });

        document.getElementById('slsList').addEventListener('click', (e) => {
            const li = e.target.closest('li');
            if (! li) return;
            const id = li.dataset.id ? Number(li.dataset.id) : null;
            pilihDariList(id, id ? li.querySelector('b').textContent : '');
        });

        document.addEventListener('click', (e) => {
            if (! e.target.closest('#filterSls, #slsList')) {
                document.getElementById('slsList').classList.add('hidden');
            }
        });

        // Reset: kosongkan kecamatan, kelurahan, dan SLS sekaligus
        document.getElementById('resetSls').addEventListener('click', () => {
            kecamatanTerpilih = '';
            kelurahanTerpilih = '';
            isiDropdownKecamatan();
            document.getElementById('slsList').classList.add('hidden');
            terapkanWilayah();
        });

        document.getElementById('layerSls').addEventListener('change', (e) => {
            if (! slsLayer) return;
            e.target.checked ? slsLayer.addTo(map) : map.removeLayer(slsLayer);
        });
        document.getElementById('layerBangunan').addEventListener('change', (e) => {
            if (! bangunanLayer) return;
            e.target.checked ? bangunanLayer.addTo(map) : map.removeLayer(bangunanLayer);
        });

        // Toggle QC
        ['qcSorotLuar', 'qcSorotGerombol'].forEach(id =>
            document.getElementById(id).addEventListener('change', renderBangunan));

        ['qcHanyaLuar', 'qcHanyaGerombol', 'qcHanyaPerbaikan'].forEach(id =>
            document.getElementById(id).addEventListener('change', () => {
                renderBangunan();
                zoomKeSls();
            }));

        aturToggleQc(null);   // kondisi awal: belum ada kegiatan dipilih (polygon dimuat saat kegiatan dipilih)

        // Buka dari dashboard: /peta?kegiatan=ID langsung memilih kegiatan itu
        const kegiatanAwal = new URLSearchParams(window.location.search).get('kegiatan');
        if (kegiatanAwal) {
            const pilihan = document.getElementById('filterKegiatan');
            pilihan.value = kegiatanAwal;
            if (pilihan.value === kegiatanAwal) gantiKegiatan();
        }

        muatPenugasan();
        aturModeTagging();
    </script>
</x-app-layout>