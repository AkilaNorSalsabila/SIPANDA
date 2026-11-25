<?php

namespace App\Http\Controllers;

use App\Models\Kegiatan;
use App\Models\Riwayat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class KegiatanController extends Controller
{
    private const BULAN = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember',
    ];

    public function index(Request $request)
    {
        $kegiatan = Kegiatan::query()
            ->withCount([
                'bangunan',
                // [#10] titik hasil kerja petugas (tagging + koreksi) -> hapus kegiatan butuh konfirmasi nama
                'bangunan as hasil_kerja_count' => fn ($q) => $q->hasilKerja(),
            ])
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->q;
                $query->where(fn ($w) => $w
                    ->where('kode_kegiatan', 'like', "%{$q}%")
                    ->orWhere('nama_kegiatan', 'like', "%{$q}%"));
            })
            ->orderByDesc('id')
            ->paginate(10)
            ->withQueryString();

        return view('kegiatan.index', [
            'kegiatan'     => $kegiatan,
            'kodeOtomatis' => $this->nextKode(),
            'bulanList'    => self::BULAN,
        ]);
    }

    /**
     * Tambah, edit, dan detail berupa popup di halaman index,
     */
    public function create()
    {
        return redirect()->route('kegiatan.index');
    }

    public function edit(Kegiatan $kegiatan)
    {
        return redirect()->route('kegiatan.index');
    }

    public function show(Kegiatan $kegiatan)
    {
        return redirect()->route('kegiatan.index');
    }

    public function store(Request $request)
    {
        $data = $this->validatedWithPeriode($request);

        $dibuat = null;

        try {
            DB::transaction(function () use ($data, &$dibuat) {
                $dibuat = Kegiatan::create([
                    'kode_kegiatan' => $this->nextKode(),
                    'nama_kegiatan' => $data['nama_kegiatan'],
                    'periode'       => $data['periode'],
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Gagal menyimpan kegiatan baru', [
                'data_baru'   => $data,
                'pesan_error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat menyimpan kegiatan baru. Silakan coba lagi.');
        }

        Riwayat::catat(
            modul: 'kegiatan',
            aksi: 'tambah',
            keterangan: "Menambahkan kegiatan {$dibuat->nama_kegiatan}",
            objekId: $dibuat->id,
            objekLabel: "{$dibuat->kode_kegiatan} - {$dibuat->nama_kegiatan}",
            baru: [
                'kode_kegiatan' => $dibuat->kode_kegiatan,
                'nama_kegiatan' => $dibuat->nama_kegiatan,
                'periode'       => $dibuat->periode,
            ],
        );

        return redirect()
            ->route('kegiatan.index')
            ->with('status', 'Kegiatan berhasil ditambahkan.');
    }

    public function update(Request $request, Kegiatan $kegiatan)
    {
        try {
            $data = $this->validatedWithPeriode($request);
        } catch (ValidationException $e) {
            Log::warning('Gagal validasi saat edit kegiatan', [
                'kegiatan_id' => $kegiatan->id,
                'input'       => $request->except(['_token', '_method']),
                'errors'      => $e->errors(),
            ]);

            throw $e; // Laravel tetap redirect balik + isi $errors seperti biasa
        }

        $lama = $kegiatan->only(['nama_kegiatan', 'periode']);

        try {
            $kegiatan->update([
                'nama_kegiatan' => $data['nama_kegiatan'],
                'periode'       => $data['periode'],
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal menyimpan perubahan kegiatan', [
                'kegiatan_id' => $kegiatan->id,
                'data_baru'   => $data,
                'pesan_error' => $e->getMessage(),
            ]);

            return back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat menyimpan perubahan kegiatan. Silakan coba lagi.');
        }

        Riwayat::catat(
            modul: 'kegiatan',
            aksi: 'ubah',
            keterangan: "Mengubah kegiatan {$kegiatan->kode_kegiatan}",
            objekId: $kegiatan->id,
            objekLabel: "{$kegiatan->kode_kegiatan} - {$data['nama_kegiatan']}",
            lama: $lama,
            baru: ['nama_kegiatan' => $data['nama_kegiatan'], 'periode' => $data['periode']],
        );

        return redirect()
            ->route('kegiatan.index')
            ->with('status', 'Kegiatan berhasil diperbarui.');
    }

    public function destroy(Kegiatan $kegiatan)
    {
        $nama = $kegiatan->nama_kegiatan;

        // [#10] Hasil kerja petugas (titik tagging & titik import yang sudah dikoreksi) ikut terhapus bersama
        // kegiatan. Karena itu admin harus mengetik nama kegiatan persis (kolom konfirmasi_nama dari popup di view).
        $hasilKerja = $kegiatan->bangunan()->hasilKerja()->count();
        if ($hasilKerja > 0 && trim((string) request('konfirmasi_nama')) !== $nama) {
            return back()->with(
                'error',
                "Kegiatan \"{$nama}\" tidak dihapus: memuat {$hasilKerja} titik hasil kerja petugas. "
                . 'Unduh dulu datanya lewat Export, lalu hapus lagi dan ketik nama kegiatan dengan persis.'
            );
        }

        // RIWAYAT: hitung dulu sebelum datanya terhapus
        $id           = $kegiatan->id;
        $kode         = $kegiatan->kode_kegiatan;
        $jumlahTitik  = $kegiatan->bangunan()->count();
        $jumlahImport = $kegiatan->importBatches()->count();

        try {
            DB::transaction(function () use ($kegiatan) {
                $kegiatan->bangunan()->delete();
                $kegiatan->importBatches()->delete();
                $kegiatan->delete();
            });
        } catch (\Throwable $e) {
            Log::error('Gagal menghapus kegiatan', [
                'kegiatan_id' => $kegiatan->id,
                'nama'        => $nama,
                'pesan_error' => $e->getMessage(),
            ]);

            return back()->with('error', "Gagal menghapus kegiatan \"{$nama}\". Silakan coba lagi.");
        }

        Riwayat::catat(
            modul: 'kegiatan',
            aksi: 'hapus',
            keterangan: "Menghapus kegiatan {$nama} beserta {$jumlahTitik} titik dan {$jumlahImport} riwayat import",
            objekId: $id,
            objekLabel: "{$kode} - {$nama}",
            lama: [
                'kode_kegiatan'  => $kode,
                'nama_kegiatan'  => $nama,
                'jumlah_titik'   => $jumlahTitik,
                'jumlah_import'  => $jumlahImport,
            ],
        );

        return redirect()
            ->route('kegiatan.index')
            ->with('status', "Kegiatan \"{$nama}\" beserta seluruh datanya berhasil dihapus.");
    }
    private function nextKode(): string
    {
        $terakhir = Kegiatan::where('kode_kegiatan', 'like', 'KD-%')
            ->lockForUpdate()
            ->pluck('kode_kegiatan')
            ->map(fn ($kode) => (int) substr($kode, 3))
            ->max() ?? 0;

        return 'KD-' . str_pad($terakhir + 1, 3, '0', STR_PAD_LEFT);
    }

    private function buildPeriode(int $bulanMulai, int $tahunMulai, ?int $bulanSelesai, ?int $tahunSelesai): string
    {
        $awal = self::BULAN[$bulanMulai] . ' ' . $tahunMulai;

        $sama = $bulanSelesai === null
            || $tahunSelesai === null
            || ($bulanSelesai === $bulanMulai && $tahunSelesai === $tahunMulai);

        if ($sama) {
            return $awal;
        }

        return $awal . ' - ' . self::BULAN[$bulanSelesai] . ' ' . $tahunSelesai;
    }

    private function validatedWithPeriode(Request $request): array
    {
        $validated = $request->validate([
            'nama_kegiatan' => ['required', 'string', 'max:255'],
            'bulan_mulai'   => ['required', 'integer', 'between:1,12'],
            'tahun_mulai'   => ['required', 'integer', 'between:2000,2100'],
            'bulan_selesai' => ['nullable', 'integer', 'between:1,12', 'required_with:tahun_selesai'],
            'tahun_selesai' => ['nullable', 'integer', 'between:2000,2100', 'required_with:bulan_selesai'],
        ]);

        $validated['periode'] = $this->buildPeriode(
            (int) $validated['bulan_mulai'],
            (int) $validated['tahun_mulai'],
            isset($validated['bulan_selesai']) ? (int) $validated['bulan_selesai'] : null,
            isset($validated['tahun_selesai']) ? (int) $validated['tahun_selesai'] : null,
        );

        return $validated;
    }
    
}