<?php

namespace App\Http\Controllers;

use App\Models\Kecamatan;
use App\Models\Riwayat;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KecamatanController extends Controller
{
    // Wilayah kerja SIPANDA: Kota Mojokerto, Jawa Timur.
    private const ID_PROVINSI = '35';
    private const ID_KABUPATEN = '76';
    private const NAMA_KOTA = 'KOTA MOJOKERTO';

    public function index(Request $request): View
    {
        $kecamatan = Kecamatan::query()
            ->withCount(['kelurahan', 'sls'])
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->q;
                $query->where(fn ($w) => $w
                    ->whereRaw('LOWER(nama_kecamatan) LIKE ?', ['%' . mb_strtolower($q) . '%'])
                    ->orWhere('id_kecamatan', 'like', "%{$q}%"));
            })
            ->orderBy('id_kecamatan')
            ->paginate(10)
            ->withQueryString();

        return view('kecamatan.index', [
            'kecamatan'  => $kecamatan,
            'idProvinsi' => self::ID_PROVINSI,
            'idKabupaten' => self::ID_KABUPATEN,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'id_kecamatan' => [
                'required', 'digits:3',
                Rule::unique('kecamatan', 'id_kecamatan')->where(fn ($q) => $q
                    ->where('id_provinsi', self::ID_PROVINSI)
                    ->where('id_kabupaten', self::ID_KABUPATEN)),
            ],
            'nama_kecamatan' => ['required', 'string', 'max:255'],
        ], [
            'id_kecamatan.digits' => 'Kode kecamatan harus 3 digit angka, mis. 010.',
            'id_kecamatan.unique' => 'Kode kecamatan ini sudah dipakai.',
        ]);

        try {
            $dibuat = Kecamatan::create([
                'id_provinsi'    => self::ID_PROVINSI,
                'id_kabupaten'   => self::ID_KABUPATEN,
                'id_kecamatan'   => $data['id_kecamatan'],
                'nama_kecamatan' => mb_strtoupper(trim($data['nama_kecamatan'])),
                'kabupaten_kota' => self::NAMA_KOTA,
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal menyimpan kecamatan baru', ['data' => $data, 'pesan_error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'Terjadi kesalahan saat menyimpan kecamatan. Silakan coba lagi.');
        }

        Riwayat::catat(
            modul: 'kecamatan',
            aksi: 'tambah',
            keterangan: "Menambahkan kecamatan {$dibuat->nama_kecamatan}",
            objekId: $dibuat->id,
            objekLabel: "{$dibuat->id_kecamatan} - {$dibuat->nama_kecamatan}",
            baru: ['kode' => $dibuat->id_kecamatan, 'nama_kecamatan' => $dibuat->nama_kecamatan],
        );

        return redirect()->route('kecamatan.index')->with('status', 'Kecamatan berhasil ditambahkan.');
    }

    /**
     * Hanya nama yang bisa diubah. Kode sengaja dikunci karena kode itu
     * bagian dari id_sls di seluruh data.
     */
    public function update(Request $request, Kecamatan $kecamatan): RedirectResponse
    {
        $data = $request->validate([
            'nama_kecamatan' => ['required', 'string', 'max:255'],
        ]);

        $nama = mb_strtoupper(trim($data['nama_kecamatan']));
        $namaLama = $kecamatan->nama_kecamatan;

        try {
            DB::transaction(function () use ($kecamatan, $nama) {
                $kecamatan->update(['nama_kecamatan' => $nama]);

                // Kolom teks di tabel sls (dipakai halaman Peta) ikut diperbarui.
                $kecamatan->sls()->update(['kecamatan' => $nama]);
            });
        } catch (\Throwable $e) {
            Log::error('Gagal memperbarui kecamatan', ['kecamatan_id' => $kecamatan->id, 'pesan_error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'Terjadi kesalahan saat menyimpan perubahan. Silakan coba lagi.');
        }

        Riwayat::catat(
            modul: 'kecamatan',
            aksi: 'ubah',
            keterangan: "Mengubah nama kecamatan {$namaLama} menjadi {$nama}",
            objekId: $kecamatan->id,
            objekLabel: "{$kecamatan->id_kecamatan} - {$nama}",
            lama: ['nama_kecamatan' => $namaLama],
            baru: ['nama_kecamatan' => $nama],
        );

        return redirect()->route('kecamatan.index')->with('status', 'Kecamatan berhasil diperbarui.');
    }

    public function destroy(Kecamatan $kecamatan): RedirectResponse
    {
        if ($kecamatan->kelurahan()->exists() || $kecamatan->sls()->exists()) {
            return back()->with('error', "Kecamatan \"{$kecamatan->nama_kecamatan}\" tidak bisa dihapus karena masih memiliki kelurahan atau SLS.");
        }

        $nama = $kecamatan->nama_kecamatan;
        $kode = $kecamatan->id_kecamatan;
        $id   = $kecamatan->id;
        $kecamatan->delete();

        Riwayat::catat(
            modul: 'kecamatan',
            aksi: 'hapus',
            keterangan: "Menghapus kecamatan {$nama}",
            objekId: $id,
            objekLabel: "{$kode} - {$nama}",
            lama: ['kode' => $kode, 'nama_kecamatan' => $nama],
        );

        return redirect()->route('kecamatan.index')->with('status', "Kecamatan \"{$nama}\" berhasil dihapus.");
    }
}
