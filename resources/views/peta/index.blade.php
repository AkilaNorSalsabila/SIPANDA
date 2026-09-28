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

        /* ===== Popup card ala WilkerStat ===== */
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
            flex: 1; text-align: center; padding: 7px 0; border-radius: 6px;
            font-size: 11px; font-weight: 700; text-decoration: none; font-family: sans-serif;
        }
        .pc-btn-primary   { background: #0d9488; color: #fff; }
        .pc-btn-secondary { background: #f1f5f9; color: #0f172a; }
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

    <script>
        const urlDataSls = @json(route('peta.data.sls'));
        const urlDataBangunan = @json(route('peta.data.bangunan'));
        const urlDetailBangunan = @json(url('/peta/bangunan'));

        const map = L.map('peta').setView([-2.5, 118], 5);

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

        const warnaStatus = { normal: '#3b82f6', di_luar_sls: '#ef4444', tanpa_sls: '#f59e0b' };

        function muatSls() {
            fetch(urlDataSls)
                .then(res => res.json())
                .then(geojson => {
                    if (slsLayer) map.removeLayer(slsLayer);

                    slsLayer = L.geoJSON(geojson, {
                        style: { color: '#ef4444', weight: 3, fillOpacity: 0.05 },
                        onEachFeature: (feature, layer) => {
                            const p = feature.properties;
                            layer.bindPopup(`
                                <div class="pc-title">${p.nama_sls}</div>
                                <div class="pc-row">Kode SLS: <b>${p.kode_sls}</b></div>
                                <div class="pc-row">${p.desa_kelurahan}, ${p.kecamatan}, ${p.kabupaten_kota}</div>
                                <hr class="pc-hr">
                                <div class="pc-row">Bangunan: <b>${p.jumlah_bangunan}</b></div>
                                <div class="pc-row">Jumlah KK: <b>${p.jumlah_kk}</b></div>
                                <div class="pc-row">Jumlah Usaha: <b>${p.jumlah_usaha}</b></div>
                            `);
                        },
                    });

                    if (document.getElementById('layerSls').checked) slsLayer.addTo(map);
                    if (slsLayer.getBounds().isValid()) map.fitBounds(slsLayer.getBounds(), { maxZoom: 18 });
                });
        }

        function popupBangunan(d) {
            const adaUsaha = d.usaha && d.usaha.length > 0;
            const adaRt = d.rumah_tangga && d.rumah_tangga.length > 0;

            const judul = adaUsaha
                ? d.usaha[0].nama_usaha
                : (adaRt ? d.rumah_tangga[0].nama_kepala_keluarga : `Bangunan #${d.nomor_bangunan ?? '-'}`);

            // ---- badges ----
            let badges = '';
            if (adaUsaha) {
                badges += `<span class="pc-badge pc-badge-usaha">USAHA${d.usaha[0].jenis_usaha ? ': ' + d.usaha[0].jenis_usaha.toUpperCase() : ''}</span>`;
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

            // ---- alamat & keterangan ----
            const alamat = d.sls_tercatat && d.sls_tercatat.nama_sls
                ? `${d.sls_tercatat.nama_sls}${d.sls_tercatat.desa_kelurahan ? ', ' + d.sls_tercatat.desa_kelurahan : ''}`
                : '-';

            const keterangan = adaUsaha ? (d.usaha[0].jenis_usaha ?? '-')
                : (adaRt ? `Kepala keluarga: ${d.rumah_tangga[0].nomor_kk ?? '-'}` : 'Belum diisi manual');

            // ---- kotak peringatan (kalau bermasalah) ----
            let warnBox = '';

            if (d.status_kondisi === 'di_luar_sls') {
                warnBox = `
                    <div class="pc-warn-box">
                        <div class="pc-warn-title">⚠ PERINGATAN: POSISI DI LUAR BATAS SLS</div>
                        <div class="pc-warn-row">Tercatat di data: <b>${d.sls_tercatat?.nama_sls ?? '-'} (ID: ${d.sls_tercatat?.kode_sls ?? '-'})</b></div>
                        <div class="pc-warn-row">Lokasi GPS riil berada di: <b>${d.sls_aktual ? d.sls_aktual.nama_sls + ' (ID: ' + d.sls_aktual.kode_sls + ')' : 'tidak ada SLS manapun yang cocok'}</b></div>
                        <div class="pc-warn-row">Jarak deviasi pergeseran: <b>~${d.jarak_meter ?? '?'} meter</b></div>
                        <div class="pc-reco">Saran rekomendasi: lakukan geotagging ulang di lokasi ini.</div>
                    </div>`;
            } else if (d.status_kondisi === 'tanpa_sls') {
                warnBox = `
                    <div class="pc-warn-box">
                        <div class="pc-warn-title">⚠ PERINGATAN: KODE SLS TIDAK DITEMUKAN</div>
                        <div class="pc-warn-row">Kode SLS pada data: <b>${d.sls_tercatat?.kode_sls ?? '-'}</b></div>
                        <div class="pc-warn-row">Kode ini belum ada di database batas SLS.</div>
                        <div class="pc-reco">Saran rekomendasi: cek apakah batas SLS wilayah ini sudah diimport.</div>
                    </div>`;
            }

            const gmaps = `https://www.google.com/maps?q=${d.latitude},${d.longitude}`;
            const gnav = `https://www.google.com/maps/dir/?api=1&destination=${d.latitude},${d.longitude}`;

            return `
                <div style="min-width:250px; max-width:280px">
                    <div class="pc-badges">${badges}</div>
                    <div class="pc-title">${judul}</div>
                    <div class="pc-row">Nomor Bangunan: <b>${d.nomor_bangunan ?? '-'}</b></div>
                    <div class="pc-row">Alamat: <b>${alamat}</b></div>
                    <div class="pc-row">Keterangan: <b>${keterangan}</b></div>
                    <div class="pc-row">ID SLS Tercatat: <b>${d.sls_tercatat?.kode_sls ?? '-'}</b></div>
                    <div class="pc-row">Jumlah KK: <b>${d.jumlah_kk}</b> &nbsp;|&nbsp; Jumlah Usaha: <b>${d.jumlah_usaha}</b></div>
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

        function muatBangunan() {
            const kegiatanId = document.getElementById('filterKegiatan').value;

            if (bangunanLayer) {
                map.removeLayer(bangunanLayer);
                bangunanLayer = null;
            }

            document.getElementById('ringkasan').classList.add('hidden');
            if (! kegiatanId) return;

            fetch(`${urlDataBangunan}?kegiatan_id=${kegiatanId}`)
                .then(res => res.json())
                .then(geojson => {
                    let cNormal = 0, cLuar = 0, cTanpa = 0;

                    bangunanLayer = L.geoJSON(geojson, {
                        pointToLayer: (feature, latlng) => {
                            const status = feature.properties.status_kondisi;
                            if (status === 'normal') cNormal++;
                            if (status === 'di_luar_sls') cLuar++;
                            if (status === 'tanpa_sls') cTanpa++;

                            return L.marker(latlng, { icon: iconBernomor(feature.properties.nomor_bangunan, status) });
                        },
                        onEachFeature: (feature, layer) => {
                            layer.on('click', () => {
                                fetch(`${urlDetailBangunan}/${feature.properties.id}`)
                                    .then(res => res.json())
                                    .then(detail => layer.bindPopup(popupBangunan(detail)).openPopup());
                            });
                        },
                    });

                    if (document.getElementById('layerBangunan').checked) bangunanLayer.addTo(map);

                    document.getElementById('rTotal').textContent = cNormal + cLuar + cTanpa;
                    document.getElementById('rNormal').textContent = cNormal;
                    document.getElementById('rLuar').textContent = cLuar;
                    document.getElementById('rTanpa').textContent = cTanpa;
                    document.getElementById('ringkasan').classList.remove('hidden');
                });
        }

        document.getElementById('filterKegiatan').addEventListener('change', muatBangunan);
        document.getElementById('layerSls').addEventListener('change', (e) => {
            if (! slsLayer) return;
            e.target.checked ? slsLayer.addTo(map) : map.removeLayer(slsLayer);
        });
        document.getElementById('layerBangunan').addEventListener('change', (e) => {
            if (! bangunanLayer) return;
            e.target.checked ? bangunanLayer.addTo(map) : map.removeLayer(bangunanLayer);
        });

        muatSls();
    </script>
</x-app-layout>
