<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-3">
            <div class="min-w-0">
                <h2 class="font-semibold text-xl text-slate-800 leading-tight">Data Lokasi</h2>
                <p class="hidden sm:block text-xs text-slate-500 mt-0.5">{{ auth()->user()->isAdmin() ? 'Pantau titik bangunan per SLS dan status perbaikannya' : 'Titik bangunan dan status perbaikan di SLS yang ditugaskan kepada Anda' }}</p>
            </div>
            <a href="{{ route('peta.index') }}"
                class="inline-flex items-center gap-1.5 shrink-0 px-3 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 text-sm font-medium transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.5 3.5-4.9-2.45a1 1 0 0 0-.9 0L4.5 20V6.5l5.2-2.6a1 1 0 0 1 .9 0l4.8 2.4a1 1 0 0 0 .9 0L20 4v13.5l-4.5 2.25Z"/></svg>
                <span class="hidden sm:inline">Buka Peta</span>
            </a>
        </div>
    </x-slot>

    @php
        $adaFilter = request()->filled('kegiatan_id') || request()->filled('kecamatan_id') || request()->filled('kelurahan_id')
            || request()->filled('status') || request()->filled('q');
        $kegiatanAktif  = $kegiatanList->firstWhere('id', (int) request('kegiatan_id'));
        $kecamatanAktif = $kecamatanList->firstWhere('id', (int) request('kecamatan_id'));
        $kelurahanAktif = $kelurahanList->firstWhere('id', (int) request('kelurahan_id'));
        $input = 'w-full text-sm rounded-lg border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition';
        $detailParams = fn ($s) => array_filter(['sls' => $s->id, 'kegiatan_id' => request('kegiatan_id')]);
    @endphp

    <div class="py-6 sm:py-8" x-data="{
            kecamatan: '{{ request('kecamatan_id') }}',
            kelurahan: '{{ request('kelurahan_id') }}',
            kelurahanList: @js($kelurahanList),
            loading: false,
            async muatKelurahan() {
                this.kelurahan = '';
                this.kelurahanList = [];
                if (! this.kecamatan) return;
                this.loading = true;
                try {
                    const res = await fetch('{{ url('wilayah/kecamatan') }}/' + this.kecamatan + '/kelurahan', { headers: { Accept: 'application/json' } });
                    const data = await res.json();
                    this.kelurahanList = data.map(k => ({ id: k.id, nama_kelurahan: k.nama_kelurahan }));
                } finally { this.loading = false; }
            },
        }">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            @if (session('status'))
                <div class="flex items-center gap-2 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-xl">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    {{ session('status') }}
                </div>
            @endif

            {{-- ================= RINGKASAN ================= --}}
            <div class="grid grid-cols-2 {{ auth()->user()->isAdmin() ? 'lg:grid-cols-4' : 'lg:grid-cols-3' }} gap-3 sm:gap-4">
                {{-- SLS --}}
                <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 flex items-start gap-3 shadow-sm">
                    <span class="hidden sm:flex w-10 h-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.5 3.5-4.9-2.45a1 1 0 0 0-.9 0L4.5 20V6.5l5.2-2.6a1 1 0 0 1 .9 0l4.8 2.4a1 1 0 0 0 .9 0L20 4v13.5l-4.5 2.25Z"/></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-slate-500">Total SLS</p>
                        <p class="text-2xl font-bold text-slate-800 tabular-nums">{{ number_format($ringkasan['sls']) }}</p>
                    </div>
                </div>
                {{-- Perlu perbaikan --}}
                <div class="bg-white rounded-2xl border p-4 sm:p-5 flex items-start gap-3 shadow-sm {{ $ringkasan['perbaikan'] > 0 ? 'border-amber-200' : 'border-slate-200' }}">
                    <span class="hidden sm:flex w-10 h-10 shrink-0 items-center justify-center rounded-xl {{ $ringkasan['perbaikan'] > 0 ? 'bg-amber-50 text-amber-600' : 'bg-emerald-50 text-emerald-600' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.3 3.4c-.87 1.5.2 3.35 1.95 3.35h14.7c1.75 0 2.82-1.85 1.95-3.35L13.95 4.6a2.25 2.25 0 0 0-3.9 0L2.7 16.15ZM12 15.75h.01"/></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-slate-500">Perlu perbaikan</p>
                        <p class="text-2xl font-bold tabular-nums {{ $ringkasan['perbaikan'] > 0 ? 'text-amber-600' : 'text-slate-800' }}">{{ number_format($ringkasan['perbaikan']) }}</p>
                    </div>
                </div>
                {{-- Titik --}}
                <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 flex items-start gap-3 shadow-sm">
                    <span class="hidden sm:flex w-10 h-10 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.14-7.5 11.25-7.5 11.25S4.5 17.64 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-slate-500">Titik tercatat</p>
                        <p class="text-2xl font-bold text-slate-800 tabular-nums">{{ number_format($ringkasan['titik']) }}</p>
                    </div>
                </div>
                {{-- Tanpa SLS (hanya admin) --}}
                @if (auth()->user()->isAdmin())
                <div class="bg-white rounded-2xl border p-4 sm:p-5 flex items-start gap-3 shadow-sm {{ ($ringkasan['tanpa_sls'] ?? 0) > 0 ? 'border-red-200' : 'border-slate-200' }}">
                    <span class="hidden sm:flex w-10 h-10 shrink-0 items-center justify-center rounded-xl {{ ($ringkasan['tanpa_sls'] ?? 0) > 0 ? 'bg-red-50 text-red-600' : 'bg-slate-100 text-slate-500' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                    </span>
                    <div class="min-w-0">
                        <p class="text-xs font-medium text-slate-500">Titik tanpa SLS</p>
                        <p class="text-2xl font-bold tabular-nums {{ ($ringkasan['tanpa_sls'] ?? 0) > 0 ? 'text-red-600' : 'text-slate-800' }}">
                            {{ $ringkasan['tanpa_sls'] === null ? '–' : number_format($ringkasan['tanpa_sls']) }}
                        </p>
                        @if ($ringkasan['tanpa_sls'] === null)
                            <p class="text-[11px] text-slate-400 leading-tight mt-0.5">Hanya dihitung tanpa filter wilayah</p>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            {{-- ================= FILTER ================= --}}
            <form method="GET" action="{{ route('data-lokasi.index') }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5 space-y-4">
                {{-- Pencarian --}}
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari kode atau nama SLS…" class="{{ $input }} ps-9 py-2.5">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 sm:flex-none inline-flex items-center justify-center gap-1.5 px-5 py-2.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold shadow-sm transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4.5h18l-7 8.25V19l-4 1.5v-7.75L3 4.5Z"/></svg>
                            Terapkan
                        </button>
                        @if ($adaFilter)
                            <a href="{{ route('data-lokasi.index') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-600 text-sm font-medium transition">Reset</a>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Kegiatan</label>
                        <select name="kegiatan_id" class="{{ $input }}">
                            <option value="">Semua kegiatan</option>
                            @foreach ($kegiatanList as $k)
                                <option value="{{ $k->id }}" @selected((string) request('kegiatan_id') === (string) $k->id)>{{ $k->nama_kegiatan }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Kecamatan</label>
                        <select name="kecamatan_id" x-model="kecamatan" @change="muatKelurahan()" class="{{ $input }}">
                            <option value="">Semua kecamatan</option>
                            @foreach ($kecamatanList as $kc)
                                <option value="{{ $kc->id }}">{{ $kc->nama_kecamatan }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">
                            Kelurahan
                            <span x-show="loading" x-cloak class="text-slate-400 font-normal">· memuat…</span>
                        </label>
                        <select name="kelurahan_id" x-model="kelurahan" :disabled="! kecamatan" class="{{ $input }} disabled:opacity-60 disabled:cursor-not-allowed">
                            <option value="">Semua kelurahan</option>
                            <template x-for="k in kelurahanList" :key="k.id">
                                <option :value="k.id" x-text="k.nama_kelurahan" :selected="String(k.id) === String(kelurahan)"></option>
                            </template>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Status</label>
                        <select name="status" class="{{ $input }}">
                            <option value="">Semua status</option>
                            <option value="perlu" @selected(request('status') === 'perlu')>Perlu perbaikan</option>
                            <option value="normal" @selected(request('status') === 'normal')>Tidak perlu perbaikan</option>
                        </select>
                    </div>
                </div>

                {{-- Filter aktif --}}
                @if ($adaFilter)
                    <div class="flex flex-wrap items-center gap-2 pt-1">
                        <span class="text-xs text-slate-500">Filter aktif:</span>
                        @if (request()->filled('q'))
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-xs">Cari: “{{ request('q') }}”</span>
                        @endif
                        @if ($kegiatanAktif)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-xs">{{ $kegiatanAktif->nama_kegiatan }}</span>
                        @endif
                        @if ($kecamatanAktif)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-xs">Kec. {{ $kecamatanAktif->nama_kecamatan }}</span>
                        @endif
                        @if ($kelurahanAktif)
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-slate-100 text-slate-700 text-xs">Kel. {{ $kelurahanAktif->nama_kelurahan }}</span>
                        @endif
                        @if (request('status') === 'perlu')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 text-xs">Perlu perbaikan</span>
                        @elseif (request('status') === 'normal')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs">Tidak perlu perbaikan</span>
                        @endif
                    </div>
                @endif
            </form>

            {{-- ================= DAFTAR SLS ================= --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-4 sm:px-5 py-3.5 border-b border-slate-100 flex items-center justify-between gap-3">
                    <h3 class="font-semibold text-slate-800 text-sm">Daftar SLS</h3>
                    <p class="text-xs text-slate-500">
                        @if ($daftar->total() > 0)
                            Menampilkan <span class="font-medium text-slate-700">{{ $daftar->firstItem() }}–{{ $daftar->lastItem() }}</span>
                            dari <span class="font-medium text-slate-700">{{ number_format($daftar->total()) }}</span> SLS
                        @else
                            0 hasil
                        @endif
                    </p>
                </div>

                {{-- Tabel (tablet & desktop) --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50/80 text-slate-500 text-[11px] uppercase tracking-wider">
                            <tr>
                                <th class="ps-5 pe-2 py-3 text-center font-semibold w-12">No</th>
                                <th class="px-4 py-3 text-left font-semibold">SLS</th>
                                <th class="px-4 py-3 text-left font-semibold">Wilayah</th>
                                <th class="px-4 py-3 text-center font-semibold">Titik</th>
                                <th class="px-4 py-3 text-center font-semibold">Di luar SLS</th>
                                <th class="px-4 py-3 text-left font-semibold">Status</th>
                                <th class="ps-4 pe-5 py-3 text-right font-semibold">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($daftar as $s)
                                @php $alasan = $s->alasanPerbaikan(); @endphp
                                <tr class="group hover:bg-teal-50/30 transition align-top">
                                    <td class="ps-5 pe-2 py-4 text-center text-slate-400 tabular-nums">{{ $daftar->firstItem() + $loop->index }}</td>
                                    <td class="px-4 py-4">
                                        <p class="font-medium text-slate-800">{{ $s->nama_sls }}</p>
                                        <p class="mt-0.5 text-xs font-mono text-slate-400">{{ $s->id_sls }}</p>
                                        @if (! request('kegiatan_id') && $s->kegiatan)
                                            <p class="mt-0.5 text-[11px] text-teal-700">{{ $s->kegiatan->nama_kegiatan }}</p>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4">
                                        <p class="text-slate-700">{{ $s->kelurahanMaster->nama_kelurahan ?? '-' }}</p>
                                        <p class="mt-0.5 text-xs text-slate-400">Kec. {{ $s->kecamatanMaster->nama_kecamatan ?? '-' }}</p>
                                    </td>
                                    <td class="px-4 py-4 text-center font-semibold text-slate-700 tabular-nums">{{ number_format($s->jumlah_titik) }}</td>
                                    <td class="px-4 py-4 text-center">
                                        @if ($s->jumlah_di_luar > 0)
                                            <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-0.5 rounded-md bg-amber-50 text-amber-700 font-semibold tabular-nums">{{ number_format($s->jumlah_di_luar) }}</span>
                                        @else
                                            <span class="text-slate-300">0</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 max-w-xs">
                                        @if ($alasan)
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Perlu perbaikan
                                            </span>
                                            <ul class="mt-2 text-xs text-slate-500 space-y-1">
                                                @foreach ($alasan as $a)
                                                    <li class="flex gap-1.5"><span class="text-slate-300">•</span><span>{{ $a }}</span></li>
                                                @endforeach
                                            </ul>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Baik
                                            </span>
                                        @endif
                                    </td>
                                    <td class="ps-4 pe-5 py-4 text-right whitespace-nowrap">
                                        <a href="{{ route('data-lokasi.show', $detailParams($s)) }}"
                                            class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-teal-700 hover:bg-teal-600 hover:text-white hover:border-teal-600 text-xs font-semibold transition">
                                            Detail
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7"/></svg>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-4 py-14">
                                        @include('data-lokasi.partials.kosong', ['adaFilter' => $adaFilter])
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Kartu (mobile) --}}
                <div class="md:hidden divide-y divide-slate-100">
                    @forelse ($daftar as $s)
                        @php $alasan = $s->alasanPerbaikan(); @endphp
                        <a href="{{ route('data-lokasi.show', $detailParams($s)) }}" class="block p-4 active:bg-slate-50">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-semibold text-slate-800 leading-snug">{{ $s->nama_sls }}</p>
                                    <p class="text-xs font-mono text-slate-400 mt-0.5">{{ $s->id_sls }}</p>
                                    @if (! request('kegiatan_id') && $s->kegiatan)
                                        <p class="text-[11px] text-teal-700 mt-0.5">{{ $s->kegiatan->nama_kegiatan }}</p>
                                    @endif
                                </div>
                                @if ($alasan)
                                    <span class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Perbaikan
                                    </span>
                                @else
                                    <span class="shrink-0 inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Baik
                                    </span>
                                @endif
                            </div>
                            <p class="mt-2 text-xs text-slate-500">
                                {{ $s->kelurahanMaster->nama_kelurahan ?? '-' }} · Kec. {{ $s->kecamatanMaster->nama_kecamatan ?? '-' }}
                            </p>
                            <div class="mt-3 flex items-center gap-4 text-xs">
                                <span class="text-slate-500">Titik <b class="text-slate-800 tabular-nums">{{ number_format($s->jumlah_titik) }}</b></span>
                                <span class="text-slate-500">Di luar SLS
                                    <b class="tabular-nums {{ $s->jumlah_di_luar > 0 ? 'text-amber-600' : 'text-slate-800' }}">{{ number_format($s->jumlah_di_luar) }}</b>
                                </span>
                                <svg class="w-4 h-4 text-slate-300 ms-auto" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 5 7 7-7 7"/></svg>
                            </div>
                            @if ($alasan)
                                <ul class="mt-2.5 text-xs text-slate-500 space-y-0.5">
                                    @foreach ($alasan as $a)<li>• {{ $a }}</li>@endforeach
                                </ul>
                            @endif
                        </a>
                    @empty
                        <div class="px-4 py-12">@include('data-lokasi.partials.kosong', ['adaFilter' => $adaFilter])</div>
                    @endforelse
                </div>

                @if ($daftar->hasPages())
                    <div class="px-4 sm:px-5 py-3 border-t border-slate-100 bg-slate-50/50">{{ $daftar->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>