<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-3">
            <div class="min-w-0">
                <h2 class="font-semibold text-xl text-slate-800 leading-tight">Penugasan Saya</h2>
                <p class="hidden sm:block text-xs text-slate-500 mt-0.5">Rekap SLS yang ditugaskan admin kepada Anda, per kegiatan</p>
            </div>
            <a href="{{ route('dashboard') }}"
               class="shrink-0 px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition">
                Lihat progres
            </a>
        </div>
    </x-slot>

@php
    $aksen = [
        'belum'    => ['Belum mulai', 'bg-slate-100 text-slate-600'],
        'berjalan' => ['Berjalan',    'bg-amber-50 text-amber-700'],
        'selesai'  => ['Selesai',     'bg-emerald-50 text-emerald-700'],
    ];
    $input = 'w-full text-sm rounded-lg border-slate-200 bg-white focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500';
@endphp

<div class="py-6 sm:py-8">
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

    @if ($rekap->isEmpty())
        <div class="p-10 bg-white rounded-2xl ring-1 ring-slate-200/70 text-center">
            <p class="text-4xl mb-3">🗂️</p>
            <p class="text-sm font-semibold text-slate-700">Belum ada SLS yang ditugaskan kepada Anda</p>
            <p class="text-sm text-slate-500 mt-1">Hubungi admin untuk mendapatkan penugasan.</p>
        </div>
    @else

    {{-- ================= REKAP TOTAL ================= --}}
    <div class="grid grid-cols-3 gap-3">
        <div class="bg-white rounded-2xl ring-1 ring-slate-200/70 p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Kegiatan</p>
            <p class="text-2xl font-bold text-slate-900 mt-1 tabular-nums">{{ $rekap->count() }}</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-slate-200/70 p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">SLS ditugaskan</p>
            <p class="text-2xl font-bold text-slate-900 mt-1 tabular-nums">{{ $totalSls }}</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-slate-200/70 p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">SLS selesai</p>
            <p class="text-2xl font-bold text-slate-900 mt-1 tabular-nums">{{ $slsSelesai }} <span class="text-base font-semibold text-slate-400">/ {{ $totalSls }}</span></p>
        </div>
    </div>

    {{-- ================= REKAP PER KEGIATAN (ketuk untuk memfilter) ================= --}}
    <div>
        <div class="flex items-baseline justify-between mb-2.5">
            <h3 class="text-sm font-bold text-slate-800">Rekap per kegiatan</h3>
            @if ($rekap->count() > 1)
                <span class="text-[11px] text-slate-400">Ketuk kartu untuk memfilter daftar</span>
            @endif
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 {{ $rekap->count() > 2 ? 'xl:grid-cols-3' : '' }} gap-3">
            @foreach ($rekap as $k)
                @php $terpilih = $kegiatanDipilih === $k->kegiatan_id; @endphp
                <a href="{{ $terpilih
                        ? route('penugasan.saya', array_filter(['status' => $statusDipilih, 'q' => $q]))
                        : route('penugasan.saya', array_filter(['kegiatan' => $k->kegiatan_id, 'status' => $statusDipilih, 'q' => $q])) }}"
                   class="block bg-white rounded-2xl p-4 shadow-sm hover:shadow transition {{ $terpilih ? 'ring-2 ring-teal-500' : 'ring-1 ring-slate-200/70' }}">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <p class="text-[15px] font-bold text-slate-900 break-words">{{ $k->kegiatan?->nama_kegiatan ?? 'Kegiatan' }}</p>
                            <p class="text-xs text-slate-400 mt-0.5">
                                {{ $k->kegiatan?->kode_kegiatan }}@if ($k->kegiatan?->periode) · {{ $k->kegiatan->periode }}@endif
                            </p>
                        </div>
                        <span class="shrink-0 text-lg font-extrabold tabular-nums" style="color: {{ $k->persen >= 100 ? '#059669' : '#0f766e' }}">{{ $k->persen }}%</span>
                    </div>
                    <div class="mt-3" style="height:8px;border-radius:9999px;background:#f1f5f9;overflow:hidden;">
                        <div style="height:100%;border-radius:9999px;width:{{ $k->persen }}%;background:{{ $k->persen >= 100 ? '#10b981' : '#14b8a6' }};"></div>
                    </div>
                    <p class="mt-2 text-xs text-slate-600"><b class="text-slate-800 tabular-nums">{{ $k->aktif }} dari {{ $k->total }}</b> SLS dikerjakan</p>
                    <div class="mt-2.5 flex flex-wrap gap-1.5 text-[11px] font-medium">
                        <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600">{{ $k->belum }} belum mulai</span>
                        <span class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-700">{{ $k->berjalan }} berjalan</span>
                        <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700">{{ $k->selesai }} selesai</span>
                        @if ($k->sisa > 0)
                            <span class="px-2 py-0.5 rounded-md bg-slate-50 text-slate-600">🛠️ {{ $k->sisa }} perlu diperbaiki</span>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    {{-- ================= FILTER ================= --}}
    <form method="GET" action="{{ route('penugasan.saya') }}"
          class="bg-white rounded-2xl ring-1 ring-slate-200/70 shadow-sm p-4 sm:p-5">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-[1.2fr_1fr_1.6fr_auto] gap-3 items-end">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Kegiatan</label>
                <select name="kegiatan" onchange="this.form.submit()" class="{{ $input }}">
                    <option value="">Semua kegiatan</option>
                    @foreach ($rekap as $k)
                        <option value="{{ $k->kegiatan_id }}" @selected($kegiatanDipilih === $k->kegiatan_id)>{{ $k->kegiatan?->nama_kegiatan }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Status</label>
                <select name="status" onchange="this.form.submit()" class="{{ $input }}">
                    <option value="">Semua status</option>
                    @foreach (['belum' => 'Belum mulai', 'berjalan' => 'Berjalan', 'selesai' => 'Selesai'] as $kunci => $label)
                        <option value="{{ $kunci }}" @selected($statusDipilih === $kunci)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1.5">Cari SLS</label>
                <input type="search" name="q" value="{{ $q }}" placeholder="Kode, nama SLS, atau wilayah…" class="{{ $input }}">
            </div>
            <div class="flex gap-2">
                <button class="px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold transition">Terapkan</button>
                @if ($adaFilter)
                    <a href="{{ route('penugasan.saya') }}" class="px-4 py-2 rounded-lg bg-white ring-1 ring-slate-200 hover:bg-slate-50 text-slate-600 text-sm font-medium transition">Reset</a>
                @endif
            </div>
        </div>
    </form>

    {{-- ================= DAFTAR SLS ================= --}}
    <section class="bg-white rounded-2xl ring-1 ring-slate-200/70 shadow-sm overflow-hidden">
        <div class="px-4 sm:px-5 py-3.5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
            <h3 class="text-sm font-bold text-slate-800">Daftar SLS</h3>
            <p class="text-xs text-slate-500">
                @if ($daftar->total() > 0)
                    Menampilkan <b class="text-slate-700">{{ $daftar->firstItem() }}–{{ $daftar->lastItem() }}</b> dari <b class="text-slate-700">{{ $daftar->total() }}</b> SLS
                @else
                    0 SLS
                @endif
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-4 sm:px-5 py-2.5 text-left font-semibold">SLS</th>
                        <th class="px-3 py-2.5 text-left font-semibold hidden lg:table-cell">Kegiatan</th>
                        <th class="px-3 py-2.5 text-left font-semibold hidden md:table-cell">Wilayah</th>
                        <th class="px-3 py-2.5 text-left font-semibold">Status</th>
                        <th class="px-3 py-2.5 text-right font-semibold hidden sm:table-cell">Titik saya</th>
                        <th class="px-3 py-2.5 text-right font-semibold hidden sm:table-cell">Perlu diperbaiki</th>
                        <th class="px-3 py-2.5 text-left font-semibold hidden xl:table-cell">Terakhir aktif</th>
                        <th class="px-4 sm:px-5 py-2.5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($daftar as $r)
                        @php [$labelStatus, $kelasBadge] = $aksen[$r->status]; @endphp
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-4 sm:px-5 py-3">
                                <p class="font-semibold text-slate-800">{{ $r->nama_sls }}</p>
                                <p class="text-xs text-slate-400">{{ $r->id_sls }}</p>
                                <p class="text-xs text-slate-400 lg:hidden">{{ $r->nama_kegiatan }}</p>
                                <p class="text-xs text-slate-400 md:hidden">{{ $r->desa_kelurahan }}, {{ $r->kecamatan }}</p>
                            </td>
                            <td class="px-3 py-3 text-xs text-slate-600 hidden lg:table-cell">{{ $r->nama_kegiatan }}</td>
                            <td class="px-3 py-3 text-xs text-slate-500 hidden md:table-cell">{{ $r->desa_kelurahan }}<br>{{ $r->kecamatan }}</td>
                            <td class="px-3 py-3">
                                <span class="inline-flex px-2.5 py-1 rounded-full text-[11px] font-semibold whitespace-nowrap {{ $kelasBadge }}">{{ $labelStatus }}</span>
                                @if ($r->dikembalikan > 0)
                                    <p class="mt-1 text-[11px] font-semibold" style="color:#b91c1c">↩ {{ $r->dikembalikan }} dikembalikan</p>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-right tabular-nums text-slate-700 hidden sm:table-cell">{{ $r->titik_saya }}</td>
                            <td class="px-3 py-3 text-right tabular-nums hidden sm:table-cell {{ $r->sisa_perbaikan ? 'font-semibold text-amber-700' : 'text-slate-400' }}">{{ $r->sisa_perbaikan }}</td>
                            <td class="px-3 py-3 text-xs text-slate-500 whitespace-nowrap tabular-nums hidden xl:table-cell">{{ $r->terakhir_label ?? '—' }}</td>
                            <td class="px-4 sm:px-5 py-3 text-right whitespace-nowrap">
                                @if ($r->punya_polygon)
                                    <a href="{{ route('peta.index', ['mode' => 'tagging', 'kegiatan' => $r->kegiatan_id, 'sls' => $r->sls_id]) }}"
                                       class="inline-block px-3.5 py-2 rounded-lg text-xs font-bold transition
                                              {{ $r->status === 'selesai' ? 'bg-white text-teal-700 ring-1 ring-teal-200 hover:bg-teal-50' : 'bg-teal-600 text-white hover:bg-teal-700' }}">
                                        {{ ['belum' => 'Mulai', 'berjalan' => 'Lanjutkan', 'selesai' => 'Buka'][$r->status] }}
                                    </a>
                                @else
                                    <span class="text-[11px] text-amber-700">Belum ada batas SLS</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-12 text-center">
                                <p class="text-sm font-semibold text-slate-700">Tidak ada SLS yang cocok</p>
                                <p class="text-xs text-slate-500 mt-1">Coba ubah kata kunci atau filter.</p>
                                <a href="{{ route('penugasan.saya') }}" class="inline-block mt-3 text-xs font-semibold text-teal-700 hover:underline">Reset filter</a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($daftar->hasPages())
            <div class="px-4 sm:px-5 py-3.5 border-t border-slate-100">{{ $daftar->links() }}</div>
        @endif
    </section>

    <p class="text-[11px] leading-relaxed text-slate-400 max-w-3xl">
        <b>Belum mulai</b>: belum ada titik yang Anda tag atau koreksi di SLS ini.
        <b>Berjalan</b>: sudah ada pekerjaan, tapi masih ada titik yang perlu diperbaiki.
        <b>Selesai</b>: sudah dikerjakan dan tidak ada titik yang perlu diperbaiki.
    </p>

    @endif
</div>
</div>
</x-app-layout>
