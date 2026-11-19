<x-app-layout>
    <x-slot name="header">
        <div class="min-w-0">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">Detail SLS</h2>
            <p class="hidden sm:block text-xs text-slate-500 mt-0.5">Status perbaikan dan daftar titik pada SLS ini</p>
        </div>
    </x-slot>

    @php
        $alasan = $sls->alasanPerbaikan();
        $input  = 'w-full text-sm rounded-lg border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition';
        $persenLuar = $sls->jumlah_titik > 0 ? round($sls->jumlah_di_luar / $sls->jumlah_titik * 100) : 0;
    @endphp

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            @if (session('status'))
                <div class="flex items-center gap-2 p-3 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm rounded-xl">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                    {{ session('status') }}
                </div>
            @endif

            {{-- Breadcrumb --}}
            <nav class="flex items-center gap-1.5 text-sm text-slate-500">
                <a href="{{ route('data-lokasi.index', array_filter(['kegiatan_id' => $kegiatanId])) }}" class="inline-flex items-center gap-1 hover:text-teal-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m15 19-7-7 7-7"/></svg>
                    Data Lokasi
                </a>
                <span class="text-slate-300">/</span>
                <span class="text-slate-700 font-medium truncate">{{ $sls->nama_sls }}</span>
            </nav>

            {{-- ================= INFO SLS ================= --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="p-5 sm:p-6 flex flex-wrap items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="inline-block px-2 py-0.5 rounded-md bg-slate-100 text-xs font-mono text-slate-500">{{ $sls->id_sls }}</p>
                        <h3 class="mt-2 text-xl font-bold text-slate-800 leading-snug">{{ $sls->nama_sls }}</h3>
                        <p class="mt-1 flex items-center gap-1.5 text-sm text-slate-500">
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.14-7.5 11.25-7.5 11.25S4.5 17.64 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
                            {{ $sls->kelurahanMaster->nama_kelurahan ?? '-' }}, Kec. {{ $sls->kecamatanMaster->nama_kecamatan ?? '-' }}
                        </p>
                    </div>

                    @if ($alasan)
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>Perlu perbaikan
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>Tidak perlu perbaikan
                        </span>
                    @endif
                </div>

                {{-- Statistik --}}
                <dl class="grid grid-cols-3 divide-x divide-slate-100 border-t border-slate-100 bg-slate-50/50">
                    <div class="px-4 sm:px-6 py-4">
                        <dt class="text-xs font-medium text-slate-500">Titik</dt>
                        <dd class="mt-1 text-xl font-bold text-slate-800 tabular-nums">{{ number_format($sls->jumlah_titik) }}</dd>
                    </div>
                    <div class="px-4 sm:px-6 py-4">
                        <dt class="text-xs font-medium text-slate-500">Di luar SLS</dt>
                        <dd class="mt-1 flex items-baseline gap-1.5">
                            <span class="text-xl font-bold tabular-nums {{ $sls->jumlah_di_luar > 0 ? 'text-amber-600' : 'text-slate-800' }}">{{ number_format($sls->jumlah_di_luar) }}</span>
                            @if ($sls->jumlah_di_luar > 0)<span class="text-xs text-slate-400">({{ $persenLuar }}%)</span>@endif
                        </dd>
                    </div>
                    <div class="px-4 sm:px-6 py-4">
                        <dt class="text-xs font-medium text-slate-500">Batas polygon</dt>
                        <dd class="mt-1">
                            @if ($sls->punya_polygon)
                                <span class="inline-flex items-center gap-1 text-sm font-semibold text-emerald-700">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>Ada
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-sm font-semibold text-amber-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>Belum ada
                                </span>
                            @endif
                        </dd>
                    </div>
                </dl>

                {{-- Alasan perbaikan --}}
                @if ($alasan)
                    <div class="mx-5 sm:mx-6 mt-5 p-4 rounded-xl bg-amber-50/70 border border-amber-200">
                        <p class="text-xs font-semibold text-amber-800 mb-2">Alasan perlu perbaikan</p>
                        <ul class="space-y-1.5 text-sm text-amber-900/80">
                            @foreach ($alasan as $a)
                                <li class="flex gap-2">
                                    <svg class="w-4 h-4 mt-0.5 shrink-0 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
                                    <span>{{ $a }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Solusi perbaikan --}}
                <div class="p-5 sm:p-6">
                    @if (auth()->user()->isAdmin())
                        <form method="POST" action="{{ route('data-lokasi.update', $sls->id) }}" class="space-y-4">
                            @csrf
                            @method('PATCH')

                            <div class="flex items-center justify-between gap-4 p-3.5 rounded-xl border border-slate-200 bg-slate-50/60">
                                <div>
                                    <p class="text-sm font-medium text-slate-800">Tandai perlu perbaikan</p>
                                    <p class="text-xs text-slate-500 mt-0.5">Penandaan manual oleh admin, di luar pengecekan otomatis.</p>
                                </div>
                                <label class="relative inline-flex items-center cursor-pointer shrink-0">
                                    <input type="hidden" name="perlu_perbaikan" value="0">
                                    <input type="checkbox" name="perlu_perbaikan" value="1" class="sr-only peer" @checked(old('perlu_perbaikan', $sls->perlu_perbaikan))>
                                    <span class="w-11 h-6 rounded-full bg-slate-300 peer-checked:bg-amber-500 peer-focus-visible:ring-2 peer-focus-visible:ring-teal-500/40 transition"></span>
                                    <span class="absolute left-0.5 top-0.5 w-5 h-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></span>
                                </label>
                            </div>

                            <div>
                                <label for="solusi_perbaikan" class="block text-xs font-medium text-slate-600 mb-1.5">Solusi / catatan perbaikan</label>
                                <textarea id="solusi_perbaikan" name="solusi_perbaikan" rows="3" maxlength="2000"
                                    placeholder="Contoh: Titik digeser ke dalam batas SLS, atau batas SLS perlu diimpor ulang."
                                    class="{{ $input }}">{{ old('solusi_perbaikan', $sls->solusi_perbaikan) }}</textarea>
                                @error('solusi_perbaikan')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>

                            <div class="flex justify-end">
                                <button type="submit" class="inline-flex items-center gap-1.5 px-5 py-2.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold shadow-sm transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                                    Simpan
                                </button>
                            </div>
                        </form>
                    @elseif ($sls->solusi_perbaikan)
                        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                            <p class="text-xs font-semibold text-slate-700 mb-1">Solusi / catatan perbaikan</p>
                            <p class="text-sm text-slate-600 whitespace-pre-line">{{ $sls->solusi_perbaikan }}</p>
                        </div>
                    @else
                        <p class="text-sm text-slate-400">Belum ada catatan perbaikan.</p>
                    @endif
                </div>
            </div>

            {{-- ================= DAFTAR TITIK ================= --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-4 sm:px-5 py-3.5 border-b border-slate-100 flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h3 class="font-semibold text-slate-800 text-sm">Titik di SLS ini</h3>
                        <p class="text-xs text-slate-500 mt-0.5">
                            @if ($titik->total() > 0)
                                Menampilkan {{ $titik->firstItem() }}–{{ $titik->lastItem() }} dari {{ number_format($titik->total()) }} titik
                            @else
                                Belum ada titik
                            @endif
                        </p>
                    </div>

                    <form method="GET" action="{{ route('data-lokasi.show', $sls->id) }}" class="w-full sm:w-56">
                        <select name="kegiatan_id" onchange="this.form.submit()" class="{{ $input }}">
                            <option value="">Semua kegiatan</option>
                            @foreach ($kegiatanList as $k)
                                <option value="{{ $k->id }}" @selected((int) $kegiatanId === $k->id)>{{ $k->nama_kegiatan }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>

                {{-- Tabel (tablet & desktop) --}}
                <div class="hidden md:block overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50/80 text-slate-500 text-[11px] uppercase tracking-wider">
                            <tr>
                                <th class="ps-5 pe-4 py-3 text-left font-semibold">ID Assignment</th>
                                <th class="px-4 py-3 text-left font-semibold">No. Bangunan</th>
                                <th class="px-4 py-3 text-left font-semibold">Kegiatan</th>
                                <th class="px-4 py-3 text-left font-semibold">Koordinat</th>
                                <th class="px-4 py-3 text-left font-semibold">Posisi terhadap SLS</th>
                                <th class="ps-4 pe-5 py-3 text-left font-semibold">Status perbaikan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($titik as $t)
                                <tr class="hover:bg-teal-50/30 transition">
                                    <td class="ps-5 pe-4 py-3 whitespace-nowrap font-mono text-xs text-slate-700">{{ $t->id_assignment }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $t->nomor_bangunan ?? '-' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $t->kegiatan->nama_kegiatan ?? '-' }}</td>
                                    <td class="px-4 py-3 whitespace-nowrap">
                                        @if ($t->latitude !== null && $t->longitude !== null)
                                            <a href="https://www.google.com/maps?q={{ $t->latitude }},{{ $t->longitude }}" target="_blank" rel="noopener"
                                                class="inline-flex items-center gap-1 font-mono text-xs text-slate-600 hover:text-teal-700 transition" title="Buka di Google Maps">
                                                {{ $t->latitude }}, {{ $t->longitude }}
                                                <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H18m0 0v4.5M18 6l-7.5 7.5M10.5 4.5H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25h10.5a2.25 2.25 0 0 0 2.25-2.25V13.5"/></svg>
                                            </a>
                                        @else
                                            <span class="text-slate-300">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        @include('data-lokasi.partials.posisi', ['t' => $t])
                                    </td>
                                    <td class="ps-4 pe-5 py-3">
                                        @include('data-lokasi.partials.status-perbaikan', ['t' => $t])
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="px-4 py-12 text-center text-sm text-slate-400">Belum ada titik di SLS ini.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Kartu (mobile) --}}
                <div class="md:hidden divide-y divide-slate-100">
                    @forelse ($titik as $t)
                        <div class="p-4">
                            <div class="flex items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <p class="font-mono text-xs text-slate-700 break-all">{{ $t->id_assignment }}</p>
                                    <p class="text-xs text-slate-500 mt-0.5">No. {{ $t->nomor_bangunan ?? '-' }} · {{ $t->kegiatan->nama_kegiatan ?? '-' }}</p>
                                </div>
                                <div class="shrink-0 flex flex-col items-end gap-1">
                                    @include('data-lokasi.partials.posisi', ['t' => $t])
                                    @include('data-lokasi.partials.status-perbaikan', ['t' => $t])
                                </div>
                            </div>
                            @if ($t->latitude !== null && $t->longitude !== null)
                                <a href="https://www.google.com/maps?q={{ $t->latitude }},{{ $t->longitude }}" target="_blank" rel="noopener"
                                    class="mt-2 inline-flex items-center gap-1 font-mono text-xs text-teal-700">
                                    {{ $t->latitude }}, {{ $t->longitude }}
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H18m0 0v4.5M18 6l-7.5 7.5M10.5 4.5H6.75A2.25 2.25 0 0 0 4.5 6.75v10.5a2.25 2.25 0 0 0 2.25 2.25h10.5a2.25 2.25 0 0 0 2.25-2.25V13.5"/></svg>
                                </a>
                            @endif
                        </div>
                    @empty
                        <p class="px-4 py-10 text-center text-sm text-slate-400">Belum ada titik di SLS ini.</p>
                    @endforelse
                </div>

                @if ($titik->hasPages())
                    <div class="px-4 sm:px-5 py-3 border-t border-slate-100 bg-slate-50/50">{{ $titik->links() }}</div>
                @endif
            </div>

            {{-- ================= RIWAYAT PERUBAHAN (khusus admin) ================= --}}
            @if (auth()->user()->isAdmin())
                @include('data-lokasi.partials.riwayat-sls', ['riwayat' => $riwayat])
            @endif
        </div>
    </div>
</x-app-layout>
