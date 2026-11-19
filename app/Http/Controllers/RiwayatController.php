<?php

namespace App\Http\Controllers;

use App\Models\Riwayat;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Halaman Riwayat Aktivitas (khusus admin, lihat routes/web.php).
 * Hanya membaca: tidak ada fitur ubah/hapus riwayat.
 */
class RiwayatController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'dari' => ['nullable', 'date'],
            'sampai' => ['nullable', 'date'],
        ]);

        $modul = array_key_exists((string) $request->modul, Riwayat::MODUL) ? $request->modul :null;
        $aksi = array_key_exists((string) $request->aksi, Riwayat::AKSI) ? $request->aksi : null;

        $daftar = Riwayat::query()
            ->when($modul, fn ($q) => $q->where('modul', $modul))
            ->when($aksi, fn ($q) => $q->where('aksi', $aksi))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', (int) $request->user_id))
            ->when($request->filled('dari'), fn ($q) => $q->whereDate('created_at', '>=', $request->dari))
            ->when($request->filled('sampai'), fn ($q) => $q->whereDate('created_at', '<=', $request->sampai))
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = trim($request->q);
                $query->where(fn ($w) => $w
                    ->where('keterangan', 'like', "%{$q}%")
                    ->orWhere('objek_label', 'like', "%{$q}%")
                    ->orWhere('nama_user', 'like', "%{$q}%"));
            })

            ->latest('created_at')
            ->latest('id')
            ->paginate(25)
            ->withQueryString();

        return view('riwayat.index', [
            'daftar'    => $daftar,
            'ringkasan' => [
                'hari_ini' => Riwayat::whereDate('created_at', today())->count(),
                'tujuh_hari' => Riwayat::where('created_at', '>=', now()->subDays(7))->count(),
                'total'    => Riwayat::count(),
            ],
            // Pengguna yang pernah tercatat, untuk dropdown filter.
            'penggunaList' => Riwayat::query()
                ->whereNotNull('user_id')
                ->selectRaw('user_id, MAX(nama_user) as nama_user')
                ->groupBy('user_id')
                ->orderBy('nama_user')
                ->get(),
        ]);
    }
}
