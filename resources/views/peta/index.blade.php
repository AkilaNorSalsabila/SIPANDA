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
        .pc-title { font-size: 14px; font-weight: 800; color: #0f172a; margin-bottom: 6px; font-family: sans-serif; }
        .pc-row { font-size: 12px; color: #334155; margin-bottom: 3px; font-family: sans-serif; }
        .pc-row b { color: #0f172a; }
        .pc-hr { margin: 8px 0; border: none; border-top: 1px solid #e2e8f0; }
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

        /* Cincin merah pada cluster yang berisi titik di luar batas SLS */
        .marker-cluster-qc > div { box-shadow: 0 0 0 4px rgba(239,68,68,.75); }
    </style>

    <div class="py-6">
        <div class="max-w-[1600px] mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div id="wadahPeta" class="grid grid-cols-1 lg:grid-cols-[280px_1fr]">

                    {{-- ================= SIDEBAR FILTER ================= --}}
                    <div class="border-b lg:border-b-0 lg:border-r border-slate-200 p-5 space-y-6 lg:overflow-y-auto">

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
                                    <span class="w-3 h-3 rounded-full bg-blue-500 inline-block" style="box-shadow:0 0 0 2px #8b5cf6"></span>
                                    Cincin ungu: titik bergerombol
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full bg-red-500 inline-block" style="box-shadow:0 0 0 2px #ef4444"></span>
                                    Cincin merah: di luar batas SLS
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
        <div class="bg-white rounded-xl shadow-xl w-full max-w-4xl max-h-[85vh] flex flex-col">
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
                            <th class="px-3 py-2 text-left">Nama Usaha</th>
                            <th class="px-3 py-2 text-left">Nama Keluarga</th>
                        </tr>
                    </thead>
                    <tbody id="modalBody" class="divide-y divide-slate-100 text-slate-700"></tbody>
                </table>
            </div>
        </div>
    </div>

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

        // ================= BATAS SLS =================
        const styleSls = { color: '#ef4444', weight: 3, fillOpacity: 0.05 };

        let slsLayer = null;
        let slsGeojson = null;     // polygon SLS dari server (hanya yang punya area)
        let slsTerpilih = null;    // id SLS yang dipilih (null = semua)
        const slsIndex = {};
        let dataDetailSls = null;
        let daftarSls = [];        // daftar SLS untuk dropdown

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
                        <td class="px-3 py-2">${esc(r.nama_usaha)}</td>
                        <td class="px-3 py-2">${esc(r.nama_keluarga)}</td>
                    </tr>`).join('')
                : `<tr><td colspan="3" class="px-3 py-6 text-center text-slate-400">Belum ada bangunan di SLS ini.</td></tr>`;

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

        // Batas SLS hanya digambar kalau kegiatan sudah dipilih.
        // Kalau ada SLS terpilih, yang digambar cuma SLS itu (kalau punya polygon).
        function renderSls() {
            if (slsLayer) {
                map.removeLayer(slsLayer);
                slsLayer = null;
            }
            Object.keys(slsIndex).forEach(k => delete slsIndex[k]);

            if (! kegiatanDipilih() || ! slsGeojson) return;

            slsLayer = L.geoJSON(slsGeojson, {
                filter: (feature) => ! slsTerpilih || feature.properties.id == slsTerpilih,
                style: styleSls,
                onEachFeature: (feature, layer) => {
                    slsIndex[feature.properties.id] = layer;
                    layer.on('click', (e) => tampilStatistikSls(feature.properties.id, e.latlng));
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
            renderBangunan();   // harus sebelum zoom, supaya titik sudah ada
            zoomKeSls();
        }

        // Saat kegiatan diganti
        function gantiKegiatan() {
            const ada = !! kegiatanDipilih();
            const input = document.getElementById('filterSls');

            slsTerpilih = null;
            input.disabled = ! ada;
            input.value = '';
            document.getElementById('infoSls').classList.toggle('hidden', ada);
            document.getElementById('slsList').classList.add('hidden');

            map.closePopup();
            renderSls();
            muatDaftarSls();
            muatBangunan();   // zoom dilakukan setelah titik selesai dimuat
        }

        // Polygon SLS dari server (hanya untuk digambar)
        function muatSls() {
            fetch(urlDataSls)
                .then(res => res.json())
                .then(geojson => {
                    slsGeojson = geojson;
                    renderSls();
                });
        }

        // Daftar SLS untuk dropdown, dari database, tidak bergantung polygon
        function muatDaftarSls() {
            daftarSls = [];
            isiListSls();
            document.getElementById('jumlahSls').textContent = 0;

            const kegiatanId = kegiatanDipilih();
            if (! kegiatanId) return;

            fetch(`${urlDaftarSls}?kegiatan_id=${kegiatanId}`)
                .then(res => res.json())
                .then(list => {
                    daftarSls = list;
                    document.getElementById('jumlahSls').textContent = list.length;
                    isiListSls();
                });
        }

        function isiListSls(cari = '') {
            const q = cari.trim().toLowerCase();
            const items = daftarSls.filter(p =>
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
        function popupBangunan(d) {
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

            const keterangan = adaUsaha ? (d.usaha[0].jenis_usaha ?? '-')
                : (adaRt ? `No. KK: ${d.rumah_tangga[0].nomor_kk ?? '-'}` : 'Belum diisi manual');

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

            const gmaps = `https://www.google.com/maps?q=${encodeURIComponent(d.latitude + ',' + d.longitude)}`;
            const gnav = `https://www.google.com/maps/dir/?api=1&destination=${encodeURIComponent(d.latitude + ',' + d.longitude)}`;

            return `
                <div style="min-width:250px; max-width:280px">
                    <div class="pc-badges">${badges}</div>
                    <div class="pc-title">${esc(judul)}</div>
                    <div class="pc-row">Nomor Bangunan: <b>${esc(d.nomor_bangunan ?? '-')}</b></div>
                    <div class="pc-row">Wilayah (SLS): <b>${esc(wilayah)}</b></div>
                    <div class="pc-row">Keterangan: <b>${esc(keterangan)}</b></div>
                    <div class="pc-row">ID SLS Tercatat: <b>${esc(d.sls_tercatat?.id_sls ?? '-')}</b></div>
                    <div class="pc-row">Jumlah KK: <b>${esc(d.jumlah_kk)}</b> &nbsp;|&nbsp; Jumlah Usaha: <b>${esc(d.jumlah_usaha)}</b></div>
                    ${kotak}
                    <div class="pc-actions">
                        <a href="${gmaps}" target="_blank" rel="noopener" class="pc-btn pc-btn-secondary">📍 Lihat Maps</a>
                        <a href="${gnav}" target="_blank" rel="noopener" class="pc-btn pc-btn-primary">🚗 Navigasi</a>
                    </div>
                </div>
            `;
        }

        // ================= QC SPASIAL =================
        const JARAK_GEROMBOL_M = 15;   // radius dianggap "berdekatan" (meter)
        const MIN_GEROMBOL = 3;        // minimal titik (termasuk dirinya) agar dianggap bergerombol

        function qcOn(id) {
            return document.getElementById(id).checked;
        }

        function qcFlag(feature) {
            const p = feature.properties;
            return { luar: p.status_kondisi === 'di_luar_sls', gerombol: !! p._gerombol };
        }

        // Tandai titik bergerombol (pakai grid supaya ringan untuk ribuan titik)
        function hitungBergerombol(features) {
            const sel = 0.00015;   // ± 16 meter
            const grid = new Map();
            const kunci = (x, y) => x + ',' + y;

            features.forEach(f => {
                const [lng, lat] = f.geometry.coordinates;
                const k = kunci(Math.floor(lng / sel), Math.floor(lat / sel));
                if (! grid.has(k)) grid.set(k, []);
                grid.get(k).push(f);
            });

            features.forEach(f => {
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

        // Cincin sorotan di sekeliling titik (string box-shadow, berakhir dengan koma kalau ada isi)
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
            return qcOn('qcSorotGerombol') || qcOn('qcHanyaGerombol') || qcOn('qcHanyaLuar') || sorotLuarAktif;
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
                html: `<div class="marker-nomor" style="background:${warna};box-shadow:${ringQc(feature)}0 1px 4px rgba(0,0,0,.5)">${esc(p.nomor_bangunan ?? '?')}</div>`,
                iconSize: [26, 26],
                iconAnchor: [13, 13],
                popupAnchor: [0, -13],
            });
        }

        function iconTitikKecil(feature) {
            const warna = warnaStatus[feature.properties.status_kondisi] ?? '#64748b';
            return L.divIcon({
                className: '',
                html: `<div style="width:12px;height:12px;border-radius:50%;background:${warna};border:2px solid #fff;box-shadow:${ringQc(feature)}0 1px 3px rgba(0,0,0,.5)"></div>`,
                iconSize: [12, 12],
                iconAnchor: [6, 6],
                popupAnchor: [0, -6],
            });
        }

        // Ikon cluster: gaya bawaan markercluster + cincin merah kalau berisi titik luar batas
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

        // Semua SLS: titik kecil. 1 SLS dipilih: marker bernomor.
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

            const semua = dataBangunanGeojson.features
                .filter(f => ! slsTerpilih || f.properties.sls_id == slsTerpilih);

            // Ringkasan dihitung dari semua titik (sebelum filter "Hanya ...")
            const hitung = { normal: 0, di_luar_sls: 0, tanpa_sls: 0, belum_dicek: 0 };
            let jumlahGerombol = 0;
            semua.forEach(f => {
                const status = f.properties.status_kondisi;
                hitung[status] = (hitung[status] ?? 0) + 1;
                if (f.properties._gerombol) jumlahGerombol++;
            });

            const total = hitung.normal + hitung.di_luar_sls + hitung.tanpa_sls + hitung.belum_dicek;
            document.getElementById('rTotal').textContent = total;
            document.getElementById('rNormal').textContent = hitung.normal;
            document.getElementById('rLuar').textContent = hitung.di_luar_sls;
            document.getElementById('rTanpa').textContent = hitung.tanpa_sls;
            document.getElementById('rBelum').textContent = hitung.belum_dicek;
            document.getElementById('rGerombol').textContent = jumlahGerombol;
            document.getElementById('ringkasan').classList.remove('hidden');

            aturToggleQc(hitung);   // harus sebelum membaca toggle "Hanya ..."

            // Filter "Hanya ...": kalau dua-duanya aktif, tampilkan gabungan keduanya
            const hanyaLuar = qcOn('qcHanyaLuar');
            const hanyaGerombol = qcOn('qcHanyaGerombol');
            const fitur = (hanyaLuar || hanyaGerombol)
                ? semua.filter(f => {
                    const q = qcFlag(f);
                    return (hanyaLuar && q.luar) || (hanyaGerombol && q.gerombol);
                })
                : semua;

            const klikTitik = (feature, layer) => {
                layer.on('click', () => {
                    fetch(`${urlDetailBangunan}/${feature.properties.id}`)
                        .then(res => res.json())
                        .then(detail => layer.bindPopup(popupBangunan(detail), { maxWidth: 320 }).openPopup());
                });
            };

            if (! qcAktif()) {
                // Mode biasa: titik apa adanya, tanpa cluster
                // (semua SLS = titik kecil, 1 SLS dipilih = marker bernomor)
                bangunanLayer = L.geoJSON({ type: 'FeatureCollection', features: fitur }, {
                    pointToLayer: (feature, latlng) => {
                        const status = feature.properties.status_kondisi;

                        if (! slsTerpilih) {
                            return L.circleMarker(latlng, {
                                renderer: canvasTitik, radius: 5, weight: 1, color: '#fff',
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

        // ================= EVENT-EVENT =================
        document.getElementById('filterKegiatan').addEventListener('change', gantiKegiatan);

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

        document.getElementById('resetSls').addEventListener('click', () => {
            pilihDariList(null, '');
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

        ['qcHanyaLuar', 'qcHanyaGerombol'].forEach(id =>
            document.getElementById(id).addEventListener('change', () => {
                renderBangunan();
                zoomKeSls();
            }));

        aturToggleQc(null);   // kondisi awal: belum ada kegiatan dipilih
        muatSls();
    </script>
</x-app-layout>