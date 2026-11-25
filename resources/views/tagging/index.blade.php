<x-app-layout>
    <x-slot name="header">
        <div class="min-w-0">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">Tagging</h2>
            <p class="hidden sm:block text-xs text-slate-500 mt-0.5">Kegiatan dan SLS yang ditugaskan kepada Anda</p>
        </div>
    </x-slot>

@php
    $namaDepan = \Illuminate\Support\Str::of($user->name)->trim()->before(' ');

    // [label, kelas badge, warna bar] — inline supaya tampil benar walau CSS Tailwind belum di-build ulang.
    $aksen = [
        'belum'    => ['Belum mulai', 'bg-slate-100 text-slate-600', '#94a3b8'],
        'berjalan' => ['Berjalan',    'bg-amber-50 text-amber-700',  '#f59e0b'],
        'selesai'  => ['Selesai',     'bg-emerald-50 text-emerald-700', '#10b981'],
    ];
    $input = 'w-full text-sm rounded-lg border-slate-200 bg-white focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500';

    // Status per kegiatan diturunkan dari status SLS-nya.
    $statusKegiatan = fn ($k) => $k->selesai === $k->total ? 'selesai' : ($k->aktif > 0 ? 'berjalan' : 'belum');
    $persenSelesai  = $totalSls ? (int) round($slsSelesai / $totalSls * 100) : 0;
    $buka = $rekap->count() === 1 ? $rekap->first()->kegiatan_id : ($lanjut->kegiatan_id ?? null);
@endphp

