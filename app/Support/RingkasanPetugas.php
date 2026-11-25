<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Ringkasan INSIGHT untuk dashboard petugas (viewer).
 *
 * Sengaja dipisah dari ProgresPetugas: ProgresPetugas menjawab "SLS mana yang harus saya
 * kerjakan" (dipakai menu Tagging), sedangkan kelas ini menjawab "bagaimana kinerja saya"
 * (tren aktivitas, kualitas GPS, hasil verifikasi, hal yang perlu perhatian).
 * Dashboard dengan begitu tidak lagi mengulang daftar SLS milik menu Tagging.
 *
 * Aktivitas = titik baru yang di-tag (tag) + koreksi titik yang dikerjakan (kor).
 * Pengelompokan per hari memakai zona waktu WIB, dilakukan di PHP supaya tidak bergantung
 * pada fungsi zona waktu milik database.
 */
class RingkasanPetugas
{
    private const TZ = 'Asia/Jakarta';
    private const HARI = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
    private const BULAN = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

    /** Panjang grafik (hari) dan jangkauan data untuk menghitung streak. */
    private const HARI_GRAFIK = 14;
    private const HARI_RIWAYAT = 60;

    /**
     * @param  array  $progres  hasil ProgresPetugas::untuk($user)
     */
    public static function untuk(User $user, array $progres): array
    {
        $uid = $user->id;
        $sekarang = Carbon::now(self::TZ);

        $aktivitas = self::aktivitasHarian($uid, $sekarang);
        $gps = self::kualitasGps($uid);
        $verifikasi = self::kualitasVerifikasi($progres);

        return [
            'aktivitas'    => $aktivitas,
            'gps'          => $gps,
            'verifikasi'   => $verifikasi,
            'kontribusi'   => self::kontribusiPerKegiatan($uid),
            'terbaru'      => self::aktivitasTerbaru($uid),
            'perhatian'    => self::perluPerhatian($progres),
            'insight'      => self::insight($aktivitas, $gps, $verifikasi),
        ];
    }

    // ------------------------------------------------------------------ aktivitas

    private static function aktivitasHarian(int $uid, Carbon $sekarang): array
    {
        $hariIni = $sekarang->copy()->startOfDay();
        $mulai = $hariIni->copy()->subDays(self::HARI_RIWAYAT - 1);
        $mulaiDb = $mulai->copy()->timezone(config('app.timezone'));

        $tag = DB::table('bangunan')
            ->where('sumber', 'tagging')
            ->where('dibuat_oleh', $uid)
            ->whereRaw('COALESCE(ditandai_at, created_at) >= ?', [$mulaiDb])
            ->selectRaw('COALESCE(ditandai_at, created_at) AS waktu')
            ->pluck('waktu');

        $kor = DB::table('bangunan')
            ->where('dikoreksi_oleh', $uid)
            ->where('dikoreksi_at', '>=', $mulaiDb)
            ->pluck('dikoreksi_at');

        // [tanggal Y-m-d] => ['tag' => n, 'kor' => n]
        $perHari = [];
        foreach ([['tag', $tag], ['kor', $kor]] as [$jenis, $daftar]) {
            foreach ($daftar as $waktu) {
                $tgl = Carbon::parse($waktu, config('app.timezone'))->timezone(self::TZ)->toDateString();
                $perHari[$tgl][$jenis] = ($perHari[$tgl][$jenis] ?? 0) + 1;
            }
        }

        $ambil = fn (Carbon $d) => [
            'tag' => $perHari[$d->toDateString()]['tag'] ?? 0,
            'kor' => $perHari[$d->toDateString()]['kor'] ?? 0,
        ];

        // ---- grafik 14 hari terakhir (lama -> baru)
        $grafik = [];
        for ($i = self::HARI_GRAFIK - 1; $i >= 0; $i--) {
            $d = $hariIni->copy()->subDays($i);
            $v = $ambil($d);
            $grafik[] = [
                'tanggal' => $d->toDateString(),
                'hari'    => self::HARI[$d->dayOfWeek],
                'tgl'     => $d->day,
                'label'   => self::HARI[$d->dayOfWeek] . ', ' . $d->day . ' ' . self::BULAN[$d->month - 1],
                'tag'     => $v['tag'],
                'kor'     => $v['kor'],
                'total'   => $v['tag'] + $v['kor'],
                'hari_ini' => $i === 0,
            ];
        }
        $grafik = collect($grafik);
        $maks = max(1, (int) $grafik->max('total'));

        // ---- perbandingan hari ini vs kemarin, 7 hari ini vs 7 hari sebelumnya
        $total = fn (Carbon $d) => array_sum($ambil($d));
        $hariIniTotal = $total($hariIni);
        $kemarinTotal = $total($hariIni->copy()->subDay());

        $jumlahRentang = function (int $dariOffset, int $hari) use ($hariIni, $total) {
            $s = 0;
            for ($i = $dariOffset; $i < $dariOffset + $hari; $i++) {
                $s += $total($hariIni->copy()->subDays($i));
            }

            return $s;
        };
        $minggu = $jumlahRentang(0, 7);
        $mingguLalu = $jumlahRentang(7, 7);

        // ---- hari aktif, rata-rata, tersibuk
        $aktif = $grafik->where('total', '>', 0);
        $tersibuk = $aktif->sortByDesc('total')->first();

        // ---- streak: hari berturut-turut yang ada aktivitas.
        // Kalau hari ini belum ada aktivitas, hitung mundur dari kemarin (streak belum putus).
        $streak = 0;
        $cursor = $hariIni->copy();
        if ($total($cursor) === 0) {
            $cursor->subDay();
        }
        while ($streak < self::HARI_RIWAYAT && $total($cursor) > 0) {
            $streak++;
            $cursor->subDay();
        }

        return [
            'grafik'        => $grafik,
            'maks'          => $maks,
            'hari_ini'      => $hariIniTotal,
            'kemarin'       => $kemarinTotal,
            'minggu'        => $minggu,
            'minggu_lalu'   => $mingguLalu,
            'delta_minggu'  => $mingguLalu > 0 ? (int) round(($minggu - $mingguLalu) / $mingguLalu * 100) : null,
            'hari_aktif'    => $aktif->count(),
            'rata_aktif'    => $aktif->count() ? round($aktif->sum('total') / $aktif->count(), 1) : 0,
            'tersibuk'      => $tersibuk,
            'streak'        => $streak,
            'total_grafik'  => (int) $grafik->sum('total'),
            'ada_data'      => $grafik->sum('total') > 0,
        ];
    }

