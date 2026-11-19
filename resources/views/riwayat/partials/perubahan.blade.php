{{-- Rincian nilai lama -> nilai baru dari satu baris riwayat ($r). --}}
@php
    $lama = $r->data_lama ?? [];
    $baru = $r->data_baru ?? [];
    $kolom = array_values(array_unique(array_merge(array_keys($lama), array_keys($baru))));

    $namaKolom = [
        'perlu_perbaikan'  => 'Perlu perbaikan',
        'solusi_perbaikan' => 'Solusi / catatan perbaikan',
        'nama_kegiatan'    => 'Nama kegiatan',
        'kode_kegiatan'    => 'Kode kegiatan',
        'periode'          => 'Periode',
        'nama_kecamatan'   => 'Nama kecamatan',
        'kode'             => 'Kode',
        'kecamatan'        => 'Kecamatan',
        'nama_kelurahan'   => 'Nama kelurahan',
        'status'           => 'Status akun',
        'role'             => 'Peran',
        'jenis_data'       => 'Jenis data',
        'file'             => 'File',
        'kegiatan'         => 'Kegiatan',
        'total_data'       => 'Total data',
        'data_valid'       => 'Berhasil',
        'catatan'          => 'Catatan / bermasalah',
        'jumlah_titik'     => 'Titik ikut terhapus',
        'jumlah_import'    => 'Riwayat import ikut terhapus',
        'jumlah_sls'       => 'Jumlah SLS ditugaskan',
        'ditambah'         => 'SLS ditambahkan',
        'dicabut'          => 'SLS dicabut',
        'no_bang'          => 'No. bangunan (no_bang)',
        'kode_bang_value'  => 'Kode jenis bangunan (kode_bang_value)',
        'btt'              => 'Bangunan tempat tinggal (btt)',
        'bku'              => 'Bangunan khusus usaha (bku)',
        'flag_repair'      => 'Perlu perbaikan (flag_repair)',
        'kk'               => 'Jumlah KK (kk)',
        'usaha'            => 'Jumlah usaha (usaha)',
    ];

    $tampil = function ($v) {
        if (is_bool($v)) return $v ? 'Ya' : 'Tidak';
        if ($v === null || $v === '') return '—';
        return is_array($v) ? json_encode($v, JSON_UNESCAPED_UNICODE) : (string) $v;
    };
@endphp

@if ($kolom)
    <dl class="space-y-2.5">
        @foreach ($kolom as $k)
            <div>
                <dt class="text-[11px] font-medium uppercase tracking-wide text-slate-400">{{ $namaKolom[$k] ?? str_replace('_', ' ', $k) }}</dt>
                <dd class="mt-0.5 text-sm break-words whitespace-pre-line">
                    @if (array_key_exists($k, $lama) && array_key_exists($k, $baru))
                        <span class="text-red-600/80 line-through">{{ $tampil($lama[$k]) }}</span>
                        <span class="mx-1 text-slate-300">→</span>
                        <span class="text-emerald-700 font-medium">{{ $tampil($baru[$k]) }}</span>
                    @elseif (array_key_exists($k, $baru))
                        <span class="text-slate-700">{{ $tampil($baru[$k]) }}</span>
                    @else
                        <span class="text-slate-500">{{ $tampil($lama[$k]) }}</span>
                    @endif
                </dd>
            </div>
        @endforeach
    </dl>
@endif