<div class="py-6 sm:py-8">
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5"
     x-data="{ q: '', st: 'semua', buka: {{ $buka ? "'{$buka}'" : 'null' }} }">

    @if ($totalSls === 0)
        <div class="p-10 bg-white rounded-2xl ring-1 ring-slate-200/70 text-center">
            <p class="text-4xl mb-3">🗂️</p>
            <p class="text-base font-semibold text-slate-800">Halo, {{ $namaDepan }}!</p>
            <p class="text-sm font-semibold text-slate-700 mt-3">Belum ada kegiatan yang ditugaskan kepada Anda</p>
            <p class="text-sm text-slate-500 mt-1">Hubungi admin untuk mendapatkan penugasan. Setelah ditugaskan, kegiatan dan SLS Anda tampil di sini.</p>
        </div>
    @else

    {{-- ================= RINGKASAN + LANJUTKAN ================= --}}
    <div class="rounded-2xl p-5 sm:p-6 text-white shadow-lg"
         style="background: linear-gradient(135deg, #0f766e 0%, #0d9488 55%, #14b8a6 100%);">
        <div class="flex flex-col sm:flex-row sm:items-center gap-4 sm:gap-8">
            <div class="flex-1 min-w-0">
                <p class="text-sm text-teal-100">Halo, {{ $namaDepan }} 👋</p>
                <p class="text-lg sm:text-xl font-bold mt-0.5">{{ $slsSelesai }} dari {{ $totalSls }} SLS selesai</p>
                <div class="mt-3" style="height:10px;border-radius:9999px;background:rgba(255,255,255,.25);overflow:hidden;">
                    <div style="height:100%;border-radius:9999px;width:{{ $persenSelesai }}%;background:#fff;"></div>
                </div>
                <div class="mt-3 flex flex-wrap gap-2 text-xs font-semibold">
                    <span class="px-3 py-1.5 rounded-full" style="background:rgba(255,255,255,.18)">{{ $rekap->count() }} kegiatan</span>
                    <span class="px-3 py-1.5 rounded-full" style="background:rgba(255,255,255,.18)">📍 {{ number_format($totalTitik) }} titik di-tag</span>
                    @if ($menunggu > 0)
                        <span class="px-3 py-1.5 rounded-full" style="background:rgba(255,255,255,.18)">⏳ {{ $menunggu }} menunggu verifikasi</span>
                    @endif
                    @if ($sisaPerbaikan > 0)
                        <span class="px-3 py-1.5 rounded-full" style="background:rgba(255,255,255,.18)">🛠️ {{ $sisaPerbaikan }} perlu diperbaiki</span>
                    @endif
                </div>
            </div>
            @if ($lanjut)
                <a href="{{ route('peta.index', ['mode' => 'tagging', 'kegiatan' => $lanjut->kegiatan_id, 'sls' => $lanjut->sls_id]) }}"
                   class="shrink-0 inline-flex flex-wrap items-center justify-center gap-x-2 px-5 py-3 rounded-xl bg-white text-teal-800 text-sm font-bold shadow hover:bg-teal-50 transition">
                    {{ $lanjut->status === 'berjalan' ? 'Lanjutkan tagging' : 'Mulai tagging' }}
                    <span class="font-semibold text-teal-600">· {{ $lanjut->nama_sls }}</span>
                    <span aria-hidden="true">→</span>
                </a>
            @endif
        </div>
    </div>

    @if ($dikembalikan > 0)
        <div class="p-4 rounded-2xl text-sm leading-relaxed" style="background:#fef2f2;border:1px solid #fecaca;color:#b91c1c;">
            <b>{{ $dikembalikan }} koreksi dikembalikan admin.</b>
            Buka SLS bertanda merah di bawah, lalu ambil ulang lokasi titiknya.
        </div>
    @endif

    {{-- ================= FILTER ================= --}}
    <div class="bg-white rounded-2xl ring-1 ring-slate-200/70 shadow-sm p-4 sm:p-5">
        <div class="grid grid-cols-1 sm:grid-cols-[1fr_220px] gap-3">
            <input type="search" x-model="q" placeholder="Cari kegiatan, kode SLS, nama SLS, atau wilayah…" class="{{ $input }}">
            <select x-model="st" class="{{ $input }}">
                <option value="semua">Semua status SLS</option>
                <option value="belum">Belum mulai</option>
                <option value="berjalan">Berjalan</option>
                <option value="selesai">Selesai</option>
            </select>
        </div>
    </div>

    {{-- ================= KEGIATAN → SLS ================= --}}
    @foreach ($rekap as $kg)
        @php
            $sk = $statusKegiatan($kg);
            [$lblK, $badgeK, $warnaK] = $aksen[$sk];
            $persenK = $kg->total ? (int) round($kg->selesai / $kg->total * 100) : 0;
            $kid = (string) $kg->kegiatan_id;
        @endphp
        <section class="bg-white rounded-2xl ring-1 ring-slate-200/70 shadow-sm overflow-hidden">

            {{-- Kepala kegiatan: nama, target, progres. Klik untuk buka/tutup daftar SLS. --}}
            <button type="button" @click="buka = (buka === '{{ $kid }}' ? null : '{{ $kid }}')"
                    class="w-full text-left p-4 sm:p-5 hover:bg-slate-50/60 transition">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-[15px] sm:text-base font-bold text-slate-900 break-words">{{ $kg->kegiatan?->nama_kegiatan ?? 'Kegiatan' }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">
                            {{ $kg->kegiatan?->kode_kegiatan }}@if ($kg->kegiatan?->periode) · {{ $kg->kegiatan->periode }}@endif
                        </p>
                    </div>
                    <div class="shrink-0 flex items-center gap-3">
                        <span class="hidden sm:inline-flex px-2.5 py-1 rounded-full text-[11px] font-semibold {{ $badgeK }}">{{ $lblK }}</span>
                        <svg class="w-5 h-5 text-slate-400 transition-transform" :class="buka === '{{ $kid }}' ? 'rotate-180' : ''"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>

                <div class="mt-3 flex items-center gap-3">
                    <div class="flex-1" style="height:10px;border-radius:9999px;background:#f1f5f9;overflow:hidden;">
                        <div style="height:100%;border-radius:9999px;width:{{ $persenK }}%;background:{{ $warnaK }};"></div>
                    </div>
                    <span class="text-sm font-extrabold tabular-nums" style="color: {{ $persenK >= 100 ? '#059669' : '#0f766e' }}">{{ $persenK }}%</span>
                </div>

                <div class="mt-2.5 flex flex-wrap gap-1.5 text-[11px] font-medium">
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 text-slate-700">Target <b class="tabular-nums">{{ $kg->total }}</b> SLS</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-600">{{ $kg->belum }} belum mulai</span>
                    <span class="px-2 py-0.5 rounded-md bg-amber-50 text-amber-700">{{ $kg->berjalan }} berjalan</span>
                    <span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700">{{ $kg->selesai }} selesai</span>
                    <span class="px-2 py-0.5 rounded-md bg-slate-50 text-slate-600">📍 {{ number_format($kg->titik_saya) }} titik</span>
                    @if ($kg->sisa > 0)
                        <span class="px-2 py-0.5 rounded-md bg-slate-50 text-slate-600">🛠️ {{ number_format($kg->sisa) }} perlu diperbaiki</span>
                    @endif
                </div>
            </button>

            {{-- Daftar SLS kegiatan ini --}}
            <div x-show="buka === '{{ $kid }}'" x-cloak class="border-t border-slate-100">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-4 sm:px-5 py-2.5 text-left font-semibold">SLS</th>
                                <th class="px-3 py-2.5 text-left font-semibold hidden md:table-cell">Wilayah</th>
                                <th class="px-3 py-2.5 text-left font-semibold">Status</th>
                                <th class="px-3 py-2.5 text-right font-semibold hidden sm:table-cell">Titik saya</th>
                                <th class="px-3 py-2.5 text-left font-semibold hidden sm:table-cell" style="min-width:140px">Perbaikan</th>
                                <th class="px-3 py-2.5 text-left font-semibold hidden xl:table-cell">Terakhir aktif</th>
                                <th class="px-4 sm:px-5 py-2.5"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($kg->sls as $r)
                                @php [$lbl, $badge, $warna] = $aksen[$r->status]; @endphp
                                <tr x-show="(st === 'semua' || st === '{{ $r->status }}')
                                            && (q === '' || {{ \Illuminate\Support\Js::from(mb_strtolower("{$r->id_sls} {$r->nama_sls} {$r->desa_kelurahan} {$r->kecamatan} {$r->nama_kegiatan}")) }}.includes(q.toLowerCase()))"
                                    class="hover:bg-slate-50/60"
                                    @if ($r->dikembalikan > 0) style="box-shadow: inset 3px 0 0 #ef4444;" @endif>
                                    <td class="px-4 sm:px-5 py-3">
                                        <p class="font-semibold text-slate-800">{{ $r->nama_sls }}</p>
                                        <p class="text-xs text-slate-400">{{ $r->id_sls }}</p>
                                        <p class="text-xs text-slate-400 md:hidden">{{ $r->desa_kelurahan }}, {{ $r->kecamatan }}</p>
                                    </td>
                                    <td class="px-3 py-3 text-xs text-slate-500 hidden md:table-cell">{{ $r->desa_kelurahan }}<br>{{ $r->kecamatan }}</td>
                                    <td class="px-3 py-3">
                                        <span class="inline-flex px-2.5 py-1 rounded-full text-[11px] font-semibold whitespace-nowrap {{ $badge }}">{{ $lbl }}</span>
                                        @if ($r->dikembalikan > 0)
                                            <p class="mt-1 text-[11px] font-semibold" style="color:#b91c1c">↩ {{ $r->dikembalikan }} dikembalikan</p>
                                        @endif
                                        @if ($r->koreksi_menunggu > 0)
                                            <p class="mt-1 text-[11px] text-amber-700">⏳ {{ $r->koreksi_menunggu }} menunggu verifikasi</p>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-right tabular-nums text-slate-700 hidden sm:table-cell">{{ $r->titik_saya }}</td>
                                    <td class="px-3 py-3 hidden sm:table-cell">
                                        @if ($r->persen_perbaikan !== null)
                                            <div class="flex items-center gap-2">
                                                <div class="flex-1" style="height:8px;border-radius:9999px;background:#f1f5f9;overflow:hidden;">
                                                    <div style="height:100%;border-radius:9999px;width:{{ $r->persen_perbaikan }}%;background:{{ $r->persen_perbaikan >= 100 ? '#10b981' : '#14b8a6' }};"></div>
                                                </div>
                                                <span class="text-[11px] tabular-nums text-slate-600 whitespace-nowrap">{{ $r->sudah_perbaikan }}/{{ $r->sudah_perbaikan + $r->sisa_perbaikan }}</span>
                                            </div>
                                        @else
                                            <span class="text-xs text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 text-xs text-slate-500 whitespace-nowrap tabular-nums hidden xl:table-cell">{{ $r->terakhir_label ?? '—' }}</td>
                                    <td class="px-4 sm:px-5 py-3 text-right whitespace-nowrap">
                                        @if ($r->punya_polygon)
                                            <a href="{{ route('peta.index', ['mode' => 'tagging', 'kegiatan' => $r->kegiatan_id, 'sls' => $r->sls_id]) }}"
                                               class="inline-block px-3.5 py-2 rounded-lg text-xs font-bold transition
                                                      {{ $r->status === 'selesai' ? 'bg-white text-teal-700 ring-1 ring-teal-200 hover:bg-teal-50' : 'bg-teal-600 text-white hover:bg-teal-700' }}">
                                                {{ ['belum' => 'Mulai tagging', 'berjalan' => 'Lanjutkan', 'selesai' => 'Buka'][$r->status] }}
                                            </a>
                                        @else
                                            <span class="text-[11px] text-amber-700">Belum ada batas SLS</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endforeach

    <p class="text-[11px] leading-relaxed text-slate-400 max-w-3xl">
        <b>Belum mulai</b>: belum ada titik yang Anda tag atau koreksi di SLS ini.
        <b>Berjalan</b>: sudah ada pekerjaan, tapi masih ada titik yang perlu diperbaiki.
        <b>Selesai</b>: sudah dikerjakan dan tidak ada titik yang perlu diperbaiki.
        Tips: pakai HP, nyalakan GPS akurasi tinggi, dan berdiri di area terbuka (akurasi maksimal ±{{ $akurasiMaks }} m).
    </p>

    @endif
</div>
</div>
</x-app-layout>