    // ------------------------------------------------------------------ kualitas GPS

    private static function kualitasGps(int $uid): array
    {
        $baik = (float) config('tagging.akurasi_baik_m', 10);
        $cukup = (float) config('tagging.akurasi_cukup_m', 30);

        $r = DB::table('bangunan')
            ->where('sumber', 'tagging')
            ->where('dibuat_oleh', $uid)
            ->selectRaw('
                COUNT(*) AS total,
                COUNT(akurasi_gps) AS ada_akurasi,
                AVG(akurasi_gps) AS rata,
                COUNT(*) FILTER (WHERE akurasi_gps <= ?) AS baik,
                COUNT(*) FILTER (WHERE akurasi_gps > ? AND akurasi_gps <= ?) AS cukup,
                COUNT(*) FILTER (WHERE akurasi_gps > ?) AS rendah,
                COUNT(*) FILTER (WHERE sumber_tag = \'gps\') AS via_gps,
                COUNT(*) FILTER (WHERE sumber_tag = \'peta\') AS via_peta
            ', [$baik, $baik, $cukup, $cukup])
            ->first();

        $adaAkurasi = (int) ($r->ada_akurasi ?? 0);
        $viaGps = (int) ($r->via_gps ?? 0);
        $viaPeta = (int) ($r->via_peta ?? 0);

        return [
            'total'      => (int) ($r->total ?? 0),
            'ada_akurasi' => $adaAkurasi,
            'rata'       => $adaAkurasi ? round((float) $r->rata, 1) : null,
            'baik'       => (int) ($r->baik ?? 0),
            'cukup'      => (int) ($r->cukup ?? 0),
            'rendah'     => (int) ($r->rendah ?? 0),
            'persen_baik' => $adaAkurasi ? (int) round((int) $r->baik / $adaAkurasi * 100) : null,
            'via_gps'    => $viaGps,
            'via_peta'   => $viaPeta,
            'batas_baik' => $baik,
            'batas_cukup' => $cukup,
        ];
    }

    // ------------------------------------------------------------------ hasil verifikasi

    private static function kualitasVerifikasi(array $p): array
    {
        $ok = (int) $p['terverifikasi'];
        $kembali = (int) $p['dikembalikan'];
        $tunggu = (int) $p['menunggu'];
        $diputuskan = $ok + $kembali; // koreksi yang sudah diputuskan admin

        return [
            'menunggu'      => $tunggu,
            'terverifikasi' => $ok,
            'dikembalikan'  => $kembali,
            'total'         => $ok + $kembali + $tunggu,
            // null = belum ada koreksi yang diputuskan admin, jadi belum ada angka yang adil.
            'persen_lolos'  => $diputuskan > 0 ? (int) round($ok / $diputuskan * 100) : null,
        ];
    }

    // ------------------------------------------------------------------ kontribusi

    /**
     * Per kegiatan: berapa titik tagging di SLS saya, dan berapa persen di antaranya hasil saya
     * (relevan kalau satu SLS dikerjakan beberapa petugas).
     */
    private static function kontribusiPerKegiatan(int $uid): Collection
    {
        return DB::table('bangunan as b')
            ->join('penugasan_sls as p', function ($j) {
                $j->on('p.sls_id', '=', 'b.sls_id')->on('p.kegiatan_id', '=', 'b.kegiatan_id');
            })
            ->where('p.user_id', $uid)
            ->where('b.sumber', 'tagging')
            ->selectRaw('b.kegiatan_id, COUNT(*) AS total, COUNT(*) FILTER (WHERE b.dibuat_oleh = ?) AS saya', [$uid])
            ->groupBy('b.kegiatan_id')
            ->get()
            ->keyBy('kegiatan_id');
    }

    // ------------------------------------------------------------------ aktivitas terbaru

    private static function aktivitasTerbaru(int $uid): Collection
    {
        $nama = fn ($r) => $r->nama_bangunan ?: ($r->nomor_bangunan ? 'Bangunan ' . $r->nomor_bangunan : 'Titik tanpa nama');

        $tag = DB::table('bangunan as b')
            ->join('sls as s', 's.id', '=', 'b.sls_id')
            ->where('b.sumber', 'tagging')
            ->where('b.dibuat_oleh', $uid)
            ->selectRaw('COALESCE(b.ditandai_at, b.created_at) AS waktu, s.nama_sls, b.nama_bangunan, b.nomor_bangunan, b.akurasi_gps')
            ->orderByRaw('COALESCE(b.ditandai_at, b.created_at) DESC')
            ->limit(6)
            ->get()
            ->map(fn ($r) => (object) [
                'jenis' => 'tag',
                'judul' => 'Menandai titik baru',
                'objek' => $nama($r),
                'sls'   => $r->nama_sls,
                'waktu' => $r->waktu,
                'info'  => $r->akurasi_gps !== null ? 'akurasi ±' . round((float) $r->akurasi_gps) . ' m' : null,
            ]);

        $kor = DB::table('bangunan as b')
            ->join('sls as s', 's.id', '=', 'b.sls_id')
            ->where('b.dikoreksi_oleh', $uid)
            ->whereNotNull('b.dikoreksi_at')
            ->selectRaw('b.dikoreksi_at AS waktu, s.nama_sls, b.nama_bangunan, b.nomor_bangunan, b.status_perbaikan')
            ->orderByDesc('b.dikoreksi_at')
            ->limit(6)
            ->get()
            ->map(function ($r) use ($nama) {
                [$jenis, $judul] = match ($r->status_perbaikan) {
                    'terverifikasi'    => ['ok', 'Koreksi diverifikasi admin'],
                    'perlu_perbaikan'  => ['kembali', 'Koreksi dikembalikan admin'],
                    default            => ['kor', 'Mengoreksi titik'],
                };

                return (object) [
                    'jenis' => $jenis,
                    'judul' => $judul,
                    'objek' => $nama($r),
                    'sls'   => $r->nama_sls,
                    'waktu' => $r->waktu,
                    'info'  => null,
                ];
            });

        return $tag->concat($kor)
            ->sortByDesc(fn ($e) => Carbon::parse($e->waktu, config('app.timezone'))->getTimestamp())
            ->take(6)
            ->values()
            ->map(function ($e) {
                $w = Carbon::parse($e->waktu, config('app.timezone'))->timezone(self::TZ);
                $e->relatif = $w->copy()->locale('id')->diffForHumans();
                $e->lengkap = $w->format('d/m/Y H:i') . ' WIB';

                return $e;
            });
    }

    // ------------------------------------------------------------------ perlu perhatian

    /**
     * Daftar tindakan ringkas (bukan daftar SLS). Tiap butir punya jumlah dan, bila ada,
     * satu tautan ke SLS yang paling mendesak.
     */
    private static function perluPerhatian(array $p): array
    {
        $semua = $p['grup']->flatten(1);
        $butir = [];

        $peta = fn ($r) => route('peta.index', ['mode' => 'tagging', 'kegiatan' => $r->kegiatan_id, 'sls' => $r->sls_id]);

        // 1. Koreksi dikembalikan admin (paling mendesak)
        if ($p['dikembalikan'] > 0) {
            $target = $semua->where('dikembalikan', '>', 0)->sortByDesc('dikembalikan')->first();
            $butir[] = [
                'tingkat' => 'merah',
                'angka'   => $p['dikembalikan'],
                'judul'   => 'koreksi dikembalikan admin',
                'detail'  => 'Ambil ulang lokasinya. Terbanyak di ' . $target->nama_sls . '.',
                'url'     => $target->punya_polygon ? $peta($target) : route('tagging.index'),
                'aksi'    => 'Perbaiki sekarang',
            ];
        }

        // 2. Sisa titik perlu perbaikan (di luar yang sudah dikembalikan)
        $sisaLain = max(0, $p['sisaPerbaikan'] - $p['dikembalikan']);
        if ($sisaLain > 0) {
            $target = $semua->where('punya_polygon', true)->where('sisa_perbaikan', '>', 0)->sortByDesc('sisa_perbaikan')->first();
            $butir[] = [
                'tingkat' => 'kuning',
                'angka'   => $sisaLain,
                'judul'   => 'titik masih perlu diperbaiki',
                'detail'  => $target ? 'Paling banyak di ' . $target->nama_sls . '.' : 'Menunggu batas SLS dari admin.',
                'url'     => $target ? $peta($target) : route('tagging.index'),
                'aksi'    => $target ? 'Buka SLS' : null,
            ];
        }

        // 3. SLS yang belum punya batas (tidak bisa mulai)
        $tanpaBatas = $semua->where('punya_polygon', false)->count();
        if ($tanpaBatas > 0) {
            $butir[] = [
                'tingkat' => 'abu',
                'angka'   => $tanpaBatas,
                'judul'   => 'SLS belum punya batas wilayah',
                'detail'  => 'Belum bisa ditagging. Hubungi admin untuk mengimpor batasnya.',
                'url'     => null,
                'aksi'    => null,
            ];
        }

        // 4. Koreksi menunggu verifikasi (informasi, tidak perlu tindakan)
        if ($p['menunggu'] > 0) {
            $butir[] = [
                'tingkat' => 'biru',
                'angka'   => $p['menunggu'],
                'judul'   => 'koreksi menunggu verifikasi',
                'detail'  => 'Sudah Anda kerjakan. Tinggal menunggu admin.',
                'url'     => null,
                'aksi'    => null,
            ];
        }

        return $butir;
    }

    // ------------------------------------------------------------------ kalimat insight

    /**
     * Beberapa kalimat singkat yang menafsirkan angka-angka di atas. Hanya yang punya dasar data.
     */
    private static function insight(array $a, array $gps, array $v): array
    {
        $k = [];

        if ($a['streak'] >= 2) {
            $k[] = "Anda aktif {$a['streak']} hari berturut-turut.";
        }
        if ($a['delta_minggu'] !== null && $a['minggu'] > 0) {
            $d = $a['delta_minggu'];
            $k[] = $d > 0
                ? "Minggu ini {$d}% lebih banyak dari minggu lalu."
                : ($d < 0 ? 'Minggu ini ' . abs($d) . '% lebih sedikit dari minggu lalu.' : 'Kecepatan kerja minggu ini sama dengan minggu lalu.');
        }
        if ($a['tersibuk'] && $a['hari_aktif'] >= 3) {
            $k[] = "Hari terproduktif 2 minggu terakhir: {$a['tersibuk']['label']} ({$a['tersibuk']['total']} aktivitas).";
        }
        if ($gps['persen_baik'] !== null && $gps['ada_akurasi'] >= 5) {
            $k[] = "{$gps['persen_baik']}% titik Anda memiliki akurasi GPS baik.";
        }
        if ($v['persen_lolos'] !== null) {
            $k[] = "{$v['persen_lolos']}% koreksi Anda lolos verifikasi admin.";
        }

        return $k;
    }
}
