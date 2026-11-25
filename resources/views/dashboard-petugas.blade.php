<x-app-layout>
    <x-slot name="header">
        <div class="min-w-0">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">Dashboard</h2>
            <p class="hidden sm:block text-xs text-slate-500 mt-0.5">Ringkasan kinerja dan hal yang perlu Anda perhatikan</p>
        </div>
    </x-slot>

@php
    /*
     | Dashboard petugas = RINGKASAN KINERJA (tren, kualitas, tindak lanjut).
     | Daftar kegiatan + SLS + tombol mulai tagging ada di menu "Tagging", jadi tidak diulang di sini.
     */
    $namaDepan = \Illuminate\Support\Str::of($user->name)->trim()->before(' ');
    $fmt = fn ($n, $d = 0) => number_format((float) $n, $d, ',', '.');

    $a = $aktivitas;
    $persenSelesai = $totalSls ? (int) round($slsSelesai / $totalSls * 100) : 0;
    $keliling = 2 * M_PI * 52;               // cincin hero (r = 52)
    $kelilingKecil = 2 * M_PI * 34;          // cincin kecil verifikasi (r = 34)
    $tinggiBar = fn ($n) => $n > 0 ? max(5, (int) round($n / $a['maks'] * 166)) : 0;
    $lebar = fn ($n, $tot) => $tot > 0 ? round($n / $tot * 100, 2) : 0;

    // Pesan utama di hero: yang paling penting dulu.
    if ($slsAktif === 0) {
        $pesan = 'Belum ada SLS yang dikerjakan. Mulai dari menu Tagging, pilih SLS pertama Anda.';
    } elseif ($dikembalikan > 0) {
        $pesan = "{$dikembalikan} koreksi dikembalikan admin dan perlu diambil ulang lokasinya.";
    } elseif ($slsSelesai === $totalSls) {
        $pesan = 'Hebat! Semua SLS tugas Anda sudah selesai dikerjakan.';
    } else {
        $pesan = "{$slsSelesai} dari {$totalSls} SLS selesai, {$hitungStatus['berjalan']} sedang berjalan"
            . ($sisaPerbaikan > 0 ? ", dan {$sisaPerbaikan} titik masih perlu diperbaiki." : '.');
    }

    // Ikon (stroke, gaya lucide). Dirender lewat $ico('nama').
    $paths = [
        'sun'     => '<circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M2 12h2M20 12h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>',
        'trend'   => '<path d="m22 7-8.5 8.5-5-5L2 17"/><path d="M16 7h6v6"/>',
        'pin'     => '<path d="M20 10c0 6-8 12-8 12S4 16 4 10a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/>',
        'target'  => '<circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>',
        'check'   => '<path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><path d="m9 11 3 3L22 4"/>',
        'flame'   => '<path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.4-.5-2-1-3-1.1-2.1-.2-4 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.2.4-2.4 1.5-3.5.3 1.3 1 2.5 2 3.5Z"/>',
        'arrow'   => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'sparkle' => '<path d="m12 3 1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9L12 3Z"/>',
    ];
    $ico = fn ($n, $s = 20) => '<svg width="'.$s.'" height="'.$s.'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$paths[$n].'</svg>';

    // Chip pembanding KPI: [teks, kelas]
    $selisihHari = $a['hari_ini'] - $a['kemarin'];
    if ($selisihHari > 0)      $chipHari = ['▲ +' . $selisihHari . ' dari kemarin', 'up'];
    elseif ($selisihHari < 0)  $chipHari = ['▼ ' . $selisihHari . ' dari kemarin', 'down'];
    elseif ($a['hari_ini'] > 0) $chipHari = ['Sama seperti kemarin', 'flat'];
    else                       $chipHari = ['Belum ada aktivitas hari ini', 'flat'];

    if ($a['delta_minggu'] !== null) {
        $d = $a['delta_minggu'];
        $chipMinggu = $d > 0 ? ['▲ ' . $d . '% vs 7 hari sebelumnya', 'up']
            : ($d < 0 ? ['▼ ' . abs($d) . '% vs 7 hari sebelumnya', 'down'] : ['Sama dengan 7 hari sebelumnya', 'flat']);
    } else {
        $chipMinggu = [$a['minggu'] > 0 ? 'Belum ada pembanding' : 'Belum ada aktivitas', 'flat'];
    }
    $g = $gps;
    $v = $verifikasi;
@endphp

