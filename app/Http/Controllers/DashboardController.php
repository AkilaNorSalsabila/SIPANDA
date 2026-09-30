<?php

namespace App\Http\Controllers;

use App\Models\Bangunan;
use App\Models\ImportBatch;
use App\Models\Kegiatan;
use App\Models\RumahTangga;
use App\Models\Usaha;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Throwable;

class DashboardController extends Controller
{
    public function index()
    {
        $isAdmin = Auth::user()->isAdmin();

        // ================= STATUS TITIK (semua kegiatan) =================
        // "status_kondisi" bukan kolom; diturunkan dari sls_id + di_luar_sls
        // (aturan yang sama dengan scope di model Bangunan).
        $tanpa  = Bangunan::tanpaSls()->count();      // sls_id NULL
        $luar   = Bangunan::diLuarSls()->count();     // sls_id ada, di_luar_sls = true
        $belum  = Bangunan::belumDicek()->count();    // sls_id ada, di_luar_sls NULL
        $normal = Bangunan::whereNotNull('sls_id')->where('di_luar_sls', false)->count();

        $totalBangunan = $tanpa + $luar + $belum + $normal;
        $bermasalah    = $luar + $tanpa;

        $segmen = [
            ['label' => 'Normal (di dalam SLS)',    'nilai' => $normal, 'warna' => '#3b82f6'],
            ['label' => 'Di luar batas SLS',        'nilai' => $luar,   'warna' => '#ef4444'],
            ['label' => 'Kode SLS tidak ditemukan', 'nilai' => $tanpa,  'warna' => '#f59e0b'],
            ['label' => 'Belum bisa dicek',         'nilai' => $belum,  'warna' => '#64748b'],
        ];

        // Gradien donat (tanpa library grafik)
        $stops = [];
        $acc = 0;
        foreach ($segmen as $s) {
            if ($totalBangunan > 0 && $s['nilai'] > 0) {
                $mulai = $acc;
                $acc += $s['nilai'] / $totalBangunan * 100;
                $stops[] = "{$s['warna']} {$mulai}% {$acc}%";
            }
        }
        $donutCss = $stops
            ? 'conic-gradient(' . implode(', ', $stops) . ')'
            : 'conic-gradient(#e2e8f0 0% 100%)';

        // ================= RINGKASAN =================
        $totalKegiatan = Kegiatan::count();
        $totalSls      = Bangunan::whereNotNull('sls_id')->distinct()->count('sls_id');
        $totalUsaha    = Usaha::count();
        $totalKk       = RumahTangga::count();

        // Kalau tabel import_batches tidak punya created_at, hasilnya null (tidak ditampilkan).
        $importTerakhir = $this->coba(fn () => ImportBatch::latest()->value('created_at'));

        // ================= PER KEGIATAN =================
        $slsPerKegiatan = Bangunan::selectRaw('kegiatan_id, count(distinct sls_id) as jml')
            ->groupBy('kegiatan_id')
            ->pluck('jml', 'kegiatan_id');

        $kegiatan = Kegiatan::withCount([
            'bangunan',
            'bangunan as normal_count' => fn ($q) => $q
                ->whereNotNull('sls_id')->where('di_luar_sls', false),
            'bangunan as bermasalah_count' => fn ($q) => $q
                ->where(fn ($w) => $w
                    ->whereNull('sls_id')
                    ->orWhere(fn ($x) => $x->whereNotNull('sls_id')->where('di_luar_sls', true))),
            'bangunan as belum_dicek_count' => fn ($q) => $q
                ->whereNotNull('sls_id')->whereNull('di_luar_sls'),
        ])->orderByDesc('id')->limit(10)->get();

        // ================= KHUSUS ADMIN =================
        $pending = $isAdmin ? User::where('status', 'pending')->count() : 0;

        return view('dashboard', compact(
            'isAdmin', 'totalKegiatan', 'totalSls', 'totalBangunan', 'totalUsaha', 'totalKk',
            'bermasalah', 'segmen', 'donutCss', 'importTerakhir',
            'kegiatan', 'slsPerKegiatan', 'pending'
        ));
    }

    /** Jalankan query yang mungkin gagal; hasil null = bagian itu tidak ditampilkan. */
    private function coba(callable $fn)
    {
        try {
            return $fn();
        } catch (Throwable $e) {
            report($e);
            return null;
        }
    }
}