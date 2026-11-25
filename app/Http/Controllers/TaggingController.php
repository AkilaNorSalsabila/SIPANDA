<?php

namespace App\Http\Controllers;

use App\Models\Bangunan;
use App\Models\Kegiatan;
use App\Models\PenugasanSls;
use App\Models\Riwayat;
use App\Models\Sls;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Tagging titik bangunan lewat peta.
 *
 * Field yang disimpan SAMA dengan file titik_lokasi dari QGIS
 * (lihat ImportController::storeTitikLokasi), supaya hasil tagging bisa
 * diekspor kembali ke QGIS tanpa konversi:
 *
 *   id_assignment, idsls, no_bang, kode_bang_value, btt, bku, flag_repair, kk, usaha
 */
class TaggingController extends Controller
{
    /**
     * SLS yang ditugaskan ke akun yang sedang login (untuk panel "Penugasan Saya").
     * Admin tidak dibatasi penugasan, jadi `bebas` = true dan daftarnya kosong.
     */
    public function penugasan(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->isAdmin()) {
            return response()->json(['bebas' => true, 'penugasan' => []]);
        }

        $rows = PenugasanSls::query()
            ->where('user_id', $user->id)
            ->join('sls', 'sls.id', '=', 'penugasan_sls.sls_id')
            ->join('kegiatan', 'kegiatan.id', '=', 'penugasan_sls.kegiatan_id')
            ->select(
                'penugasan_sls.kegiatan_id',
                'kegiatan.nama_kegiatan',
                'sls.id as sls_id',
                'sls.id_sls',
                'sls.nama_sls',
                'sls.kecamatan',
                'sls.desa_kelurahan'
            )
            ->selectRaw('(sls.area IS NOT NULL) as punya_polygon')
            ->selectRaw('(SELECT COUNT(*) FROM bangunan b WHERE b.sls_id = sls.id AND b.kegiatan_id = penugasan_sls.kegiatan_id) as jumlah_titik')
            ->selectRaw("(SELECT COUNT(*) FROM bangunan b WHERE b.sls_id = sls.id AND b.kegiatan_id = penugasan_sls.kegiatan_id AND b.sumber = 'tagging' AND b.dibuat_oleh = ?) as titik_saya", [$user->id])
            ->orderBy('kegiatan.nama_kegiatan')
            ->orderBy('sls.id_sls')
            ->get();

