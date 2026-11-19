<?php

namespace App\Http\Controllers;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Riwayat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class KelurahanController extends Controller
{
    public function index(Request $request): View
    {
        $kelurahan = Kelurahan::query()
            ->with('kecamatan')
            ->withCount('sls')
            ->when($request->filled('kecamatan_id'), fn ($q) => $q->where('kecamatan_id', $request->kecamatan_id))
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->q;
                $query->where(fn ($w) => $w
                    ->whereRaw('LOWER(nama_kelurahan) LIKE ?', ['%' . mb_strtolower($q) . '%'])
                    ->orWhere('id_kelurahan', 'like', "%{$q}%"));
            })
            ->orderBy('kecamatan_id')
            ->orderBy('id_kelurahan')
            ->paginate(15)
            ->withQueryString();

        return view('kelurahan.index', [
            'kelurahan'     => $kelurahan,
            'kecamatanList' => Kecamatan::orderBy('id_kecamatan')->get(['id', 'id_kecamatan', 'nama_kecamatan']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'kecamatan_id' => ['required', 'exists:kecamatan,id'],
            'id_kelurahan' => [
                'required', 'digits:3',
                Rule::unique('kelurahan', 'id_kelurahan')
                    ->where(fn ($q) => $q->where('kecamatan_id', $request->kecamatan_id)),
            ],
            'nama_kelurahan' => ['required', 'string', 'max:255'],
        ], [
            'id_kelurahan.digits' => 'Kode kelurahan harus 3 digit angka, mis. 001.',
            'id_kelurahan.unique' => 'Kode kelurahan ini sudah dipakai di kecamatan tersebut.',
            'kecamatan_id.required' => 'Pilih kecamatan terlebih dahulu.',
        ]);

        try {
            $dibuat = Kelurahan::create([
                'kecamatan_id'   => $data['kecamatan_id'],
                'id_kelurahan'   => $data['id_kelurahan'],
                'nama_kelurahan' => mb_strtoupper(trim($data['nama_kelurahan'])),
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal menyimpan kelurahan baru', ['data' => $data, 'pesan_error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'Terjadi kesalahan saat menyimpan kelurahan. Silakan coba lagi.');
        }

        $namaKec = $dibuat->kecamatan?->nama_kecamatan;

        Riwayat::catat(
            modul: 'kelurahan',
            aksi: 'tambah',
            keterangan: "Menambahkan kelurahan {$dibuat->nama_kelurahan}" . ($namaKec ? " (Kec. {$namaKec})" : ''),
            objekId: $dibuat->id,
            objekLabel: "{$dibuat->id_kelurahan} - {$dibuat->nama_kelurahan}",
            baru: [
                'kode'           => $dibuat->id_kelurahan,
                'nama_kelurahan' => $dibuat->nama_kelurahan,
                'kecamatan'      => $namaKec,
            ],
        );

        return redirect()->route('kelurahan.index', $request->only('kecamatan_id'))
            ->with('status', 'Kelurahan berhasil ditambahkan.');
    }

    /** Hanya nama yang bisa diubah; kode dan kecamatan dikunci (lihat KecamatanController::update). */
    public function update(Request $request, Kelurahan $kelurahan): RedirectResponse
    {
        $data = $request->validate([
            'nama_kelurahan' => ['required', 'string', 'max:255'],
        ]);

        $nama = mb_strtoupper(trim($data['nama_kelurahan']));
        $namaLama = $kelurahan->nama_kelurahan;

        try {
            DB::transaction(function () use ($kelurahan, $nama) {
                $kelurahan->update(['nama_kelurahan' => $nama]);
                $kelurahan->sls()->update(['desa_kelurahan' => $nama]);
            });
        } catch (\Throwable $e) {
            Log::error('Gagal memperbarui kelurahan', ['kelurahan_id' => $kelurahan->id, 'pesan_error' => $e->getMessage()]);

            return back()->withInput()->with('error', 'Terjadi kesalahan saat menyimpan perubahan. Silakan coba lagi.');
        }

        Riwayat::catat(
            modul: 'kelurahan',
            aksi: 'ubah',
            keterangan: "Mengubah nama kelurahan {$namaLama} menjadi {$nama}",
            objekId: $kelurahan->id,
            objekLabel: "{$kelurahan->id_kelurahan} - {$nama}",
            lama: ['nama_kelurahan' => $namaLama],
            baru: ['nama_kelurahan' => $nama],
        );

        return back()->with('status', 'Kelurahan berhasil diperbarui.');
    }

    public function destroy(Kelurahan $kelurahan): RedirectResponse
    {
        if ($kelurahan->sls()->exists()) {
            return back()->with('error', "Kelurahan \"{$kelurahan->nama_kelurahan}\" tidak bisa dihapus karena masih memiliki SLS.");
        }

        $nama =$kelurahan->nama_kelurahan;
        $kode = $kelurahan ->id_kelurahan;
        $id = $kelurahan->id;
        $kelurahan->delete();

        Riwayat::catat(
            modul: 'kelurahan', 
            aksi: 'hapus',
            keterangan: "Menghapus kelurahan {$nama}",
            objekId: $id,
            objekLabel: "{$kode} - {$nama}",
            lama: ['kode' => $kode, 'nama_kelurahan' => $nama],
        );

        return back()->with('status', "Kelurahan \"{$nama}\" berhasil dihapus.");
    }

    /**
     * JSON untuk dropdown berantai (pilih kecamatan -> daftar kelurahan).
     * Dipakai fitur berikutnya (upload per kecamatan, filter data lokasi).
     */
    public function byKecamatan(Kecamatan $kecamatan): JsonResponse
    {
        return response()->json(
            $kecamatan->kelurahan()
                ->orderBy('id_kelurahan')
                ->get(['id', 'id_kelurahan', 'nama_kelurahan'])
        );
    }
}
