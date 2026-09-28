<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">Peta</h2>
    </x-slot>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

    <style>
        .marker-nomor {
            display: flex; align-items: center; justify-content: center;
            width: 26px; height: 26px; border-radius: 50%;
            border: 2px solid #fff; box-shadow: 0 1px 4px rgba(0,0,0,.5);
            color: #fff; font-size: 11px; font-weight: 700; font-family: sans-serif;
        }

        /* ===== Popup card ===== */
        .leaflet-popup-content { margin: 12px 14px; }
        .pc-badges { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 8px; }
        .pc-badge {
            padding: 3px 10px; border-radius: 9999px; font-size: 10px;
            font-weight: 700; font-family: sans-serif; white-space: nowrap;
        }
        .pc-badge-usaha  { background: #ecfdf5; color: #059669; }
        .pc-badge-ok     { background: #eff6ff; color: #2563eb; }
        .pc-badge-warn   { background: #fee2e2; color: #dc2626; }
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
        .pc-actions { margin-top: 10px; display: flex; gap: 8px; }
        .pc-btn {
            flex: 1; text-align: center; padding: 7px 0; border-radius: 6px; border: 0; cursor: pointer;
            font-size: 11px; font-weight: 700; text-decoration: none; font-family: sans-serif;
        }
        .pc-btn-primary   { background: #0d9488; color: #fff; }
        .pc-btn-secondary { background: #f1f5f9; color: #0f172a; }

        /* ===== Popup statistik SLS ===== */
        .st-title { font-size: 14px; font-weight: 800; color: #2563eb; text-transform: uppercase; line-height: 1.25; font-family: sans-serif; }
        .st-sub { font-size: 11px; color: #64748b; text-transform: uppercase; margin-top: 2px; font-family: sans-serif; }
        .st-id { font-size: 11px; color: #64748b; margin-bottom: 8px; font-family: sans-serif; }
        .st-row { display: flex; justify-content: space-between; font-size: 12px; font-weight: 600; margin-bottom: 4px; font-family: sans-serif; }
        .st-row span:last-child { font-weight: 800; }
        .c-hitam { color: #0f172a; } .c-hijau { color: #10b981; } .c-merah { color: #ef4444; }
        .c-oranye { color: #f59e0b; } .c-pink { color: #ec4899; } .c-teal { color: #14b8a6; }
        .c-biru { color: #3b82f6; } .c-ungu { color: #8b5cf6; }
    </style>

    <div class="py-6">
        <div class="max-w-[1600px] mx-auto sm:px-6 lg:px-8">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="grid grid-cols-1 lg:grid-cols-[280px_1fr]">

                    {{-- ================= SIDEBAR FILTER ================= --}}
                    <div class="border-b lg:border-b-0 lg:border-r border-slate-200 p-5 space-y-6">

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
                            <select id="filterSls" disabled
                                class="w-full text-sm rounded-lg border-slate-300 focus:ring-teal-500 focus:border-teal-500 disabled:bg-slate-100 disabled:text-slate-400">
                                <option value="">-- Semua SLS --</option>
                            </select>
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
                            </div>
                        </div>

                        <div id="ringkasan" class="hidden pt-4 border-t border-slate-100 text-xs text-slate-600 space-y-1">
                            <p><b id="rTotal">0</b> total bangunan</p>
                            <p><b id="rNormal">0</b> normal</p>
                            <p><b id="rLuar">0</b> di luar SLS</p>
                            <p><b id="rTanpa">0</b> tanpa SLS</p>
                        </div>
                    </div>

                    {{-- ================= PETA ================= --}}
                    <div class="relative">
                        <div id="peta" class="w-full" style="height: 78vh;"></div>
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
                            <th class="px-3 py-2 text-left">Nomor Urut Rumah Tangga</th>
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

    <script>
        const urlDataSls = @json(route('peta.data.sls'));
        const urlDataBangunan = @json(route('peta.data.bangunan'));
        const urlDetailBangunan = @json(url('/peta/bangunan'));
        const urlStatistikSls = @json(url('/peta/sls'));

        const map = L.map('peta').setView([-2.5, 118], 5);

        // Escape teks dari database sebelum masuk ke HTML popup
        function esc(v) {
            if (v === null || v === undefined || v === '') return '-';
            return String(v).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
        }

        // ================= BASEMAP =================
        const basemapSatelit = L.tileLayer(
            'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
            { maxZoom: 20, attribution: 'Tiles &copy; Esri — Source: Esri, Maxar, Earthstar Geographics, and the GIS User Community' }
        );

        const labelJalan = L.tileLayer(
            'https://server.arcgisonline.com/ArcGIS/rest/services/Reference/World_Boundaries_and_Places/MapServer/tile/{z}/{y}/{x}',
            { maxZoom: 20, attribution: 'Labels &copy; Esri' }
        );

        const basemapJalan = L.tileLayer(
            'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            { maxZoom: 20, attribution: '&copy; OpenStreetMap contributors' }
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

        // ================= LAYER DATA =================
        let slsLayer = null;
        let bangunanLayer = null;
        let dataDetailSls = null; // cache hasil statistik SLS terakhir untuk modal

        const warnaStatus = { normal: '#3b82f6', di_luar_sls: '#ef4444', tanpa_sls: '#f59e0b' };

        // ---------- Popup statistik SLS (klik area SLS) ----------
        function popupStatistikSls(res) {
            const s = res.sls;
            const t = res.statistik;

            return `
                <div style="min-width:240px; max-width:270px">
                    <div class="st-title">${esc(s.nama_sls)}</div>
                    <div class="st-sub">${esc(s.desa_kelurahan)}, ${esc(s.kecamatan)}</div>
                    <div class="st-id">ID SLS: ${esc(s.kode_sls)}</div>
                    <hr class="pc-hr">
                    <div class="st-row c-hitam"><span>Total Bangunan:</span><span>${t.total_bangunan}</span></div>
                    <div class="st-row c-teal"><span>Jumlah Usaha:</span><span>${t.jumlah_usaha}</span></div>
                    <div class="st-row c-biru"><span>Titik Keluarga (KK):</span><span>${t.keluarga_kk}</span></div>
                    <div class="st-row c-ungu"><span>Titik Bangunan Lainnya:</span><span>${t.bangunan_lainnya}</span></div>
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
                `${sls.desa_kelurahan}, ${sls.kecamatan} • ID SLS ${sls.kode_sls} • ${statistik.total_bangunan} bangunan`;

            document.getElementById('modalBody').innerHTML = daftar.length
                ? daftar.map(r => `
                    <tr>
                        <td class="px-3 py-2 font-semibold">${esc(r.nomor_bangunan)}</td>
                        <td class="px-3 py-2">${esc(r.nomor_urut_rt)}</td>
                        <td class="px-3 py-2">${esc(r.nama_usaha)}</td>
                        <td class="px-3 py-2">${esc(r.nama_keluarga)}</td>
                        <td class="px-3 py-2">${esc(r.nama_rumah_tangga)}</td>
                    </tr>`).join('')
                : `<tr><td colspan="5" class="px-3 py-6 text-center text-slate-400">Belum ada bangunan di SLS ini${document.getElementById('filterKegiatan').value ? '' : ' (pilih kegiatan dulu)'}.</td></tr>`;

            document.getElementById('modalStatistik').classList.remove('hidden');
        }

        document.getElementById('modalTutup').addEventListener('click', () => {
            document.getElementById('modalStatistik').classList.add('hidden');
        });
        document.getElementById('modalStatistik').addEventListener('click', (e) => {
            if (e.target.id === 'modalStatistik') e.target.classList.add('hidden');
        });

        const styleSls = { color: '#ef4444', weight: 3, fillOpacity: 0.05 };

        let slsGeojson = null;     // semua batas SLS dari server (belum tentu digambar)
        let slsTerpilih = null;    // id SLS yang dipilih di dropdown (null = semua)
        const slsIndex = {};       // id SLS -> layer polygon yang sedang digambar

        function kegiatanDipilih() {
            return document.getElementById('filterKegiatan').value;
        }

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

        // Batas SLS (merah) HANYA digambar kalau kegiatan sudah dipilih.
        // Kalau ada SLS terpilih, yang digambar cuma batas SLS itu.
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

        function zoomKeSls() {
            if (slsLayer && slsLayer.getBounds().isValid()) {
                map.fitBounds(slsLayer.getBounds(), { maxZoom: slsTerpilih ? 19 : 18, padding: [30, 30] });
            }
        }

        // Pilih 1 SLS (id) atau null untuk semua SLS
        function pilihSls(id) {
            slsTerpilih = id;
            map.closePopup();
            renderSls();
            zoomKeSls();
            renderBangunan();
        }

        // Saat kegiatan diganti: aktifkan/nonaktifkan dropdown SLS, gambar batas SLS
        function gantiKegiatan() {
            const ada = !! kegiatanDipilih();
            const dropdown = document.getElementById('filterSls');

            dropdown.disabled = ! ada;
            document.getElementById('infoSls').classList.toggle('hidden', ada);

            if (! ada) {
                dropdown.value = '';
                slsTerpilih = null;
            }

            map.closePopup();
            renderSls();
            if (ada) zoomKeSls();
            muatBangunan();
        }

        // Ambil batas SLS dari server (hanya untuk mengisi dropdown, belum digambar)
        function muatSls() {
            fetch(urlDataSls)
                .then(res => res.json())
                .then(geojson => {
                    slsGeojson = geojson;

                    const dropdown = document.getElementById('filterSls');
                    dropdown.innerHTML = '<option value="">-- Semua SLS --</option>';

                    geojson.features
                        .map(f => f.properties)
                        .sort((x, y) => String(x.kode_sls).localeCompare(String(y.kode_sls)))
                        .forEach(p => {
                            const opt = document.createElement('option');
                            opt.value = p.id;
                            opt.textContent = `${p.kode_sls} — ${p.nama_sls} (${p.desa_kelurahan})`;
                            dropdown.appendChild(opt);
                        });

                    document.getElementById('jumlahSls').textContent = geojson.features.length;

                    // kalau kegiatan sudah terpilih sebelum data SLS selesai dimuat
                    renderSls();
                });
        }

        // ---------- Popup 1 bangunan (5 standar informasi) ----------
        function popupBangunan(d) {
            const adaUsaha = d.usaha && d.usaha.length > 0;
            const adaRt = d.rumah_tangga && d.rumah_tangga.length > 0;

            const judul = adaUsaha
                ? d.usaha[0].nama_usaha
                : (adaRt ? d.rumah_tangga[0].nama_keluarga : `Bangunan #${d.nomor_bangunan ?? '-'}`);

            // ---- badges ----
            let badges = '';
            if (adaUsaha) {
                badges += `<span class="pc-badge pc-badge-usaha">USAHA${d.usaha[0].jenis_usaha ? ': ' + esc(d.usaha[0].jenis_usaha).toUpperCase() : ''}</span>`;
            } else if (d.flag_bku) {
                badges += `<span class="pc-badge pc-badge-usaha">BANGUNAN USAHA</span>`;
            }

            if (d.status_kondisi === 'normal') {
                badges += `<span class="pc-badge pc-badge-ok">DALAM BATAS SLS</span>`;
            } else if (d.status_kondisi === 'di_luar_sls') {
                badges += `<span class="pc-badge pc-badge-warn">LUAR BATAS SLS</span>`;
            } else {
                badges += `<span class="pc-badge pc-badge-warn">SLS TIDAK DITEMUKAN</span>`;
            }

            // ---- alamat ----
            const alamat = d.sls_tercatat && d.sls_tercatat.nama_sls
                ? `${d.sls_tercatat.nama_sls}${d.sls_tercatat.desa_kelurahan ? ', ' + d.sls_tercatat.desa_kelurahan : ''}`
                : null;

            // ---- standar informasi: rumah tangga (no urut RT, nama keluarga, nama RT) ----
            const blokRt = adaRt
                ? d.rumah_tangga.map(rt => `
                    <div class="pc-sub">
                        <div class="pc-sub-title">Rumah Tangga</div>
                        <div class="pc-row">Nomor Urut Rumah Tangga: <b>${esc(rt.nomor_urut)}</b></div>
                        <div class="pc-row">Nama Kepala Keluarga (KK): <b>${esc(rt.nama_keluarga)}</b></div>
                        <div class="pc-row">Nama Kepala Rumah Tangga (KRT): <b>${esc(rt.nama_rumah_tangga)}</b></div>
                    </div>`).join('')
                : `<div class="pc-sub">
                        <div class="pc-sub-title">Rumah Tangga</div>
                        <div class="pc-row">Nomor Urut Rumah Tangga: <b>-</b></div>
                        <div class="pc-row">Nama Kepala Keluarga (KK): <b>-</b></div>
                        <div class="pc-row">Nama Kepala Rumah Tangga (KRT): <b>-</b></div>
                   </div>`;

            // ---- standar informasi: usaha (nama usaha) ----
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

            // ---- kotak peringatan (kalau bermasalah) ----
            let warnBox = '';

            if (d.status_kondisi === 'di_luar_sls') {
                warnBox = `
                    <div class="pc-warn-box">
                        <div class="pc-warn-title">⚠ PERINGATAN: POSISI DI LUAR BATAS SLS</div>
                        <div class="pc-warn-row">Tercatat di data: <b>${esc(d.sls_tercatat?.nama_sls)} (ID: ${esc(d.sls_tercatat?.kode_sls)})</b></div>
                        <div class="pc-warn-row">Lokasi GPS riil berada di: <b>${d.sls_aktual ? esc(d.sls_aktual.nama_sls) + ' (ID: ' + esc(d.sls_aktual.kode_sls) + ')' : 'tidak ada SLS manapun yang cocok'}</b></div>
                        <div class="pc-warn-row">Jarak deviasi pergeseran: <b>~${d.jarak_meter ?? '?'} meter</b></div>
                        <div class="pc-reco">Saran rekomendasi: lakukan geotagging ulang di lokasi ini.</div>
                    </div>`;
            } else if (d.status_kondisi === 'tanpa_sls') {
                warnBox = `
                    <div class="pc-warn-box">
                        <div class="pc-warn-title">⚠ PERINGATAN: KODE SLS TIDAK DITEMUKAN</div>
                        <div class="pc-warn-row">Kode SLS pada data: <b>${esc(d.sls_tercatat?.kode_sls)}</b></div>
                        <div class="pc-warn-row">Kode ini belum ada di database batas SLS.</div>
                        <div class="pc-reco">Saran rekomendasi: cek apakah batas SLS wilayah ini sudah diimport.</div>
                    </div>`;
            }

            const gmaps = `https://www.google.com/maps?q=${d.latitude},${d.longitude}`;
            const gnav = `https://www.google.com/maps/dir/?api=1&destination=${d.latitude},${d.longitude}`;

            return `
                <div style="min-width:250px; max-width:290px; max-height:340px; overflow-y:auto">
                    <div class="pc-badges">${badges}</div>
                    <div class="pc-title">${esc(judul)}</div>
                    <div class="pc-row">No. Urut Bangunan: <b>${esc(d.nomor_bangunan)}</b></div>
                    <div class="pc-row">Alamat: <b>${esc(alamat)}</b></div>
                    <div class="pc-row">ID SLS Tercatat: <b>${esc(d.sls_tercatat?.kode_sls)}</b></div>
                    <div class="pc-row">Jumlah Usaha: <b>${d.jumlah_usaha ?? 0}</b></div>
                    ${blokRt}
                    ${blokUsaha}
                    ${warnBox}
                    <div class="pc-actions">
                        <a href="${gmaps}" target="_blank" class="pc-btn pc-btn-secondary">📍 Lihat Maps</a>
                        <a href="${gnav}" target="_blank" class="pc-btn pc-btn-primary">🚗 Navigasi</a>
                    </div>
                </div>
            `;
        }

        function iconBernomor(nomor, status) {
            const warna = warnaStatus[status] ?? '#64748b';
            return L.divIcon({
                className: '',
                html: `<div class="marker-nomor" style="background:${warna}">${nomor ?? '?'}</div>`,
                iconSize: [26, 26],
                iconAnchor: [13, 13],
                popupAnchor: [0, -13],
            });
        }

        let dataBangunanGeojson = null;

        // Ambil data bangunan sesuai kegiatan
        function muatBangunan() {
            dataBangunanGeojson = null;
            renderBangunan(); // bersihkan marker & ringkasan lama

            const kegiatanId = kegiatanDipilih();
            if (! kegiatanId) return;

            fetch(`${urlDataBangunan}?kegiatan_id=${kegiatanId}`)
                .then(res => res.json())
                .then(geojson => {
                    dataBangunanGeojson = geojson;
                    renderBangunan();
                });
        }

        // Ringkasan selalu dihitung (seluruh kegiatan, atau SLS terpilih).
        // Titik bangunan HANYA digambar kalau SLS sudah dipilih.
        function renderBangunan() {
            if (bangunanLayer) {
                map.removeLayer(bangunanLayer);
                bangunanLayer = null;
            }

            document.getElementById('ringkasan').classList.add('hidden');
            if (! dataBangunanGeojson) return;

            const fitur = dataBangunanGeojson.features
                .filter(f => ! slsTerpilih || f.properties.sls_id == slsTerpilih);

            const hitung = (st) => fitur.filter(f => f.properties.status_kondisi === st).length;
            const cNormal = hitung('normal'), cLuar = hitung('di_luar_sls'), cTanpa = hitung('tanpa_sls');

            document.getElementById('rTotal').textContent = cNormal + cLuar + cTanpa;
            document.getElementById('rNormal').textContent = cNormal;
            document.getElementById('rLuar').textContent = cLuar;
            document.getElementById('rTanpa').textContent = cTanpa;
            document.getElementById('ringkasan').classList.remove('hidden');

            if (! slsTerpilih) return; // belum pilih SLS -> tanpa titik

            bangunanLayer = L.geoJSON({ type: 'FeatureCollection', features: fitur }, {
                pointToLayer: (feature, latlng) =>
                    L.marker(latlng, { icon: iconBernomor(feature.properties.nomor_bangunan, feature.properties.status_kondisi) }),
                onEachFeature: (feature, layer) => {
                    layer.on('click', () => {
                        fetch(`${urlDetailBangunan}/${feature.properties.id}`)
                            .then(res => res.json())
                            .then(detail => layer.bindPopup(popupBangunan(detail), { maxWidth: 320 }).openPopup());
                    });
                },
            });

            if (document.getElementById('layerBangunan').checked) bangunanLayer.addTo(map);
        }

        document.getElementById('filterKegiatan').addEventListener('change', gantiKegiatan);
        document.getElementById('layerSls').addEventListener('change', (e) => {
            if (! slsLayer) return;
            e.target.checked ? slsLayer.addTo(map) : map.removeLayer(slsLayer);
        });
        document.getElementById('layerBangunan').addEventListener('change', (e) => {
            if (! bangunanLayer) return;
            e.target.checked ? bangunanLayer.addTo(map) : map.removeLayer(bangunanLayer);
        });

        // ================= FILTER ID SLS =================
        document.getElementById('filterSls').addEventListener('change', (e) => {
            pilihSls(e.target.value ? Number(e.target.value) : null);
        });

        document.getElementById('resetSls').addEventListener('click', () => {
            document.getElementById('filterSls').value = '';
            pilihSls(null);
        });

        muatSls();
    </script>
</x-app-layout>