<style>
    .dp { display:flex; flex-direction:column; gap:16px; color:#0f172a; }
    .dp *, .dp *::before, .dp *::after { box-sizing:border-box; }
    .dp-card { background:#fff; border-radius:20px; border:1px solid rgba(226,232,240,.85); box-shadow:0 1px 2px rgba(15,23,42,.04); padding:20px; min-width:0; }
    .dp-h { font-size:14px; font-weight:700; margin:0; color:#0f172a; }
    .dp-sub { font-size:12px; color:#64748b; margin:2px 0 0; }
    .dp-empty { font-size:13px; color:#64748b; line-height:1.55; background:#f8fafc; border-radius:14px; padding:14px; margin-top:14px; }
    .dp-row { display:grid; gap:16px; grid-template-columns:minmax(0,1fr); }
    @@media (min-width:1024px) {
        .dp-row.r21 { grid-template-columns:minmax(0,2fr) minmax(0,1fr); }
        .dp-row.r3  { grid-template-columns:repeat(3,minmax(0,1fr)); }
    }
    @@media (min-width:768px) and (max-width:1023px) { .dp-row.r3 { grid-template-columns:repeat(2,minmax(0,1fr)); } }

    /* ---------- hero ---------- */
    .dp-hero { position:relative; overflow:hidden; border-radius:28px; color:#fff; padding:24px;
        background:linear-gradient(135deg,#0f766e 0%,#0d9488 55%,#14b8a6 100%); box-shadow:0 14px 32px -16px rgba(15,118,110,.7); }
    .dp-hero::before, .dp-hero::after { content:""; position:absolute; border-radius:9999px; background:rgba(255,255,255,.08); pointer-events:none; }
    .dp-hero::before { width:280px; height:280px; right:-80px; top:-120px; }
    .dp-hero::after  { width:170px; height:170px; right:110px; bottom:-100px; }
    .dp-hero-in { position:relative; z-index:1; display:flex; flex-direction:column; align-items:center; gap:20px; text-align:center; }
    .dp-ring { position:relative; width:124px; height:124px; flex-shrink:0; }
    .dp-ring svg { display:block; }
    .dp-ring-c { position:absolute; inset:0; display:flex; flex-direction:column; align-items:center; justify-content:center; }
    .dp-ring-c b { font-size:28px; font-weight:800; line-height:1; }
    .dp-ring-c span { font-size:9px; letter-spacing:.06em; opacity:.85; margin-top:4px; }
    .dp-hero-t { flex:1; min-width:0; }
    .dp-hero-t small { font-size:13px; color:#ccfbf1; }
    .dp-hero-t h1 { font-size:24px; font-weight:800; letter-spacing:-.01em; margin:2px 0 0; }
    .dp-hero-t p { font-size:14px; line-height:1.55; color:#f0fdfa; margin:8px 0 0; }
    .dp-pills { display:flex; flex-wrap:wrap; gap:8px; margin-top:14px; justify-content:center; }
    .dp-pill { display:inline-flex; align-items:center; gap:6px; padding:6px 12px; border-radius:9999px; background:rgba(255,255,255,.16); font-size:12px; font-weight:600; }
    .dp-pill svg { opacity:.9; }
    .dp-cta { display:inline-flex; align-items:center; justify-content:center; gap:8px; padding:12px 20px; border-radius:14px; background:#fff; color:#115e59; font-size:14px; font-weight:700; text-decoration:none; box-shadow:0 6px 16px -8px rgba(0,0,0,.4); transition:transform .15s, background .15s; flex-shrink:0; }
    .dp-cta:hover { background:#f0fdfa; transform:translateY(-1px); }
    .dp-cta small { display:block; font-size:11px; font-weight:600; color:#0d9488; }
    @@media (min-width:640px) {
        .dp-hero { padding:30px 32px; }
        .dp-hero-in { flex-direction:row; text-align:left; gap:32px; }
        .dp-pills { justify-content:flex-start; }
    }

    /* ---------- KPI ---------- */
    .dp-kpis { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:12px; }
    @@media (min-width:1024px) { .dp-kpis { grid-template-columns:repeat(4,minmax(0,1fr)); } }
    .dp-kpi { background:#fff; border-radius:20px; border:1px solid rgba(226,232,240,.85); padding:16px; transition:transform .15s, box-shadow .15s; min-width:0; }
    .dp-kpi:hover { transform:translateY(-2px); box-shadow:0 10px 24px -14px rgba(15,23,42,.25); }
    .dp-ico { width:36px; height:36px; border-radius:12px; display:flex; align-items:center; justify-content:center; color:var(--c); background:var(--bg); }
    .dp-kpi-v { font-size:28px; font-weight:800; line-height:1; margin-top:12px; font-variant-numeric:tabular-nums; letter-spacing:-.02em; }
    .dp-kpi-v small { font-size:13px; font-weight:700; color:#64748b; margin-left:2px; letter-spacing:0; }
    .dp-kpi-l { font-size:12px; color:#64748b; margin-top:4px; }
    .dp-chip { display:inline-block; margin-top:10px; padding:3px 9px; border-radius:9999px; font-size:11px; font-weight:600; }
    .dp-chip.up   { background:#ecfdf5; color:#047857; }
    .dp-chip.down { background:#fffbeb; color:#b45309; }
    .dp-chip.flat { background:#f1f5f9; color:#64748b; }

    /* ---------- grafik aktivitas ---------- */
    .dp-top { display:flex; flex-wrap:wrap; justify-content:space-between; gap:8px 16px; align-items:flex-start; }
    .dp-legend { display:flex; gap:14px; font-size:12px; color:#475569; }
    .dp-legend i, .dp-leg i { display:inline-block; width:8px; height:8px; border-radius:9999px; margin-right:6px; }
    .dp-chart { position:relative; margin-top:18px; }
    .dp-plot { height:200px; display:flex; align-items:flex-end; gap:4px; border-bottom:1px solid #e2e8f0;
        background-image:linear-gradient(to top,#eef2f7 1px,transparent 1px); background-size:100% 25%; }
    .dp-col { flex:1; min-width:0; height:100%; display:flex; flex-direction:column; justify-content:flex-end; align-items:center; }
    .dp-val { font-size:10px; font-weight:700; color:#475569; margin-bottom:3px; font-variant-numeric:tabular-nums; }
    .dp-stack { width:68%; max-width:26px; display:flex; flex-direction:column; border-radius:7px 7px 2px 2px; overflow:hidden; transform-origin:bottom; }
    .dp-stack span { display:block; width:100%; }
    .dp-xl { display:flex; gap:4px; margin-top:6px; }
    .dp-xl div { flex:1; min-width:0; text-align:center; font-size:10px; line-height:1.25; color:#94a3b8; }
    .dp-xl div b { display:block; font-size:11px; color:#475569; font-weight:700; }
    .dp-xl div.now b { color:#fff; background:#0d9488; border-radius:9999px; padding:1px 0; margin:0 auto; max-width:22px; }
    .dp-xl div.now { color:#0f766e; font-weight:700; }
    .dp-nodata { position:absolute; inset:0 0 34px 0; display:flex; align-items:center; justify-content:center; text-align:center; padding:0 24px; font-size:13px; color:#64748b; line-height:1.5; }
    .dp-stats { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; margin-top:18px; padding-top:16px; border-top:1px dashed #e2e8f0; }
    @@media (min-width:640px) { .dp-stats { grid-template-columns:repeat(4,minmax(0,1fr)); } }
    .dp-stat b { display:block; font-size:18px; font-weight:800; font-variant-numeric:tabular-nums; }
    .dp-stat b small { font-size:12px; font-weight:600; color:#64748b; }
    .dp-stat span { font-size:11px; color:#64748b; }
    @@media (prefers-reduced-motion:no-preference) {
        @@keyframes dp-grow { from { transform:scaleY(0); } to { transform:scaleY(1); } }
        .dp-stack { animation:dp-grow .6s cubic-bezier(.2,.8,.2,1) both; }
    }

    /* ---------- perlu perhatian ---------- */
    .dp-att { --bg:#f1f5f9; --fg:#475569; --bd:#e2e8f0; display:flex; flex-wrap:wrap; align-items:center; gap:10px 12px; padding:12px; border-radius:14px; background:var(--bg); border:1px solid var(--bd); }
    .dp-att.merah  { --bg:#fef2f2; --fg:#b91c1c; --bd:#fecaca; }
    .dp-att.kuning { --bg:#fffbeb; --fg:#b45309; --bd:#fde68a; }
    .dp-att.biru   { --bg:#eff6ff; --fg:#1d4ed8; --bd:#bfdbfe; }
    .dp-att-n { min-width:38px; height:38px; padding:0 8px; border-radius:11px; background:var(--fg); color:#fff; display:flex; align-items:center; justify-content:center; font-size:15px; font-weight:800; font-variant-numeric:tabular-nums; }
    .dp-att-t { flex:1; min-width:140px; }
    .dp-att-t b { display:block; font-size:13px; color:var(--fg); }
    .dp-att-t span { display:block; font-size:12px; color:#475569; line-height:1.4; margin-top:1px; }
    .dp-att-a { font-size:12px; font-weight:700; color:var(--fg); text-decoration:none; white-space:nowrap; }
    .dp-att-a:hover { text-decoration:underline; }
    .dp-ok { display:flex; gap:12px; align-items:center; padding:14px; border-radius:14px; background:#ecfdf5; color:#047857; font-size:13px; line-height:1.45; }
    .dp-ok svg { flex-shrink:0; }
    .dp-link { display:inline-flex; align-items:center; gap:6px; font-size:13px; font-weight:700; color:#0f766e; text-decoration:none; }
    .dp-link:hover { text-decoration:underline; }

    /* ---------- bar segmen & legenda ---------- */
    .dp-seg { display:flex; height:10px; border-radius:9999px; overflow:hidden; background:#f1f5f9; }
    .dp-seg span { display:block; height:100%; border-right:2px solid #fff; }
    .dp-seg span:last-child { border-right:0; }
    .dp-leg { display:flex; flex-wrap:wrap; gap:6px 14px; margin-top:12px; font-size:12px; color:#475569; }
    .dp-leg b { color:#0f172a; font-variant-numeric:tabular-nums; }
    .dp-big { font-size:34px; font-weight:800; letter-spacing:-.02em; line-height:1; font-variant-numeric:tabular-nums; }
    .dp-big small { font-size:15px; font-weight:700; color:#64748b; letter-spacing:0; margin-left:2px; }
    .dp-mini { position:relative; width:88px; height:88px; flex-shrink:0; }
    .dp-mini div { position:absolute; inset:0; display:flex; align-items:center; justify-content:center; font-size:18px; font-weight:800; }
    .dp-flex { display:flex; align-items:center; gap:16px; margin-top:16px; }
    .dp-note { font-size:12px; color:#64748b; line-height:1.5; margin-top:12px; }
    .dp-tag { display:inline-block; padding:3px 10px; border-radius:9999px; background:#f1f5f9; color:#475569; font-size:11px; font-weight:600; margin:10px 6px 0 0; }

    /* ---------- kontribusi per kegiatan ---------- */
    .dp-kg { max-height:300px; overflow:auto; margin-top:14px; display:flex; flex-direction:column; gap:16px; padding-right:2px; }
    .dp-kg-h { display:flex; justify-content:space-between; gap:10px; align-items:baseline; }
    .dp-kg-h b { font-size:13px; min-width:0; overflow-wrap:anywhere; }
    .dp-kg-h span { font-size:12px; font-weight:800; color:#0f766e; font-variant-numeric:tabular-nums; }
    .dp-bar { height:8px; border-radius:9999px; background:#f1f5f9; overflow:hidden; margin-top:7px; }
    .dp-bar i { display:block; height:100%; border-radius:9999px; background:linear-gradient(90deg,#14b8a6,#0d9488); }
    .dp-bar i.done { background:#10b981; }
    .dp-kg-s { font-size:11px; color:#64748b; margin-top:6px; line-height:1.45; }

    /* ---------- timeline ---------- */
    .dp-tl { list-style:none; margin:16px 0 0; padding:0; }
    .dp-tl li { position:relative; display:flex; gap:12px; padding-bottom:16px; }
    .dp-tl li:last-child { padding-bottom:0; }
    .dp-tl li::before { content:""; position:absolute; left:5px; top:16px; bottom:-2px; width:2px; background:#e2e8f0; }
    .dp-tl li:last-child::before { display:none; }
    .dp-dot { width:12px; height:12px; border-radius:9999px; background:var(--c); border:3px solid #fff; box-shadow:0 0 0 2px var(--c); margin-top:4px; flex-shrink:0; }
    .dp-tl-b { flex:1; min-width:0; }
    .dp-tl-b b { display:block; font-size:13px; }
    .dp-tl-b span { display:block; font-size:12px; color:#64748b; margin-top:1px; overflow-wrap:anywhere; }
    .dp-tl time { font-size:11px; color:#94a3b8; white-space:nowrap; margin-top:2px; }

    .dp-guide summary { cursor:pointer; user-select:none; font-size:13px; font-weight:700; color:#334155; list-style:none; display:flex; align-items:center; gap:8px; }
    .dp-guide summary::-webkit-details-marker { display:none; }
    .dp-steps { display:grid; gap:12px; margin-top:14px; grid-template-columns:minmax(0,1fr); }
    @@media (min-width:640px) { .dp-steps { grid-template-columns:repeat(3,minmax(0,1fr)); } }
    .dp-steps div { background:#f8fafc; border-radius:14px; padding:14px; font-size:12px; line-height:1.55; color:#475569; }
    .dp-steps b.t { display:block; font-size:13px; color:#0f172a; margin-bottom:3px; }
</style>

<div class="py-6 sm:py-8">
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
<div class="dp">

@if ($totalSls === 0)
    <div class="dp-card" style="text-align:center;padding:40px 20px;">
        <div style="width:56px;height:56px;margin:0 auto 14px;border-radius:18px;background:#ccfbf1;color:#0f766e;display:flex;align-items:center;justify-content:center;">{!! $ico('pin', 28) !!}</div>
        <p style="font-size:16px;font-weight:700;margin:0;">{{ $sapaan }}, {{ $namaDepan }}!</p>
        <p style="font-size:14px;font-weight:600;color:#334155;margin:12px 0 0;">Belum ada SLS yang ditugaskan kepada Anda</p>
        <p style="font-size:13px;color:#64748b;margin:4px 0 0;">Hubungi admin untuk mendapatkan penugasan. Setelah ditugaskan, ringkasan kinerja Anda tampil di sini.</p>
    </div>
@else

    {{-- ================= HERO ================= --}}
    <section class="dp-hero">
        <div class="dp-hero-in">
            <div class="dp-ring" role="img" aria-label="{{ $persenSelesai }} persen SLS selesai">
                <svg width="124" height="124" viewBox="0 0 124 124">
                    <circle cx="62" cy="62" r="52" fill="none" stroke="rgba(255,255,255,.22)" stroke-width="10"/>
                    <circle cx="62" cy="62" r="52" fill="none" stroke="#fff" stroke-width="10" stroke-linecap="round"
                            stroke-dasharray="{{ round($keliling * $persenSelesai / 100, 2) }} {{ round($keliling, 2) }}"
                            transform="rotate(-90 62 62)"/>
                </svg>
                <div class="dp-ring-c">
                    <b>{{ $persenSelesai }}%</b>
                    <span>SLS SELESAI</span>
                </div>
            </div>

            <div class="dp-hero-t">
                <small>{{ $sapaan }}, {{ $namaDepan }}</small>
                <h1>Ringkasan kinerja Anda</h1>
                <p>{{ $pesan }}</p>

                <div class="dp-pills">
                    @forelse (array_slice($insight, 0, 3) as $kalimat)
                        <span class="dp-pill">{!! $ico('sparkle', 14) !!} {{ $kalimat }}</span>
                    @empty
                        <span class="dp-pill">{!! $ico('sparkle', 14) !!} Sorotan kinerja akan muncul setelah Anda mulai tagging.</span>
                    @endforelse
                </div>
            </div>

            @if ($lanjut)
                <a class="dp-cta" href="{{ route('peta.index', ['mode' => 'tagging', 'kegiatan' => $lanjut->kegiatan_id, 'sls' => $lanjut->sls_id]) }}">
                    <span>
                        {{ $lanjut->status === 'berjalan' ? 'Lanjutkan tagging' : 'Mulai tagging' }}
                        <small>{{ $lanjut->nama_sls }}</small>
                    </span>
                    {!! $ico('arrow', 18) !!}
                </a>
            @endif
        </div>
    </section>

    {{-- ================= KPI ================= --}}
    <section class="dp-kpis" aria-label="Angka utama">
        <div class="dp-kpi">
            <div class="dp-ico" style="--c:#0d9488;--bg:#ccfbf1">{!! $ico('sun') !!}</div>
            <div class="dp-kpi-v">{{ $fmt($a['hari_ini']) }}</div>
            <div class="dp-kpi-l">Aktivitas hari ini</div>
            <span class="dp-chip {{ $chipHari[1] }}">{{ $chipHari[0] }}</span>
        </div>
        <div class="dp-kpi">
            <div class="dp-ico" style="--c:#4f46e5;--bg:#e0e7ff">{!! $ico('trend') !!}</div>
            <div class="dp-kpi-v">{{ $fmt($a['minggu']) }}</div>
            <div class="dp-kpi-l">Aktivitas 7 hari terakhir</div>
            <span class="dp-chip {{ $chipMinggu[1] }}">{{ $chipMinggu[0] }}</span>
        </div>
        <div class="dp-kpi">
            <div class="dp-ico" style="--c:#0369a1;--bg:#e0f2fe">{!! $ico('pin') !!}</div>
            <div class="dp-kpi-v">{{ $fmt($totalTitik) }}</div>
            <div class="dp-kpi-l">Total titik yang saya tag</div>
            <span class="dp-chip flat">di {{ $slsAktif }} dari {{ $totalSls }} SLS</span>
        </div>
        <div class="dp-kpi">
            <div class="dp-ico" style="--c:#b45309;--bg:#fef3c7">{!! $ico('target') !!}</div>
            <div class="dp-kpi-v">
                @if ($g['rata'] !== null) ±{{ $fmt($g['rata'], 1) }}<small>m</small> @else — @endif
            </div>
            <div class="dp-kpi-l">Rata-rata akurasi GPS</div>
            @if ($g['persen_baik'] !== null)
                <span class="dp-chip {{ $g['persen_baik'] >= 70 ? 'up' : 'flat' }}">{{ $g['persen_baik'] }}% titik berakurasi baik</span>
            @else
                <span class="dp-chip flat">Belum ada data GPS</span>
            @endif
        </div>
    </section>

    {{-- ================= TREN + PERLU PERHATIAN ================= --}}
    <div class="dp-row r21">
        <section class="dp-card">
            <div class="dp-top">
                <div>
                    <h2 class="dp-h">Tren aktivitas 14 hari</h2>
                    <p class="dp-sub">Titik baru dan koreksi yang Anda kerjakan per hari (WIB)</p>
                </div>
                <div class="dp-legend">
                    <span><i style="background:#14b8a6"></i>Titik baru</span>
                    <span><i style="background:#6366f1"></i>Koreksi</span>
                </div>
            </div>

            <div class="dp-chart">
                <div class="dp-plot" role="img" aria-label="Grafik batang aktivitas 14 hari terakhir">
                    @foreach ($a['grafik'] as $h)
                        <div class="dp-col" title="{{ $h['label'] }}: {{ $h['tag'] }} titik baru, {{ $h['kor'] }} koreksi">
                            @if ($h['total'] > 0)
                                <span class="dp-val">{{ $h['total'] }}</span>
                                <div class="dp-stack" style="animation-delay:{{ $loop->index * 30 }}ms">
                                    @if ($h['kor'] > 0)<span style="height:{{ $tinggiBar($h['kor']) }}px;background:#6366f1"></span>@endif
                                    @if ($h['tag'] > 0)<span style="height:{{ $tinggiBar($h['tag']) }}px;background:linear-gradient(180deg,#2dd4bf,#0d9488)"></span>@endif
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
                <div class="dp-xl">
                    @foreach ($a['grafik'] as $h)
                        <div class="{{ $h['hari_ini'] ? 'now' : '' }}"><b>{{ $h['tgl'] }}</b>{{ $h['hari'] }}</div>
                    @endforeach
                </div>
                @unless ($a['ada_data'])
                    <div class="dp-nodata">Belum ada aktivitas dalam 14 hari terakhir. Grafik akan terisi setelah Anda menandai atau mengoreksi titik.</div>
                @endunless
            </div>

            <div class="dp-stats">
                <div class="dp-stat"><b>{{ $a['hari_aktif'] }}<small> / 14 hari</small></b><span>Hari aktif</span></div>
                <div class="dp-stat"><b>{{ $a['hari_aktif'] ? $fmt($a['rata_aktif'], 1) : '—' }}</b><span>Rata-rata per hari aktif</span></div>
                <div class="dp-stat"><b>{{ $a['tersibuk'] ? $a['tersibuk']['hari'] . ', ' . $a['tersibuk']['tgl'] : '—' }}</b><span>{{ $a['tersibuk'] ? 'Hari terproduktif (' . $a['tersibuk']['total'] . ')' : 'Hari terproduktif' }}</span></div>
                <div class="dp-stat"><b style="color:{{ $a['streak'] >= 2 ? '#c2410c' : '#0f172a' }}">{{ $a['streak'] }}<small> hari</small></b><span>Aktif berturut-turut</span></div>
            </div>
        </section>

        <section class="dp-card" style="display:flex;flex-direction:column;">
            <h2 class="dp-h">Perlu perhatian</h2>
            <p class="dp-sub">Yang sebaiknya Anda tindak lanjuti</p>

            <div style="display:flex;flex-direction:column;gap:10px;margin-top:14px;flex:1;">
                @forelse ($perhatian as $b)
                    <div class="dp-att {{ $b['tingkat'] }}">
                        <div class="dp-att-n">{{ $fmt($b['angka']) }}</div>
                        <div class="dp-att-t"><b>{{ $b['judul'] }}</b><span>{{ $b['detail'] }}</span></div>
                        @if ($b['url'] && $b['aksi'])
                            <a class="dp-att-a" href="{{ $b['url'] }}">{{ $b['aksi'] }} →</a>
                        @endif
                    </div>
                @empty
                    <div class="dp-ok">
                        {!! $ico('check', 24) !!}
                        <span><b>Semua beres.</b> Tidak ada koreksi yang dikembalikan atau titik yang perlu diperbaiki saat ini.</span>
                    </div>
                @endforelse
            </div>

            <div style="margin-top:16px;padding-top:14px;border-top:1px dashed #e2e8f0;">
                <a class="dp-link" href="{{ route('tagging.index') }}">Daftar SLS &amp; mulai tagging di menu Tagging {!! $ico('arrow', 15) !!}</a>
            </div>
        </section>
    </div>

    {{-- ================= KUALITAS: VERIFIKASI | GPS | KEGIATAN ================= --}}
    <div class="dp-row r3">

        {{-- Hasil verifikasi --}}
        <section class="dp-card">
            <h2 class="dp-h">Hasil verifikasi koreksi</h2>
            <p class="dp-sub">Penilaian admin atas koreksi titik Anda</p>

            @if ($v['total'] === 0)
                <div class="dp-empty">Belum ada koreksi. Setelah Anda memperbaiki titik bermasalah di peta, hasil penilaian admin muncul di sini.</div>
            @else
                <div class="dp-flex">
                    <div class="dp-mini" role="img" aria-label="{{ $v['persen_lolos'] ?? 0 }} persen koreksi lolos">
                        <svg width="88" height="88" viewBox="0 0 88 88">
                            <circle cx="44" cy="44" r="34" fill="none" stroke="#f1f5f9" stroke-width="9"/>
                            @if ($v['persen_lolos'] !== null)
                                <circle cx="44" cy="44" r="34" fill="none" stroke="#10b981" stroke-width="9" stroke-linecap="round"
                                        stroke-dasharray="{{ round($kelilingKecil * $v['persen_lolos'] / 100, 2) }} {{ round($kelilingKecil, 2) }}"
                                        transform="rotate(-90 44 44)"/>
                            @endif
                        </svg>
                        <div>{{ $v['persen_lolos'] !== null ? $v['persen_lolos'] . '%' : '—' }}</div>
                    </div>
                    <div style="min-width:0;">
                        <p style="font-size:13px;font-weight:700;margin:0;">{{ $v['persen_lolos'] !== null ? 'Koreksi lolos verifikasi' : 'Belum ada yang diputuskan' }}</p>
                        <p class="dp-sub" style="margin-top:3px;">{{ $v['persen_lolos'] !== null ? 'Dari koreksi yang sudah dinilai admin' : 'Menunggu penilaian admin' }}</p>
                    </div>
                </div>

                <div style="margin-top:16px;">
                    <div class="dp-seg" aria-hidden="true">
                        <span style="width:{{ $lebar($v['terverifikasi'], $v['total']) }}%;background:#10b981"></span>
                        <span style="width:{{ $lebar($v['menunggu'], $v['total']) }}%;background:#f59e0b"></span>
                        <span style="width:{{ $lebar($v['dikembalikan'], $v['total']) }}%;background:#ef4444"></span>
                    </div>
                    <div class="dp-leg">
                        <span><i style="background:#10b981"></i>Terverifikasi <b>{{ $fmt($v['terverifikasi']) }}</b></span>
                        <span><i style="background:#f59e0b"></i>Menunggu <b>{{ $fmt($v['menunggu']) }}</b></span>
                        <span><i style="background:#ef4444"></i>Dikembalikan <b>{{ $fmt($v['dikembalikan']) }}</b></span>
                    </div>
                </div>
            @endif
        </section>

        {{-- Kualitas GPS --}}
        <section class="dp-card">
            <h2 class="dp-h">Kualitas lokasi GPS</h2>
            <p class="dp-sub">Akurasi saat Anda menandai titik</p>

            @if ($g['ada_akurasi'] === 0)
                <div class="dp-empty">
                    {{ $g['total'] > 0 ? 'Titik Anda belum memiliki catatan akurasi GPS.' : 'Belum ada titik hasil tagging. Akurasi GPS Anda akan dinilai di sini.' }}
                </div>
            @else
                <div style="margin-top:16px;">
                    <div class="dp-big">±{{ $fmt($g['rata'], 1) }}<small>m</small></div>
                    <p class="dp-sub" style="margin-top:6px;">rata-rata dari {{ $fmt($g['ada_akurasi']) }} titik (makin kecil makin baik)</p>
                </div>

                <div style="margin-top:16px;">
                    <div class="dp-seg" aria-hidden="true">
                        <span style="width:{{ $lebar($g['baik'], $g['ada_akurasi']) }}%;background:#10b981"></span>
                        <span style="width:{{ $lebar($g['cukup'], $g['ada_akurasi']) }}%;background:#f59e0b"></span>
                        <span style="width:{{ $lebar($g['rendah'], $g['ada_akurasi']) }}%;background:#ef4444"></span>
                    </div>
                    <div class="dp-leg">
                        <span><i style="background:#10b981"></i>Baik ≤{{ $fmt($g['batas_baik']) }} m <b>{{ $fmt($g['baik']) }}</b></span>
                        <span><i style="background:#f59e0b"></i>Cukup ≤{{ $fmt($g['batas_cukup']) }} m <b>{{ $fmt($g['cukup']) }}</b></span>
                        <span><i style="background:#ef4444"></i>Rendah <b>{{ $fmt($g['rendah']) }}</b></span>
                    </div>
                </div>

                @if ($g['via_gps'] + $g['via_peta'] > 0)
                    <div>
                        <span class="dp-tag">Lewat GPS {{ $fmt($g['via_gps']) }}</span>
                        <span class="dp-tag">Lewat peta {{ $fmt($g['via_peta']) }}</span>
                    </div>
                @endif
            @endif
        </section>

        {{-- Kontribusi per kegiatan --}}
        <section class="dp-card">
            <h2 class="dp-h">Kontribusi per kegiatan</h2>
            <p class="dp-sub">Cakupan SLS dan titik hasil kerja Anda</p>

            <div class="dp-kg">
                @foreach ($rekap as $kg)
                    @php
                        $kon = $kontribusi->get($kg->kegiatan_id);
                        $bersama = $kon && (int) $kon->total > (int) $kon->saya;
                        $selesaiSemua = $kg->total > 0 && $kg->selesai === $kg->total;
                    @endphp
                    <div>
                        <div class="dp-kg-h">
                            <b>{{ $kg->kegiatan?->nama_kegiatan ?? 'Kegiatan' }}</b>
                            <span>{{ $kg->persen }}%</span>
                        </div>
                        <div class="dp-bar"><i class="{{ $selesaiSemua ? 'done' : '' }}" style="width:{{ $kg->persen }}%"></i></div>
                        <p class="dp-kg-s">
                            {{ $kg->aktif }} dari {{ $kg->total }} SLS dikerjakan · {{ $fmt($kg->titik_saya) }} titik Anda
                            @if ($bersama)
                                ({{ (int) round($kon->saya / $kon->total * 100) }}% dari {{ $fmt($kon->total) }} titik tagging di SLS Anda)
                            @endif
                        </p>
                    </div>
                @endforeach
            </div>
        </section>
    </div>

    {{-- ================= AKTIVITAS TERBARU + STATUS SLS ================= --}}
    <div class="dp-row r21">
        <section class="dp-card">
            <h2 class="dp-h">Aktivitas terbaru</h2>
            <p class="dp-sub">Jejak pekerjaan terakhir Anda</p>

            @if ($terbaru->isEmpty())
                <div class="dp-empty">Belum ada aktivitas. Titik yang Anda tandai atau koreksi akan tercatat di sini.</div>
            @else
                @php
                    $warnaJenis = ['tag' => '#0d9488', 'kor' => '#6366f1', 'ok' => '#10b981', 'kembali' => '#ef4444'];
                @endphp
                <ul class="dp-tl">
                    @foreach ($terbaru as $e)
                        <li>
                            <span class="dp-dot" style="--c:{{ $warnaJenis[$e->jenis] ?? '#94a3b8' }}"></span>
                            <div class="dp-tl-b">
                                <b>{{ $e->judul }}</b>
                                <span>{{ $e->objek }} · {{ $e->sls }}@if ($e->info) · {{ $e->info }}@endif</span>
                            </div>
                            <time title="{{ $e->lengkap }}">{{ $e->relatif }}</time>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="dp-card" style="display:flex;flex-direction:column;">
            <h2 class="dp-h">Status SLS saya</h2>
            <p class="dp-sub">{{ $totalSls }} SLS dari {{ $rekap->count() }} kegiatan</p>

            <div style="margin-top:18px;">
                <div class="dp-seg" style="height:14px;" aria-hidden="true">
                    <span style="width:{{ $lebar($hitungStatus['selesai'], $totalSls) }}%;background:#10b981"></span>
                    <span style="width:{{ $lebar($hitungStatus['berjalan'], $totalSls) }}%;background:#f59e0b"></span>
                    <span style="width:{{ $lebar($hitungStatus['belum'], $totalSls) }}%;background:#cbd5e1"></span>
                </div>
                <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:8px;margin-top:16px;text-align:center;">
                    <div><b style="display:block;font-size:22px;font-weight:800;color:#059669;">{{ $hitungStatus['selesai'] }}</b><span style="font-size:11px;color:#64748b;">Selesai</span></div>
                    <div><b style="display:block;font-size:22px;font-weight:800;color:#d97706;">{{ $hitungStatus['berjalan'] }}</b><span style="font-size:11px;color:#64748b;">Berjalan</span></div>
                    <div><b style="display:block;font-size:22px;font-weight:800;color:#64748b;">{{ $hitungStatus['belum'] }}</b><span style="font-size:11px;color:#64748b;">Belum mulai</span></div>
                </div>
            </div>

            <p class="dp-note" style="margin-top:auto;padding-top:16px;">
                <b style="color:#334155;">Selesai</b>: sudah dikerjakan dan tidak ada titik yang perlu diperbaiki.
                <b style="color:#334155;">Berjalan</b>: sudah ada pekerjaan, masih ada titik yang perlu diperbaiki.
            </p>
        </section>
    </div>

    {{-- ================= PANDUAN ================= --}}
    <details class="dp-card dp-guide" @if ($slsAktif === 0) open @endif>
        <summary>{!! $ico('sparkle', 16) !!} Panduan singkat tagging</summary>
        <div class="dp-steps">
            <div><b class="t">1. Buka SLS</b>Pilih SLS di menu <b>Tagging</b>, lalu tekan <b>Mulai / Lanjutkan tagging</b>.</div>
            <div><b class="t">2. Aktifkan Mode Tagging</b>Di peta, nyalakan <b>Mode Tagging</b>, lalu datangi bangunan yang akan ditandai.</div>
            <div><b class="t">3. Ambil lokasi GPS</b>Tekan <b>Ambil Lokasi Saya</b>, tunggu akurasi ±{{ $akurasiMaks }} m atau lebih baik, lalu <b>Gunakan lokasi ini</b>.</div>
        </div>
        <p class="dp-note">Tips: pakai HP, nyalakan GPS akurasi tinggi, dan berdiri di area terbuka. Di dalam ruangan atau di bawah atap, sinyal GPS sering kurang akurat.</p>
    </details>

@endif

</div>
</div>
</div>
</x-app-layout>
