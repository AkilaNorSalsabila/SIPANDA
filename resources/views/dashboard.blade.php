<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">Dashboard</h2>
    </x-slot>

    @php
        /*
         * PALET WARNA STATUS TITIK (satu tempat, dipakai donat + bar tabel)
         *   normal      -> teal   (senada dengan warna utama aplikasi)
         *   bermasalah  -> merah
         *   belum dicek -> abu terang (tidak lagi mendominasi donat)
         * Warna lama dari controller dipetakan ke warna baru; warna lain dibiarkan apa adanya.
         */
        $wNormal     = '#14b8a6';
        $wBermasalah = '#ef4444';
        $wBelum      = '#cbd5e1';

        $petaWarna = [
            '#3b82f6' => $wNormal,
            '#ef4444' => $wBermasalah,
            '#64748b' => $wBelum,
        ];

        $segBaru = collect($segmen)->map(function ($s) use ($petaWarna) {
            return array_merge($s, [
                'warna' => $petaWarna[strtolower($s['warna'])] ?? $s['warna'],
            ]);
        });

        // Bangun ulang donat dari segmen berwarna baru
        $totalSeg = $segBaru->sum('nilai');
        $stops    = [];
        $acc      = 0;
        foreach ($segBaru as $s) {
            if ($totalSeg > 0 && $s['nilai'] > 0) {
                $awal  = round($acc / $totalSeg * 100, 2);
                $acc  += $s['nilai'];
                $akhir = round($acc / $totalSeg * 100, 2);
                $stops[] = "{$s['warna']} {$awal}% {$akhir}%";
            }
        }
        $donutBaru = count($stops) ? 'conic-gradient(' . implode(', ', $stops) . ')' : '#e2e8f0';

        // Kartu ringkasan: ikon + nilai
        $kartu = [
            ['label' => 'Kegiatan',         'nilai' => $totalKegiatan, 'ikon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a3 3 0 006 0M9 5a3 3 0 016 0M9 13l2 2 4-4'],
            ['label' => 'SLS Terdata',      'nilai' => $totalSls,      'ikon' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z'],
            ['label' => 'Total Bangunan',   'nilai' => $totalBangunan, 'ikon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
            ['label' => 'Jumlah Usaha',     'nilai' => $totalUsaha,    'ikon' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
            ['label' => 'Keluarga (KK)',    'nilai' => $totalKk,       'ikon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
            ['label' => 'Titik Bermasalah', 'nilai' => $bermasalah,    'ikon' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z', 'alert' => true],
        ];
    @endphp

    <div class="py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- ================= PINTASAN ================= --}}
            <div class="flex flex-wrap items-center gap-2.5">
                <a href="{{ route('peta.index') }}"
                    class="px-4 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold shadow-sm transition">
                    Buka Peta
                </a>

                @if ($isAdmin)
                    <a href="{{ route('import.index') }}"
                        class="px-4 py-2 rounded-lg bg-white border border-slate-200 text-slate-700 text-sm font-semibold shadow-sm hover:border-teal-300 hover:text-teal-700 transition">
                        Import Data
                    </a>
                @endif

                @if ($isAdmin && Route::has('kegiatan.index'))
                    <a href="{{ route('kegiatan.index') }}"
                        class="px-4 py-2 rounded-lg bg-white border border-slate-200 text-slate-700 text-sm font-semibold shadow-sm hover:border-teal-300 hover:text-teal-700 transition">
                        Kelola Kegiatan
                    </a>
                @endif

                @if ($importTerakhir)
                    <p class="ms-auto text-xs text-slate-400">
                        Import terakhir:
                        <span class="text-slate-500 font-medium">
                            {{ \Illuminate\Support\Carbon::parse($importTerakhir)->locale('id')->diffForHumans() }}
                        </span>
                    </p>
                @endif
            </div>


            {{-- ================= KARTU RINGKASAN ================= --}}
            <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                @foreach ($kartu as $k)
                    @php
                        $isAlert = ($k['alert'] ?? false) && ($k['nilai'] ?? 0) > 0;
                    @endphp

                    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-4 flex flex-col gap-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500 whitespace-nowrap">
                            {{ $k['label'] }}
                        </p>

                        <div class="flex items-center justify-between gap-2">
                            <p class="text-2xl font-bold leading-none {{ $isAlert ? 'text-red-600' : 'text-slate-800' }}">
                                {{ is_null($k['nilai']) ? '-' : number_format($k['nilai']) }}
                            </p>
                            <span class="w-8 h-8 shrink-0 rounded-lg flex items-center justify-center
                                         {{ $isAlert ? 'bg-red-50 text-red-500' : 'bg-teal-50 text-teal-600' }}">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="{{ $k['ikon'] }}" />
                                </svg>
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>


            <div class="grid grid-cols-1 xl:grid-cols-[20rem_minmax(0,1fr)] gap-6">

                {{-- ================= DONAT STATUS TITIK ================= --}}
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6">
                    <h3 class="text-sm font-semibold text-slate-800 mb-5">Kualitas Posisi Titik</h3>

                    <div class="flex flex-col items-center gap-6">
                        <div class="relative w-44 h-44 rounded-full" style="background: {{ $donutBaru }}">
                            <div class="absolute inset-5 rounded-full bg-white flex flex-col items-center justify-center">
                                <span class="text-2xl font-bold text-slate-800">{{ number_format($totalBangunan) }}</span>
                                <span class="text-[11px] text-slate-400">total titik</span>
                            </div>
                        </div>

                        <div class="w-full divide-y divide-slate-100 text-xs text-slate-600">
                            @foreach ($segBaru as $s)
                                <div class="flex items-center gap-2.5 py-2">
                                    <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background: {{ $s['warna'] }}"></span>
                                    <span class="flex-1">{{ $s['label'] }}</span>
                                    <span class="font-semibold text-slate-800">{{ number_format($s['nilai']) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>


                {{-- ================= TABEL PER KEGIATAN ================= --}}
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100">
                        <h3 class="text-sm font-semibold text-slate-800">Ringkasan per Kegiatan</h3>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-slate-50 text-slate-500 text-[11px] uppercase tracking-wide whitespace-nowrap">
                                <tr>
                                    <th class="pl-6 pr-4 py-3 text-left font-semibold min-w-[12rem]">Kegiatan</th>
                                    <th class="px-3 py-3 text-center font-semibold">SLS</th>
                                    <th class="px-3 py-3 text-center font-semibold">Bangunan</th>
                                    <th class="px-3 py-3 text-left font-semibold min-w-[8rem]">Status Titik</th>
                                    <th class="px-3 py-3 text-center font-semibold">Bermasalah</th>
                                    <th class="pl-3 pr-6 py-3 text-center font-semibold">Aksi</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">
                                @forelse ($kegiatan as $k)
                                    @php
                                        $tot = max($k->bangunan_count, 1);
                                    @endphp

                                    <tr class="hover:bg-slate-50/70 transition">
                                        <td class="pl-6 pr-4 py-4">
                                            <p class="font-medium text-slate-800">{{ $k->nama_kegiatan }}</p>
                                            <p class="mt-0.5 text-[11px] text-slate-400 leading-snug">
                                                {{ $k->kode_kegiatan }}@if ($k->periode) &middot; {{ $k->periode }}@endif
                                            </p>
                                        </td>

                                        <td class="px-3 py-4 text-center text-slate-600">
                                            {{ number_format($slsPerKegiatan[$k->id] ?? 0) }}
                                        </td>

                                        <td class="px-3 py-4 text-center text-slate-600">
                                            {{ number_format($k->bangunan_count) }}
                                        </td>

                                        {{-- Bar status: teal normal, merah bermasalah, abu terang belum dicek --}}
                                        <td class="px-3 py-4">
                                            @if ($k->bangunan_count > 0)
                                                <div class="flex h-2 w-full overflow-hidden rounded-full bg-slate-100"
                                                    title="Normal {{ $k->normal_count }} • Bermasalah {{ $k->bermasalah_count }} • Belum dicek {{ $k->belum_dicek_count }}">
                                                    <div style="width: {{ $k->normal_count / $tot * 100 }}%; background: {{ $wNormal }}"></div>
                                                    <div style="width: {{ $k->bermasalah_count / $tot * 100 }}%; background: {{ $wBermasalah }}"></div>
                                                    <div style="width: {{ $k->belum_dicek_count / $tot * 100 }}%; background: {{ $wBelum }}"></div>
                                                </div>
                                            @else
                                                <span class="text-xs text-slate-400 whitespace-nowrap">Belum ada data</span>
                                            @endif
                                        </td>

                                        <td class="px-3 py-4 text-center">
                                            @if ($k->bermasalah_count > 0)
                                                <span class="inline-block px-2.5 py-0.5 rounded-full bg-red-50 text-red-600 text-xs font-semibold">
                                                    {{ number_format($k->bermasalah_count) }}
                                                </span>
                                            @else
                                                <span class="text-slate-300">0</span>
                                            @endif
                                        </td>

                                        <td class="pl-3 pr-6 py-4 text-center whitespace-nowrap">
                                            <a href="{{ route('peta.index', ['kegiatan' => $k->id]) }}"
                                                class="inline-block px-3 py-1.5 rounded-lg bg-teal-50 text-xs font-semibold text-teal-700 hover:bg-teal-100 transition">
                                                Lihat di peta
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                                            Belum ada kegiatan.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>


            {{-- ================= KHUSUS ADMIN: AKUN MENUNGGU ================= --}}
            @if ($isAdmin && $pending > 0)
                <a href="{{ route('admin.users.index') }}"
                    class="flex items-center justify-between bg-amber-50 border border-amber-200 rounded-2xl px-6 py-4 hover:bg-amber-100/70 transition">
                    <div>
                        <p class="text-sm font-semibold text-amber-800">
                            {{ $pending }} akun menunggu persetujuan
                        </p>
                        <p class="text-xs text-amber-700/80">Klik untuk meninjau dan menyetujui pendaftaran.</p>
                    </div>
                    <span class="text-amber-700 text-lg">&rarr;</span>
                </a>
            @endif

        </div>
    </div>
</x-app-layout>