        return response()->json(['bebas' => false, 'penugasan' => $rows]);
    }

    /** Usulan nomor bangunan berikutnya (nomor terbesar di SLS itu + 1). */
    public function nomorBerikutnya(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kegiatan_id' => ['required', 'exists:kegiatan,id'],
            'sls_id'      => ['required', 'exists:sls,id'],
        ]);

        $this->pastikanBolehTagging($request, (int) $data['kegiatan_id'], (int) $data['sls_id']);

        $terbesar = Bangunan::where('kegiatan_id', $data['kegiatan_id'])
            ->where('sls_id', $data['sls_id'])
            ->pluck('nomor_bangunan')
            ->filter(fn ($n) => ctype_digit((string) $n))
            ->map(fn ($n) => (int) $n)
            ->max();

        return response()->json(['nomor' => ($terbesar ?? 0) + 1]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'kegiatan_id' => ['required', 'exists:kegiatan,id'],
            'sls_id'      => ['required', 'exists:sls,id'],
            'latitude'    => ['required', 'numeric', 'between:-90,90'],
            'longitude'   => ['required', 'numeric', 'between:-180,180'],
            // Cara titik diambil: 'gps' (petugas di lokasi) atau 'peta' (klik peta, hanya admin).
            'sumber_tag'  => ['required', 'in:gps,peta'],
            'akurasi_gps' => ['nullable', 'numeric', 'min:0', 'max:100000'],
            ...$this->aturanAtribut(),
        ], [], $this->namaAtribut());

        $kegiatanId = (int) $data['kegiatan_id'];
        $sls = Sls::findOrFail($data['sls_id']);

        // SLS adalah snapshot per kegiatan: titik hanya boleh ditaruh di SLS milik kegiatannya.
        abort_if((int) $sls->kegiatan_id !== $kegiatanId, 422, 'SLS ini bukan milik kegiatan yang dipilih.');

        $this->pastikanBolehTagging($request, $kegiatanId, $sls->id);

        // Mitra wajib memakai GPS di lokasi bangunan, dengan akurasi yang cukup baik.
        $pakaiGps = $data['sumber_tag'] === 'gps';
        $akurasi  = $pakaiGps && isset($data['akurasi_gps']) ? round((float) $data['akurasi_gps'], 2) : null;
        $batas    = (float) config('tagging.akurasi_maks_m', 100);

        if (! $request->user()->isAdmin()) {
            if (! $pakaiGps) {
                return $this->gagal('Titik harus diambil lewat GPS di lokasi bangunan.', 'sumber_tag');
            }
            if ($akurasi === null) {
                return $this->gagal('Akurasi GPS tidak terbaca. Ambil lokasi ulang.', 'akurasi_gps');
            }
            if ($akurasi > $batas) {
                return $this->gagal("Akurasi GPS ±{$akurasi} m melebihi batas {$batas} m. Tunggu sinyal lebih baik atau pindah ke area terbuka.", 'akurasi_gps');
            }
        }

        $metode = $pakaiGps ? 'GPS' . ($akurasi !== null ? " ±{$akurasi} m" : '') : 'klik peta';

        $lat = (float) $data['latitude'];
        $lng = (float) $data['longitude'];

        $dalam = $this->posisiDiDalamSls($sls->id, $lat, $lng);
        if ($dalam === false) {
            return $this->gagal('Titik berada di luar batas SLS ' . $sls->id_sls . '. Letakkan titik di dalam batas SLS yang ditugaskan.', 'latitude');
        }

        // Tanpa polygon, posisi titik tidak bisa diperiksa. Hanya admin yang boleh tetap menyimpan.
        if ($dalam === null && ! $request->user()->isAdmin()) {
            return $this->gagal('SLS ' . $sls->id_sls . ' belum punya batas polygon, jadi titik belum dapat diverifikasi. Hubungi admin.', 'sls_id');
        }

        if ($this->nomorSudahDipakai($kegiatanId, $sls->id, $data['nomor_bangunan'])) {
            return $this->gagal("Nomor bangunan {$data['nomor_bangunan']} sudah dipakai di SLS ini.", 'nomor_bangunan');
        }

        $bangunan = DB::transaction(function () use ($request, $data, $sls, $kegiatanId, $lat, $lng, $dalam, $pakaiGps, $akurasi) {
            // Dua petugas di SLS yang sama bisa menyimpan nomor yang sama hampir bersamaan: kunci per SLS,
            // lalu periksa ulang nomor di dalam transaksi (pemeriksaan di atas hanya penyaring awal).
            DB::select('SELECT pg_advisory_xact_lock(?)', [$sls->id]);
            if ($this->nomorSudahDipakai($kegiatanId, $sls->id, $data['nomor_bangunan'])) {
                throw ValidationException::withMessages([
                    'nomor_bangunan' => "Nomor bangunan {$data['nomor_bangunan']} sudah dipakai di SLS ini.",
                ]);
            }

            $bangunan = Bangunan::create([
                'kegiatan_id'     => $kegiatanId,
                'sls_id'          => $sls->id,
                'id_sls_asal'     => $sls->id_sls,
                // Prefix TAG- membedakan dari id_assignment bawaan QGIS; unik & aman dari tabrakan saat diimpor ulang.
                'id_assignment'   => 'TAG-' . Str::ulid(),
                'di_luar_sls'     => $dalam === null ? null : false,   // null = SLS belum punya polygon
                'status'          => 'terdata',
                'sumber'          => 'tagging',
                'dibuat_oleh'     => $request->user()->id,
                'sumber_tag'      => $data['sumber_tag'],
                'akurasi_gps'     => $pakaiGps ? $akurasi : null,
                'ditandai_at'     => now(),
                ...$this->atributBangunan($request, $data),
            ]);

            $bangunan->setKoordinat($lat, $lng);
            $this->simpanDetail($bangunan, $data);

            return $bangunan;
        });

        $kegiatan = Kegiatan::find($kegiatanId);
        Riwayat::catat(
            modul: 'tagging',
            aksi: 'tambah',
            keterangan: "Men-tag bangunan no. {$bangunan->nomor_bangunan} di SLS {$sls->id_sls} ({$kegiatan?->nama_kegiatan}) — {$metode}",
            objekId: $bangunan->id,
            objekLabel: "Bangunan {$bangunan->nomor_bangunan} / SLS {$sls->id_sls}",
            baru: $this->ringkasan($bangunan),
        );

        return response()->json([
            'pesan'   => "Titik bangunan no. {$bangunan->nomor_bangunan} tersimpan.",
            'fitur'   => $this->fiturPeta($bangunan->fresh()),
        ], 201);
    }

    public function update(Request $request, Bangunan $bangunan): JsonResponse
    {
        abort_unless($bangunan->bolehDiubahOleh($request->user()), 403, 'Titik ini tidak dapat Anda ubah.');

        $data = $request->validate($this->aturanAtribut(), [], $this->namaAtribut());

        // Hanya diperiksa kalau nomornya DIUBAH: data import bisa memuat no_bang kembar di satu SLS, dan itu
        // tidak boleh menghalangi petugas menyimpan perbaikan atribut lain pada titik tersebut.
        $nomorBerubah = (string) $data['nomor_bangunan'] !== (string) $bangunan->nomor_bangunan;
        if ($nomorBerubah && $this->nomorSudahDipakai((int) $bangunan->kegiatan_id, (int) $bangunan->sls_id, $data['nomor_bangunan'], $bangunan->id)) {
            return $this->gagal("Nomor bangunan {$data['nomor_bangunan']} sudah dipakai titik lain di SLS ini. Gunakan nomor yang berbeda.", 'nomor_bangunan');
        }

        $lama = $this->ringkasan($bangunan);
        $titikImport = $bangunan->sumber !== 'tagging';
        $pengubah = $request->user();

        DB::transaction(function () use ($bangunan, $request, $data, $titikImport, $pengubah) {
            $atribut = $this->atributBangunan($request, $data);
            if ($titikImport) {
                // Koreksi titik import: tandai sudah diperbaiki; import ulang akan melewati titik ini.
                $statusLama = $bangunan->status_perbaikan ?? Bangunan::STATUS_NORMAL;
                $atribut = array_merge($atribut, $this->atributKoreksi($pengubah));

                // Titik yang tadinya tidak bermasalah: isian tetap dicatat & dilindungi dari import ulang
                // (diedit_manual_at / dikoreksi_*), tetapi tidak masuk antrean verifikasi admin.
                if ($statusLama === Bangunan::STATUS_NORMAL) {
                    $atribut['status_perbaikan']  = Bangunan::STATUS_NORMAL;
                    $atribut['diverifikasi_oleh'] = null;
                    $atribut['diverifikasi_at']   = null;
                }

                // [#1] Mengubah atribut tidak menyelesaikan masalah lokasi / flag_repair. Selama titik masih
                // di luar batas SLS atau flag_repair masih dicentang, statusnya tetap "perlu perbaikan".
                // Penanda koreksi (dikoreksi_*, diedit_manual_at) tetap terisi, jadi import ulang
                // tetap melewati titik ini dan isian petugas tidak tertimpa.
                if ($bangunan->di_luar_sls === true || ! empty($atribut['flag_repair'])) {
                    $atribut['status_perbaikan']  = Bangunan::STATUS_PERLU;
                    $atribut['diverifikasi_oleh'] = null;
                    $atribut['diverifikasi_at']   = null;
                }
            }
            $bangunan->update($atribut);

            // Daftar usaha/KK diganti seluruhnya bila dikirim dari form.
            if (array_key_exists('usaha', $data) || array_key_exists('keluarga', $data)) {
                $this->simpanDetail($bangunan, $data);
            }
        });

        if (! $titikImport) {
            $bangunan->sinkronStatusPerbaikan();   // flag_repair bisa berubah dari form
        }

        Riwayat::catat(
            modul: 'tagging',
            aksi: 'ubah',
            keterangan: ($titikImport ? 'Mengoreksi' : 'Mengubah') . " titik bangunan no. {$bangunan->nomor_bangunan} (SLS {$bangunan->id_sls_asal})"
                . ($titikImport ? ' — titik hasil import, status: ' . $this->labelStatus($bangunan->status_perbaikan) : ''),
            objekId: $bangunan->id,
            objekLabel: "Bangunan {$bangunan->nomor_bangunan} / SLS {$bangunan->id_sls_asal}",
            lama: $lama,
            baru: $this->ringkasan($bangunan),
        );

        return response()->json([
            'pesan' => 'Perubahan tersimpan.',
            'fitur' => $this->fiturPeta($bangunan->fresh()),
        ]);
    }

    /**
     * Ambil ulang lokasi titik (koreksi koordinat): petugas datang lagi ke bangunan lalu ambil GPS.
     *
     * - Petugas wajib memakai GPS di lokasi (akurasi dibatasi), admin boleh klik peta.
     * - Posisi baru harus di dalam polygon SLS titik itu (admin boleh kalau SLS belum punya polygon).
     * - Untuk titik import, koordinat awal dari file disimpan sekali di latitude_asal/longitude_asal.
     * - Status_perbaikan menjadi sudah_diperbaiki (petugas) atau terverifikasi (admin);
     *   di_luar_sls dihitung ulang sehingga penanda perbaikan lepas sendiri.
     */
    public function geser(Request $request, Bangunan $bangunan): JsonResponse
    {
        $user = $request->user();
        abort_unless($bangunan->bolehDiubahOleh($user), 403, 'Lokasi titik ini tidak dapat Anda perbarui.');

        $data = $request->validate([
            'latitude'    => ['required', 'numeric', 'between:-90,90'],
            'longitude'   => ['required', 'numeric', 'between:-180,180'],
            'sumber_tag'  => ['required', 'in:gps,peta'],
            'akurasi_gps' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        if (! $bangunan->sls_id) {
            return $this->gagal('Titik ini belum terhubung ke SLS, jadi lokasinya belum bisa diperbarui. Hubungi admin.', 'sls_id');
        }

        $pakaiGps = $data['sumber_tag'] === 'gps';
        $akurasi  = $pakaiGps && isset($data['akurasi_gps']) ? round((float) $data['akurasi_gps'], 2) : null;
        $batas    = (float) config('tagging.akurasi_maks_m', 100);

        if (! $user->isAdmin()) {
            if (! $pakaiGps) {
                return $this->gagal('Posisi baru harus diambil lewat GPS di lokasi bangunan.', 'sumber_tag');
            }
            if ($akurasi === null) {
                return $this->gagal('Akurasi GPS tidak terbaca. Ambil lokasi ulang.', 'akurasi_gps');
            }
            if ($akurasi > $batas) {
                return $this->gagal("Akurasi GPS ±{$akurasi} m melebihi batas {$batas} m. Tunggu sinyal lebih baik atau pindah ke area terbuka.", 'akurasi_gps');
            }
        }

        $lat = (float) $data['latitude'];
        $lng = (float) $data['longitude'];

        $dalam = $this->posisiDiDalamSls((int) $bangunan->sls_id, $lat, $lng);
        if ($dalam === false) {
            return $this->gagal('Posisi baru berada di luar batas SLS ' . ($bangunan->id_sls_asal ?? '') . '. Pilih posisi di dalam batas SLS.', 'latitude');
        }
        if ($dalam === null && ! $user->isAdmin()) {
            return $this->gagal('SLS ini belum punya batas polygon, jadi posisi baru belum dapat diverifikasi. Hubungi admin.', 'sls_id');
        }

        $latLama = $bangunan->latitude !== null ? (float) $bangunan->latitude : null;
        $lngLama = $bangunan->longitude !== null ? (float) $bangunan->longitude : null;
        $jarak = ($latLama !== null && $lngLama !== null) ? round($this->jarakMeter($latLama, $lngLama, $lat, $lng), 1) : null;
        $statusLama = $bangunan->status_perbaikan;
        $titikImport = $bangunan->sumber !== 'tagging';

        DB::transaction(function () use ($bangunan, $user, $lat, $lng, $dalam, $pakaiGps, $akurasi, $latLama, $lngLama, $titikImport, $data) {
            $isi = [
                'di_luar_sls' => $dalam === null ? null : false,
                'sumber_tag'  => $data['sumber_tag'],
                'akurasi_gps' => $pakaiGps ? $akurasi : null,
                'ditandai_at' => now(),
            ];

            if ($titikImport) {
                // Koordinat awal dari file disimpan sekali saja, tidak ikut berubah pada koreksi berikutnya.
                if ($bangunan->latitude_asal === null && $latLama !== null) {
                    $isi['latitude_asal'] = $latLama;
                    $isi['longitude_asal'] = $lngLama;
                }
                $isi = array_merge($isi, $this->atributKoreksi($user));

                // Admin yang memperbaiki lokasi = langsung terverifikasi; tanda flag_repair dipadamkan supaya
                // tidak terekspor ke QGIS sebagai flag_repair=1 (sama seperti saat verifikasi()).
                if ($user->isAdmin()) {
                    $isi['flag_repair'] = false;
                }
            }

            $bangunan->fill($isi);
            $bangunan->setKoordinat($lat, $lng);   // menyimpan atribut di atas + kolom geometry
        });

        if (! $titikImport) {
            $bangunan->sinkronStatusPerbaikan();
        }

        $bangunan->refresh();

        Riwayat::catat(
            modul: 'tagging',
            aksi: 'ubah',
            keterangan: "Mengambil ulang lokasi titik bangunan no. {$bangunan->nomor_bangunan} (SLS {$bangunan->id_sls_asal})"
                . ($jarak !== null ? " sejauh ~{$jarak} m" : '')
                . ' — ' . ($pakaiGps ? 'GPS' . ($akurasi !== null ? " ±{$akurasi} m" : '') : 'klik peta'),
            objekId: $bangunan->id,
            objekLabel: "Bangunan {$bangunan->nomor_bangunan} / SLS {$bangunan->id_sls_asal}",
            lama: ['latitude' => $latLama, 'longitude' => $lngLama, 'status_perbaikan' => $this->labelStatus($statusLama)],
            baru: ['latitude' => $lat, 'longitude' => $lng, 'status_perbaikan' => $this->labelStatus($bangunan->status_perbaikan)],
        );

        return response()->json([
            'pesan' => 'Lokasi titik diperbarui' . ($jarak !== null ? " (~{$jarak} m)" : '') . '. ' . ($titikImport && ! $user->isAdmin() ? 'Menunggu verifikasi admin.' : ''),
            'fitur' => $this->fiturPeta($bangunan),
        ]);
    }

    /** Admin: koreksi petugas dinyatakan benar -> terverifikasi. */
    public function verifikasi(Request $request, Bangunan $bangunan): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'Hanya admin yang dapat memverifikasi.');

        if ($bangunan->status_perbaikan !== Bangunan::STATUS_SUDAH) {
            return $this->gagal('Hanya titik berstatus "sudah diperbaiki" yang dapat diverifikasi.', 'status_perbaikan');
        }

        $bangunan->update([
            'status_perbaikan'  => Bangunan::STATUS_TERVERIFIKASI,
            'diverifikasi_oleh' => $request->user()->id,
            'diverifikasi_at'   => now(),
            // [#7] Koreksi sudah dinyatakan benar: tanda "perlu perbaikan" (termasuk yang dinyalakan
            // saat ditolak) dipadamkan supaya tidak terekspor ke QGIS sebagai flag_repair=1.
            'flag_repair'       => false,
            'alasan_tolak'      => null,
        ]);

        Riwayat::catat(
            modul: 'tagging',
            aksi: 'setujui',
            keterangan: "Memverifikasi koreksi titik bangunan no. {$bangunan->nomor_bangunan} (SLS {$bangunan->id_sls_asal})",
            objekId: $bangunan->id,
            objekLabel: "Bangunan {$bangunan->nomor_bangunan} / SLS {$bangunan->id_sls_asal}",
            lama: ['status_perbaikan' => $this->labelStatus(Bangunan::STATUS_SUDAH)],
            baru: ['status_perbaikan' => $this->labelStatus(Bangunan::STATUS_TERVERIFIKASI)],
        );

        return response()->json(['pesan' => 'Koreksi diverifikasi.', 'fitur' => $this->fiturPeta($bangunan->fresh())]);
    }

    /** Admin: koreksi petugas belum benar -> kembali perlu_perbaikan (flag_repair dinyalakan agar tetap tersorot). */
    public function tolak(Request $request, Bangunan $bangunan): JsonResponse
    {
        abort_unless($request->user()->isAdmin(), 403, 'Hanya admin yang dapat menolak koreksi.');

        $data = $request->validate(['alasan' => ['nullable', 'string', 'max:500']]);
        $alasan = filled($data['alasan'] ?? null) ? trim($data['alasan']) : null;

        if ($bangunan->status_perbaikan !== Bangunan::STATUS_SUDAH) {
            return $this->gagal('Hanya titik berstatus "sudah diperbaiki" yang dapat dikembalikan.', 'status_perbaikan');
        }

        $bangunan->update([
            'status_perbaikan'  => Bangunan::STATUS_PERLU,
            'flag_repair'       => true,
            'diverifikasi_oleh' => null,
            'diverifikasi_at'   => null,
            'alasan_tolak'      => $alasan,   // dibaca petugas di popup titik supaya tahu apa yang harus diperbaiki
        ]);

        Riwayat::catat(
            modul: 'tagging',
            aksi: 'tolak',
            keterangan: "Mengembalikan koreksi titik bangunan no. {$bangunan->nomor_bangunan} (SLS {$bangunan->id_sls_asal}) untuk diperbaiki lagi"
                . ($alasan ? " — alasan: {$alasan}" : ''),
            objekId: $bangunan->id,
            objekLabel: "Bangunan {$bangunan->nomor_bangunan} / SLS {$bangunan->id_sls_asal}",
            lama: ['status_perbaikan' => $this->labelStatus(Bangunan::STATUS_SUDAH)],
            baru: ['status_perbaikan' => $this->labelStatus(Bangunan::STATUS_PERLU), 'alasan_tolak' => $alasan],
        );

        return response()->json(['pesan' => 'Dikembalikan: titik perlu diperbaiki lagi.', 'fitur' => $this->fiturPeta($bangunan->fresh())]);
    }

    public function destroy(Request $request, Bangunan $bangunan): JsonResponse
    {
        abort_unless($bangunan->bolehDihapusOleh($request->user()), 403, 'Titik ini tidak dapat Anda hapus.');

        $label = "Bangunan {$bangunan->nomor_bangunan} / SLS {$bangunan->id_sls_asal}";
        $id = $bangunan->id;
        $lama = $this->ringkasan($bangunan);

        $bangunan->delete();

        Riwayat::catat(
            modul: 'tagging',
            aksi: 'hapus',
            keterangan: "Menghapus titik {$label}",
            objekId: $id,
            objekLabel: $label,
            lama: $lama,
        );

        return response()->json(['pesan' => 'Titik dihapus.', 'id' => $id]);
    }

    /**
     * Unduh titik (GeoJSON siap QGIS, atau CSV).
     *
     * Properti memakai nama kolom yang sama dengan file titik_lokasi QGIS yang diimpor ke aplikasi ini,
     * ditambah status_perbaikan dan koordinat awal (lat_asal / lon_asal) untuk titik yang dikoreksi.
     *   - petugas : hanya titik yang ia tag sendiri
     *   - admin   : cakupan=tagging (default), semua (termasuk titik import),
     *               koreksi (hanya titik yang sudah dikoreksi, untuk memperbarui sumber data di luar aplikasi)
     */
    public function export(Request $request): StreamedResponse
    {
        $request->validate([
            'kegiatan_id' => ['nullable', 'exists:kegiatan,id'],
            'sls_id'      => ['nullable', 'exists:sls,id'],
            'cakupan'     => ['nullable', 'in:tagging,semua,koreksi'],
            'pembuat'     => ['nullable', 'integer'],
            'format'      => ['nullable', 'in:geojson,csv'],
        ]);

        $user = $request->user();
        $cakupan = $request->input('cakupan', 'tagging');
        $csv = $request->input('format', 'geojson') === 'csv';

        $query = Bangunan::query()
            ->with(['sls:id,id_sls', 'dikoreksiOleh:id,name'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->when($request->filled('kegiatan_id'), fn ($q) => $q->where('kegiatan_id', $request->kegiatan_id))
            ->when($request->filled('sls_id'), fn ($q) => $q->where('sls_id', $request->sls_id));

        if ($user->isAdmin()) {
            if ($cakupan === 'koreksi') {
                $query->terkoreksi();
            } elseif ($cakupan !== 'semua') {
                $query->hasilTagging();
            }
            $query->when($request->filled('pembuat'), fn ($q) => $q->where('dibuat_oleh', (int) $request->pembuat));
        } else {
            $query->hasilTagging()->where('dibuat_oleh', $user->id);
            $cakupan = 'tagging';
        }

        $awalan = $cakupan === 'koreksi' ? 'titik_lokasi_koreksi_' : 'titik_lokasi_tagging_';
        $nama = $awalan . now()->format('Ymd_His') . ($csv ? '.csv' : '.geojson');

        if ($csv) {
            return response()->streamDownload(function () use ($query) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF");   // BOM supaya Excel membaca UTF-8
                $kepala = null;

                $query->chunkById(1000, function ($rows) use ($out, &$kepala) {
                    foreach ($rows as $b) {
                        $f = $this->fiturExport($b);
                        $baris = $f['properties'] + [
                            'longitude' => $f['geometry']['coordinates'][0],
                            'latitude'  => $f['geometry']['coordinates'][1],
                        ];
                        if ($kepala === null) {
                            $kepala = array_keys($baris);
                            fputcsv($out, $kepala);
                        }
                        fputcsv($out, array_values($baris));
                    }
                });

                fclose($out);
            }, $nama, ['Content-Type' => 'text/csv; charset=utf-8']);
        }

        return response()->streamDownload(function () use ($query) {
            echo '{"type":"FeatureCollection","name":"titik_lokasi",'
               . '"crs":{"type":"name","properties":{"name":"urn:ogc:def:crs:OGC:1.3:CRS84"}},"features":[';

            $pertama = true;
            $query->chunkById(1000, function ($rows) use (&$pertama) {
                foreach ($rows as $b) {
                    echo ($pertama ? '' : ',') . json_encode($this->fiturExport($b), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    $pertama = false;
                }
            });

            echo ']}';
        }, $nama, ['Content-Type' => 'application/geo+json; charset=utf-8']);
    }

    // ------------------------------------------------------------------
    // Helper
    // ------------------------------------------------------------------

    /** Penanda koreksi untuk titik hasil import (dipakai update() dan geser()). */
    private function atributKoreksi(\App\Models\User $user): array
    {
        $admin = $user->isAdmin();

        return [
            // Admin mengoreksi = langsung terverifikasi; petugas = menunggu verifikasi admin.
            'status_perbaikan'  => $admin ? Bangunan::STATUS_TERVERIFIKASI : Bangunan::STATUS_SUDAH,
            'dikoreksi_oleh'    => $user->id,
            'dikoreksi_at'      => now(),
            'diverifikasi_oleh' => $admin ? $user->id : null,
            'diverifikasi_at'   => $admin ? now() : null,
            'diedit_manual_at'  => now(),   // penanda lama; import ulang melewati titik ini kalau posisinya di file tidak berubah
            'alasan_tolak'      => null,    // koreksi baru: catatan penolakan sebelumnya sudah ditindaklanjuti
        ];
    }

    private function labelStatus(?string $status): string
    {
        return match ($status) {
            Bangunan::STATUS_PERLU          => 'Perlu perbaikan',
            Bangunan::STATUS_SUDAH          => 'Sudah diperbaiki',
            Bangunan::STATUS_TERVERIFIKASI  => 'Terverifikasi',
            default                         => 'Normal',
        };
    }

    /** Jarak dua koordinat dalam meter (haversine). */
    private function jarakMeter(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $r * asin(min(1, sqrt($a)));
    }

    private function aturanAtribut(): array
    {
        return [
            // no_bang: nomor urut bangunan di SLS. Umumnya angka; huruf/strip diizinkan karena data QGIS bisa memuat
            // nomor seperti "12A" dan titik import tidak boleh gagal disimpan hanya karena format nomornya.
            'nomor_bangunan'  => ['required', 'regex:/^[0-9A-Za-z\-]{1,10}$/'],
            'kode_bang_value' => ['nullable', 'regex:/^[0-9A-Za-z]{1,10}$/'],
            'flag_btt'        => ['nullable', 'boolean'],
            'flag_bku'        => ['nullable', 'boolean'],
            'flag_repair'     => ['nullable', 'boolean'],
            'jumlah_kk'       => ['required', 'integer', 'min:0', 'max:999'],
            'jumlah_usaha'    => ['required', 'integer', 'min:0', 'max:999'],

            // Detail usaha (opsional): tampil di popup peta admin seperti data import.
            'usaha'                             => ['nullable', 'array', 'max:50'],
            'usaha.*.nama_usaha'                => ['required', 'string', 'max:255'],
            'usaha.*.jenis_usaha'               => ['nullable', 'string', 'max:255'],

            // Detail keluarga / rumah tangga (opsional).
            'keluarga'                               => ['nullable', 'array', 'max:50'],
            'keluarga.*.nama_kepala_keluarga'        => ['required', 'string', 'max:255'],
            'keluarga.*.nomor_kk'                    => ['nullable', 'regex:/^\d{1,20}$/'],
            'keluarga.*.jumlah_anggota'              => ['nullable', 'integer', 'min:1', 'max:99'],
            'keluarga.*.nomor_urut_rumah_tangga'     => ['nullable', 'integer', 'min:1', 'max:999'],
            'keluarga.*.nama_kepala_rumah_tangga'    => ['nullable', 'string', 'max:255'],
        ];
    }

    private function namaAtribut(): array
    {
        return [
            'nomor_bangunan'                      => 'no. bangunan',
            'usaha.*.nama_usaha'                  => 'nama usaha',
            'usaha.*.jenis_usaha'                 => 'jenis usaha',
            'keluarga.*.nama_kepala_keluarga'     => 'nama kepala keluarga',
            'keluarga.*.nomor_kk'                 => 'nomor KK (hanya angka)',
            'keluarga.*.jumlah_anggota'           => 'jumlah anggota',
            'keluarga.*.nomor_urut_rumah_tangga'  => 'no. urut rumah tangga',
            'keluarga.*.nama_kepala_rumah_tangga' => 'nama kepala rumah tangga',
        ];
    }

    /** Ganti seluruh daftar usaha & keluarga bangunan dengan isian form. */
    private function simpanDetail(Bangunan $bangunan, array $data): void
    {
        // Kolom yang tidak ada di form dipertahankan (per urutan baris).
        $lamaRt = $bangunan->rumahTangga()->orderBy('id')->get()->values();

        $bangunan->usaha()->delete();
        $bangunan->rumahTangga()->delete();

        foreach (array_values($data['usaha'] ?? []) as $u) {
            $bangunan->usaha()->create([
                'nama_usaha'  => $u['nama_usaha'],
                'jenis_usaha' => $u['jenis_usaha'] ?? null,
            ]);
        }

        foreach (array_values($data['keluarga'] ?? []) as $i => $k) {
            $bangunan->rumahTangga()->create([
                'nomor_urut_keluarga'      => $i + 1,
                'nama_kepala_keluarga'     => $k['nama_kepala_keluarga'],
                'nomor_kk'                 => $k['nomor_kk'] ?? null,
                'jumlah_anggota'           => $k['jumlah_anggota'] ?? null,
                'nomor_urut_rumah_tangga'  => $k['nomor_urut_rumah_tangga'] ?? null,
                'nama_kepala_rumah_tangga' => $k['nama_kepala_rumah_tangga'] ?? null,
                'identifikasi_kk_krt'      => $lamaRt[$i]->identifikasi_kk_krt ?? null,
            ]);
        }
    }

    /** Kolom bangunan hasil isian form (dipakai bersama oleh store & update). */
    private function atributBangunan(Request $request, array $data): array
    {
        return [
            'nomor_bangunan'  => $data['nomor_bangunan'],
            'kode_bang_value' => $data['kode_bang_value'] ?? null,
            'flag_btt'        => $request->boolean('flag_btt'),
            'flag_bku'        => $request->boolean('flag_bku'),
            'flag_repair'     => $request->boolean('flag_repair'),
            // Jumlah tidak boleh lebih kecil dari daftar rinci yang diisi.
            'jumlah_kk'       => max((int) $data['jumlah_kk'], count($data['keluarga'] ?? [])),
            'jumlah_usaha'    => max((int) $data['jumlah_usaha'], count($data['usaha'] ?? [])),
        ];
    }

    private function ringkasan(Bangunan $b): array
    {
        return [
            'no_bang'         => $b->nomor_bangunan,
            'kode_bang_value' => $b->kode_bang_value,
            'btt'             => (bool) $b->flag_btt,
            'bku'             => (bool) $b->flag_bku,
            'flag_repair'     => (bool) $b->flag_repair,
            'kk'              => (int) $b->jumlah_kk,
            'usaha'           => (int) $b->jumlah_usaha,
            // Nama dicatat juga, supaya koreksi nama usaha/KK muncul di Riwayat.
            'nama_usaha'      => $b->usaha()->orderBy('id')->pluck('nama_usaha')->implode("\n"),
            'kepala_keluarga' => $b->rumahTangga()->orderBy('id')->pluck('nama_kepala_keluarga')->implode("\n"),
            'status_perbaikan' => $this->labelStatus($b->status_perbaikan),
        ];
    }

    private function pastikanBolehTagging(Request $request, int $kegiatanId, int $slsId): void
    {
        abort_unless(
            $request->user()->bolehTagging($kegiatanId, $slsId),
            403,
            'SLS ini tidak ditugaskan kepada Anda untuk kegiatan tersebut.'
        );
    }

    /** true = di dalam, false = di luar, null = SLS belum punya polygon (tidak bisa dicek). */
    private function posisiDiDalamSls(int $slsId, float $lat, float $lng): ?bool
    {
        $row = DB::selectOne(
            'SELECT ST_Covers(area, ST_SetSRID(ST_MakePoint(?, ?), 4326)) AS dalam FROM sls WHERE id = ? AND area IS NOT NULL',
            [$lng, $lat, $slsId]
        );

        return $row === null ? null : (bool) $row->dalam;
    }

    private function nomorSudahDipakai(int $kegiatanId, int $slsId, string $nomor, ?int $kecuali = null): bool
    {
        return Bangunan::where('kegiatan_id', $kegiatanId)
            ->where('sls_id', $slsId)
            ->where('nomor_bangunan', $nomor)
            ->when($kecuali, fn ($q) => $q->where('id', '!=', $kecuali))
            ->exists();
    }

    private function gagal(string $pesan, string $kolom): JsonResponse
    {
        return response()->json(['message' => $pesan, 'errors' => [$kolom => [$pesan]]], 422);
    }

    /** Feature untuk peta (properti sama dengan PetaController::dataBangunan). */
    private function fiturPeta(Bangunan $b): array
    {
        return [
            'type'     => 'Feature',
            'geometry' => ['type' => 'Point', 'coordinates' => [(float) $b->longitude, (float) $b->latitude]],
            'properties' => [
                'id'             => $b->id,
                'sls_id'         => $b->sls_id,
                'nomor_bangunan' => $b->nomor_bangunan,
                'jumlah_kk'      => $b->jumlah_kk,
                'jumlah_usaha'   => $b->jumlah_usaha,
                'status_kondisi' => $b->statusKondisi(),
                'sumber'         => $b->sumber,
                'status_perbaikan' => $b->status_perbaikan ?? Bangunan::STATUS_NORMAL,
            ],
        ];
    }

    /** Feature untuk QGIS: nama properti = kolom file titik_lokasi yang diimpor. */
    private function fiturExport(Bangunan $b): array
    {
        return [
            'type'     => 'Feature',
            'properties' => [
                'id_assignment'   => $b->id_assignment,
                'idsls'           => $b->sls?->id_sls ?? $b->id_sls_asal,
                'no_bang'         => $b->nomor_bangunan,
                'kode_bang_value' => $b->kode_bang_value,
                'btt'             => $b->flag_btt ? '1' : '0',
                'bku'             => $b->flag_bku ? '1' : '0',
                'flag_repair'     => $b->flag_repair ? '1' : '0',
                'kk'              => (int) $b->jumlah_kk,
                'usaha'           => (int) $b->jumlah_usaha,
                'akurasi_gps'     => $b->akurasi_gps,   // meter; kolom tambahan, tidak mengganggu layer QGIS
                // Kolom tambahan untuk titik yang dikoreksi di aplikasi (kosong untuk titik yang tidak dikoreksi).
                'status_perbaikan' => $b->status_perbaikan ?? Bangunan::STATUS_NORMAL,
                'lat_asal'        => $b->latitude_asal !== null ? (float) $b->latitude_asal : null,
                'lon_asal'        => $b->longitude_asal !== null ? (float) $b->longitude_asal : null,
                'dikoreksi_oleh'  => $b->dikoreksiOleh?->name,
                'dikoreksi_at'    => $b->dikoreksi_at?->toIso8601String(),
            ],
            'geometry' => ['type' => 'Point', 'coordinates' => [(float) $b->longitude, (float) $b->latitude]],
        ];
    }
}
