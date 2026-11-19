<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">Dashboard</h2>
    </x-slot>

@php
    $namaDepan = \Illuminate\Support\Str::of($user->name)->trim()->before(' ');

    // Warna aksen per status (inline supaya tampil benar walau CSS Tailwind belum dikompilasi ulang).
    $aksen = [
        'belum'    => ['#94a3b8', 'Belum mulai', 'bg-slate-100 text-slate-600'],
        'berjalan' => ['#f59e0b', 'Berjalan',    'bg-amber-50 text-amber-700'],
        'selesai'  => ['#10b981', 'Selesai',     'bg-emerald-50 text-emerald-700'],
    ];

    if ($slsAktif === 0) {
        $pesan = 'Belum ada SLS yang dikerjakan. Mulai dari SLS pertama di bawah ini.';
    } elseif ($slsSelesai === $totalSls) {
        $pesan = 'Hebat! Semua SLS tugas Anda sudah selesai dikerjakan.';
    } else {
        $pesan = "Anda sudah mengerjakan {$slsAktif} dari {$totalSls} SLS."
            . ($sisaPerbaikan > 0 ? " Masih ada {$sisaPerbaikan} titik yang perlu diperbaiki." : '');
    }

    // Petunjuk satu baris untuk tiap kartu SLS.
    $petunjuk = function ($r) {
        if ($r->dikembalikan > 0) return "Admin mengembalikan {$r->dikembalikan} koreksi. Ambil ulang lokasinya.";
        return match ($r->status) {
            'belum'    => 'Datangi lokasi, lalu mulai tandai titik bangunan.',
            'berjalan' => $r->sisa_perbaikan > 0 ? "Masih ada {$r->sisa_perbaikan} titik yang perlu diperbaiki." : 'Lanjutkan menandai titik.',
            default    => 'Tidak ada titik yang perlu diperbaiki. Kerja bagus!',
        };
    };
@endphp

