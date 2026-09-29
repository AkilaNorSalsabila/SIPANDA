<?php

namespace App\Http\Controllers;

use App\Models\Kegiatan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
            ->withCount(['bangunan', 'importBatches'])
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
     * Tambah, edit, dan detail sekarang berupa popup di halaman index,
     * jadi method create/edit/show di bawah ini hanya jaga-jaga kalau
     * URL-nya diakses langsung, tidak dipakai oleh UI.
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
        $validated = $request->validate([
            'nama_kegiatan' => ['required', 'string', 'max:255'],
            'bulan_mulai'   => ['required', 'integer', 'between:1,12'],
            'tahun_mulai'   => ['required', 'integer', 'between:2000,2100'],
            'bulan_selesai' => ['nullable', 'integer', 'between:1,12', 'required_with:tahun_selesai'],
            'tahun_selesai' => ['nullable', 'integer', 'between:2000,2100', 'required_with:bulan_selesai'],
            'deskripsi'     => ['nullable', 'string'],
        ]);

        $periode = $this->buildPeriode(
            (int) $validated['bulan_mulai'],
            (int) $validated['tahun_mulai'],
            isset($validated['bulan_selesai']) ? (int) $validated['bulan_selesai'] : null,
            isset($validated['tahun_selesai']) ? (int) $validated['tahun_selesai'] : null,
        );

        // Kode dibuat di server (bukan dari input) dan di dalam transaksi
        // supaya dua pengguna yang menyimpan bersamaan tidak mendapat kode sama.
        DB::transaction(function () use ($validated, $periode) {
            Kegiatan::create([
                'kode_kegiatan' => $this->nextKode(),
                'nama_kegiatan' => $validated['nama_kegiatan'],
                'periode'       => $periode,
                'deskripsi'     => $validated['deskripsi'] ?? null,
            ]);
        });

        return redirect()
            ->route('kegiatan.index')
            ->with('status', 'Kegiatan berhasil ditambahkan.');
    }

    public function update(Request $request, Kegiatan $kegiatan)
    {
        $kegiatan->update($this->validated($request));

        return redirect()
            ->route('kegiatan.index')
            ->with('status', 'Kegiatan berhasil diperbarui.');
    }

    public function destroy(Kegiatan $kegiatan)
    {
        if ($kegiatan->importBatches()->exists()) {
            return back()->with('error',
                'Kegiatan ini tidak bisa dihapus karena sudah memiliki riwayat import.');
        }

        $nama = $kegiatan->nama_kegiatan;

        DB::transaction(function () use ($kegiatan) {
            // Hapus dulu semua data bangunan terkait, baru kegiatannya,
            // supaya tidak ada data bangunan yang "yatim" (tanpa kegiatan induk).
            $kegiatan->bangunan()->delete();
            $kegiatan->delete();
        });

        return redirect()
            ->route('kegiatan.index')
            ->with('status', "Kegiatan \"{$nama}\" beserta data bangunannya berhasil dihapus.");
    }

    /**
     * Kode berikutnya dengan format KD-001, KD-002, dst.
     * Diambil dari angka terbesar yang ada, sehingga tetap unik
     * walaupun ada kegiatan yang dihapus.
     */
    private function nextKode(): string
    {
        $terakhir = Kegiatan::where('kode_kegiatan', 'like', 'KD-%')
            ->lockForUpdate()
            ->pluck('kode_kegiatan')
            ->map(fn ($kode) => (int) substr($kode, 3))
            ->max() ?? 0;

        return 'KD-' . str_pad($terakhir + 1, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Susun teks periode dari rentang bulan-tahun.
     * Hasilnya "Agustus 2026" (kalau selesai kosong/sama), atau
     * "Agustus 2026 - Desember 2026" kalau rentangnya beda.
     */
    private function buildPeriode(int $bulanMulai, int $tahunMulai, ?int $bulanSelesai, ?int $tahunSelesai): string
    {
        $awal = self::BULAN[$bulanMulai] . ' ' . $tahunMulai;

        $sama = $bulanSelesai === null
            || $tahunSelesai === null
            || ($bulanSelesai === $bulanMulai && $tahunSelesai === $tahunMulai);

        if ($sama) {
            return $awal;
        }

        $akhir = self::BULAN[$bulanSelesai] . ' ' . $tahunSelesai;

        return "{$awal} - {$akhir}";
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'nama_kegiatan' => ['required', 'string', 'max:255'],
            'periode'       => ['nullable', 'string', 'max:50'],
            'deskripsi'     => ['nullable', 'string'],
        ]);
    }
}