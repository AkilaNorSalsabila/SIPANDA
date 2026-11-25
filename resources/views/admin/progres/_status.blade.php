@php
    $peta = [
        'belum'       => ['Belum mulai',     'bg-slate-100 text-slate-600 ring-slate-200'],
        'berjalan'    => ['Berjalan',        'bg-amber-50 text-amber-700 ring-amber-200'],
        'selesai'     => ['Selesai',         'bg-emerald-50 text-emerald-700 ring-emerald-200'],
        'tanpa_tugas' => ['Belum ditugaskan', 'bg-white text-slate-500 ring-slate-300'],
    ];
    [$label, $kelas] = $peta[$status] ?? $peta['belum'];
@endphp
<span class="inline-flex px-2.5 py-1 rounded-full text-xs font-semibold ring-1 ring-inset {{ $kelas }}">{{ $label }}</span>