<div class="py-6 sm:py-8">
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{ f: 'semua', k: 'semua' }">

    @if ($totalSls === 0)
        <div class="p-10 bg-white rounded-2xl ring-1 ring-slate-200/70 text-center">
            <p class="text-4xl mb-3">🗂️</p>
            <p class="text-base font-semibold text-slate-800">{{ $sapaan }}, {{ $namaDepan }}!</p>
            <p class="text-sm font-semibold text-slate-700 mt-3">Belum ada SLS yang ditugaskan kepada Anda</p>
            <p class="text-sm text-slate-500 mt-1">Hubungi admin untuk mendapatkan penugasan. Setelah ditugaskan, progres kerja Anda akan tampil di sini.</p>
        </div>
    @else

    {{-- ================= RINGKASAN PROGRES ================= --}}
    <div class="rounded-3xl p-5 sm:p-7 text-white shadow-lg"
         style="background: linear-gradient(135deg, #0f766e 0%, #0d9488 55%, #14b8a6 100%);">
        <div class="flex flex-col sm:flex-row sm:items-center gap-5 sm:gap-8">

            {{-- Cincin progres SLS --}}
            <div class="shrink-0 mx-auto sm:mx-0 relative" style="width:116px;height:116px;">
                <div style="width:116px;height:116px;border-radius:9999px;
                            background: conic-gradient(#ffffff {{ $persenSls * 3.6 }}deg, rgba(255,255,255,.25) 0deg);"></div>
                <div style="position:absolute;inset:11px;border-radius:9999px;background:#0f8f84;
                            display:flex;flex-direction:column;align-items:center;justify-content:center;">
                    <span style="font-size:26px;font-weight:800;line-height:1;">{{ $persenSls }}%</span>
                    <span style="font-size:9px;opacity:.85;margin-top:3px;letter-spacing:.04em;">SLS DIKERJAKAN</span>
                </div>
            </div>

            <div class="flex-1 min-w-0 text-center sm:text-left">
                <p class="text-sm text-teal-100">{{ $sapaan }}, {{ $namaDepan }} 👋</p>
                <h1 class="text-2xl sm:text-3xl font-bold tracking-tight mt-0.5">Progres Penugasan Saya</h1>
                <p class="text-sm sm:text-base text-teal-50 mt-2 leading-relaxed">{{ $pesan }}</p>

                <div class="mt-3 flex flex-wrap justify-center sm:justify-start gap-2 text-xs font-semibold">
                    <span class="px-3 py-1.5 rounded-full" style="background:rgba(255,255,255,.18)">{{ $slsAktif }} dari {{ $totalSls }} SLS dikerjakan</span>
                    <span class="px-3 py-1.5 rounded-full" style="background:rgba(255,255,255,.18)">{{ $slsSelesai }} SLS selesai</span>
                    <span class="px-3 py-1.5 rounded-full" style="background:rgba(255,255,255,.18)">{{ number_format($totalTitik) }} titik di-tag</span>
                </div>
                @if ($rekap->count() > 1)
                    <p class="mt-2 text-xs text-teal-100">Gabungan {{ $rekap->count() }} kegiatan. Rinciannya ada di "Progres per kegiatan" di bawah.</p>
                @endif

                @if ($lanjut)
                    <a href="{{ route('peta.index', ['mode' => 'tagging', 'kegiatan' => $lanjut->kegiatan_id, 'sls' => $lanjut->sls_id]) }}"
                       class="mt-4 inline-flex flex-wrap items-center justify-center gap-x-2 px-5 py-3 rounded-xl bg-white text-teal-800 text-sm font-bold shadow hover:bg-teal-50 transition">
                        {{ $lanjut->status === 'berjalan' ? 'Lanjutkan tagging' : 'Mulai tagging' }}
                        <span class="font-semibold text-teal-600">· {{ $lanjut->nama_sls }}</span>
                        <span aria-hidden="true">→</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- ================= KARTU ANGKA ================= --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mt-4">
        <div class="bg-white rounded-2xl ring-1 ring-slate-200/70 p-4">
            <p class="text-xl">📍</p>
            <p class="text-2xl font-bold text-slate-900 mt-1 tabular-nums">{{ number_format($totalTitik) }}</p>
            <p class="text-xs text-slate-500">Titik yang saya tag</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-slate-200/70 p-4">
            <p class="text-xl">⏳</p>
            <p class="text-2xl font-bold text-slate-900 mt-1 tabular-nums">{{ number_format($menunggu) }}</p>
            <p class="text-xs text-slate-500">Koreksi menunggu verifikasi</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-slate-200/70 p-4">
            <p class="text-xl">✅</p>
            <p class="text-2xl font-bold text-slate-900 mt-1 tabular-nums">{{ number_format($terverifikasi) }}</p>
            <p class="text-xs text-slate-500">Koreksi terverifikasi</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-slate-200/70 p-4">
            <p class="text-xl">🛠️</p>
            <p class="text-2xl font-bold tabular-nums mt-1" style="color: {{ $sisaPerbaikan > 0 ? '#b45309' : '#0f172a' }}">{{ number_format($sisaPerbaikan) }}</p>
            <p class="text-xs text-slate-500">Titik masih perlu diperbaiki</p>
        </div>
    </div>

    @if ($dikembalikan > 0)
        <div class="mt-4 p-4 rounded-2xl text-sm leading-relaxed" style="background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;">
            <b>{{ $dikembalikan }} koreksi dikembalikan admin.</b>
            Buka SLS yang bergaris merah di bawah, lalu ambil ulang lokasi titiknya.
        </div>
    @endif

    {{-- ================= PROGRES PER KEGIATAN ================= --}}
    <div class="mt-6">
        <div class="flex items-baseline justify-between mb-3">
            <h2 class="text-base font-bold text-slate-800">Progres per kegiatan</h2>
            @if ($rekap->count() > 1)
                <span class="text-[11px] text-slate-400">Ketuk kartu untuk menampilkan SLS kegiatan itu saja</span>
            @endif
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 {{ $rekap->count() > 2 ? 'xl:grid-cols-3' : '' }} gap-3">
            @foreach ($rekap as $kg)
                <button type="button" @click="k = (k === '{{ $kg->kegiatan_id }}' ? 'semua' : '{{ $kg->kegiatan_id }}')"
                    :class="k === '{{ $kg->kegiatan_id }}' ? 'ring-2 ring-teal-500' : 'ring-1 ring-slate-200/70'"
                    class="text-left bg-white rounded-2xl p-4 sm:p-5 shadow-sm hover:shadow transition w-full">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-[15px] font-bold text-slate-900 break-words">{{ $kg->kegiatan?->nama_kegiatan ?? 'Kegiatan' }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                {{ $kg->kegiatan?->kode_kegiatan }}@if ($kg->kegiatan?->periode) · {{ $kg->kegiatan->periode }}@endif
                            </p>
                        </div>
                        <span class="shrink-0 text-xl font-extrabold tabular-nums" style="color: {{ $kg->persen >= 100 ? '#059669' : '#0f766e' }}">{{ $kg->persen }}%</span>
                    </div>

                    <div class="mt-3" style="height:10px;border-radius:9999px;background:#f1f5f9;overflow:hidden;">
                        <div style="height:100%;border-radius:9999px;width:{{ $kg->persen }}%;background:{{ $kg->persen >= 100 ? '#10b981' : '#14b8a6' }};"></div>
                    </div>
                    <p class="mt-2 text-xs text-slate-600">
                        <b class="text-slate-800 tabular-nums">{{ $kg->aktif }} dari {{ $kg->total }}</b> SLS dikerjakan
                    </p>

                    <div class="mt-3 flex flex-wrap gap-2 text-[11px] font-medium">
                        <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600">{{ $kg->belum }} belum mulai</span>
                        <span class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700">{{ $kg->berjalan }} berjalan</span>
                        <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700">{{ $kg->selesai }} selesai</span>
                        <span class="px-2.5 py-1 rounded-lg bg-slate-50 text-slate-600">📍 {{ number_format($kg->titik_saya) }} titik di-tag</span>
                        @if ($kg->sisa > 0)
                            <span class="px-2.5 py-1 rounded-lg bg-slate-50 text-slate-600">🛠️ {{ number_format($kg->sisa) }} perlu diperbaiki</span>
                        @endif
                    </div>
                </button>
            @endforeach
        </div>
    </div>

    {{-- ================= PANDUAN SINGKAT ================= --}}
    <details class="mt-4 bg-white rounded-2xl ring-1 ring-slate-200/70" @if ($slsAktif === 0) open @endif>
        <summary class="cursor-pointer select-none px-5 py-3.5 text-sm font-semibold text-slate-700">
            💡 Panduan singkat tagging
        </summary>
        <div class="px-5 pb-5 text-sm text-slate-600">
            <ol class="grid gap-3 sm:grid-cols-3">
                <li class="rounded-xl bg-slate-50 p-3.5">
                    <p class="font-semibold text-slate-800">1. Buka SLS</p>
                    <p class="text-xs mt-1 leading-relaxed">Tekan tombol <b>Mulai / Lanjutkan tagging</b> pada SLS yang akan dikerjakan.</p>
                </li>
                <li class="rounded-xl bg-slate-50 p-3.5">
                    <p class="font-semibold text-slate-800">2. Aktifkan Mode Tagging</p>
                    <p class="text-xs mt-1 leading-relaxed">Di peta, nyalakan <b>Mode Tagging</b>, lalu datangi bangunan yang akan ditandai.</p>
                </li>
                <li class="rounded-xl bg-slate-50 p-3.5">
                    <p class="font-semibold text-slate-800">3. Ambil lokasi GPS</p>
                    <p class="text-xs mt-1 leading-relaxed">Tekan <b>Ambil Lokasi Saya</b>, tunggu akurasi ±{{ $akurasiMaks }} m atau lebih baik, lalu <b>Gunakan lokasi ini</b>.</p>
                </li>
            </ol>
            <p class="mt-3 text-xs text-slate-500 leading-relaxed">
                Tips: pakai HP, nyalakan GPS dengan akurasi tinggi, dan berdiri di area terbuka. Di dalam ruangan atau di bawah atap, sinyal GPS sering kurang akurat.
            </p>
        </div>
    </details>

    {{-- ================= FILTER STATUS ================= --}}
    <div class="mt-7 flex flex-wrap items-center gap-2">
        <span class="text-xs font-semibold text-slate-500 mr-1">Tampilkan:</span>
        @foreach (['semua' => 'Semua', 'belum' => 'Belum mulai', 'berjalan' => 'Berjalan', 'selesai' => 'Selesai'] as $kunci => $label)
            <button type="button" @click="f = '{{ $kunci }}'"
                :class="f === '{{ $kunci }}' ? 'bg-teal-600 text-white shadow-sm' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50'"
                class="px-3.5 py-1.5 rounded-full text-xs font-semibold transition">
                {{ $label }} <span class="opacity-70">{{ $hitungStatus[$kunci] }}</span>
            </button>
        @endforeach
        <a href="{{ route('penugasan.saya') }}" class="ml-auto text-xs font-semibold text-teal-700 hover:underline">Lihat rekap penugasan →</a>
    </div>

    {{-- ================= DAFTAR SLS PER KEGIATAN ================= --}}
    @foreach ($grup as $namaKegiatan => $daftar)
        <section class="mt-5" x-show="k === 'semua' || k === '{{ $daftar->first()->kegiatan_id }}'">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-base font-bold text-slate-800">{{ $namaKegiatan }}</h2>
                <span class="text-xs text-slate-500">{{ $daftar->count() }} SLS</span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach ($daftar as $r)
                    @php [$warna, $labelStatus, $kelasBadge] = $aksen[$r->status]; @endphp
                    <div x-show="f === 'semua' || f === '{{ $r->status }}'"
                         class="bg-white rounded-2xl ring-1 ring-slate-200/70 shadow-sm overflow-hidden flex flex-col"
                         style="border-top: 4px solid {{ $r->dikembalikan > 0 ? '#ef4444' : $warna }};">

                        <div class="p-4 sm:p-5 flex-1">
                            {{-- Judul + status --}}
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="text-[15px] font-bold text-slate-900 break-words">{{ $r->nama_sls }}</p>
                                    <p class="text-xs text-slate-400 mt-0.5">{{ $r->id_sls }}</p>
                                </div>
                                <span class="shrink-0 inline-flex px-2.5 py-1 rounded-full text-[11px] font-semibold {{ $kelasBadge }}">{{ $labelStatus }}</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-2">📌 {{ $r->desa_kelurahan }}, {{ $r->kecamatan }}</p>

                            {{-- Petunjuk langkah berikutnya --}}
                            <p class="mt-3 text-[13px] leading-snug {{ $r->dikembalikan > 0 ? 'font-semibold' : 'text-slate-600' }}"
                               @if ($r->dikembalikan > 0) style="color:#b91c1c" @endif>
                                {{ $petunjuk($r) }}
                            </p>

                            {{-- Progres perbaikan --}}
                            @if ($r->persen_perbaikan !== null)
                                <div class="mt-4">
                                    <div class="flex items-baseline justify-between text-xs mb-1.5">
                                        <span class="text-slate-600">Perbaikan titik</span>
                                        <span class="font-semibold text-slate-700 tabular-nums">
                                            {{ $r->sudah_perbaikan }} dari {{ $r->sudah_perbaikan + $r->sisa_perbaikan }}
                                            <span class="text-slate-400">({{ $r->persen_perbaikan }}%)</span>
                                        </span>
                                    </div>
                                    <div style="height:10px;border-radius:9999px;background:#f1f5f9;overflow:hidden;">
                                        <div style="height:100%;border-radius:9999px;width:{{ $r->persen_perbaikan }}%;background:{{ $r->persen_perbaikan >= 100 ? '#10b981' : '#14b8a6' }};"></div>
                                    </div>
                                </div>
                            @endif

                            {{-- Angka ringkas --}}
                            <div class="mt-4 flex flex-wrap gap-2 text-[11px] font-medium">
                                <span class="px-2.5 py-1 rounded-lg bg-slate-50 text-slate-600">📍 {{ $r->titik_saya }} titik saya tag</span>
                                @if ($r->koreksi_menunggu > 0)
                                    <span class="px-2.5 py-1 rounded-lg bg-amber-50 text-amber-700">⏳ {{ $r->koreksi_menunggu }} menunggu verifikasi</span>
                                @endif
                                @if ($r->koreksi_terverifikasi > 0)
                                    <span class="px-2.5 py-1 rounded-lg bg-emerald-50 text-emerald-700">✅ {{ $r->koreksi_terverifikasi }} terverifikasi</span>
                                @endif
                                @if ($r->dikembalikan > 0)
                                    <span class="px-2.5 py-1 rounded-lg font-semibold" style="background:#fef2f2;color:#b91c1c;">↩ {{ $r->dikembalikan }} dikembalikan admin</span>
                                @endif
                                @if ($r->sisa_perbaikan > 0)
                                    <span class="px-2.5 py-1 rounded-lg bg-slate-50 text-slate-600">🛠️ {{ $r->sisa_perbaikan }} perlu diperbaiki</span>
                                @endif
                            </div>
                        </div>

                        {{-- Aksi --}}
                        <div class="px-4 sm:px-5 pb-4 sm:pb-5">
                            @if ($r->punya_polygon)
                                <a href="{{ route('peta.index', ['mode' => 'tagging', 'kegiatan' => $r->kegiatan_id, 'sls' => $r->sls_id]) }}"
                                   class="block w-full text-center py-3 rounded-xl text-sm font-bold transition
                                          {{ $r->status === 'selesai' ? 'bg-white text-teal-700 ring-1 ring-teal-200 hover:bg-teal-50' : 'bg-teal-600 text-white hover:bg-teal-700' }}">
                                    {{ ['belum' => 'Mulai tagging', 'berjalan' => 'Lanjutkan tagging', 'selesai' => 'Buka kembali'][$r->status] }}
                                </a>
                            @else
                                <p class="text-center text-xs text-amber-700 bg-amber-50 rounded-xl py-3">Batas SLS belum tersedia. Hubungi admin.</p>
                            @endif
                            <p class="mt-2 text-center text-[11px] text-slate-400">
                                {{ $r->terakhir_label ? 'Terakhir aktif ' . $r->terakhir_label . ' WIB' : 'Belum ada aktivitas' }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach

    <p class="mt-6 text-[11px] leading-relaxed text-slate-400 max-w-3xl">
        <b>Belum mulai</b>: belum ada titik yang Anda tag atau koreksi di SLS ini.
        <b>Berjalan</b>: sudah ada pekerjaan, tapi masih ada titik yang perlu diperbaiki.
        <b>Selesai</b>: sudah dikerjakan dan tidak ada titik yang perlu diperbaiki.
    </p>

    @endif
</div>
</div>
</x-app-layout>
