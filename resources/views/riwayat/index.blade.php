<x-app-layout>
    <x-slot name="header">
        <div class="min-w-0">
            <h2 class="font-semibold text-xl text-slate-800 leading-tight">Riwayat Aktivitas</h2>
            <p class="hidden sm:block text-xs text-slate-500 mt-0.5">Jejak perubahan data dan akun: siapa, kapan, dan apa yang diubah</p>
        </div>
    </x-slot>

    @php
        $adaFilter = request()->filled('modul') || request()->filled('aksi') || request()->filled('user_id')
            || request()->filled('dari') || request()->filled('sampai') || request()->filled('q');
        $input = 'w-full text-sm rounded-lg border-slate-200 bg-slate-50/60 hover:bg-white focus:bg-white focus:ring-2 focus:ring-teal-500/30 focus:border-teal-500 transition';
    @endphp

    <div class="py-6 sm:py-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-5">

            {{-- ================= RINGKASAN ================= --}}
            <div class="grid grid-cols-3 gap-3 sm:gap-4">
                <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Hari ini</p>
                    <p class="text-2xl font-bold text-slate-800 tabular-nums">{{ number_format($ringkasan['hari_ini']) }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">7 hari terakhir</p>
                    <p class="text-2xl font-bold text-slate-800 tabular-nums">{{ number_format($ringkasan['tujuh_hari']) }}</p>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200 p-4 sm:p-5 shadow-sm">
                    <p class="text-xs font-medium text-slate-500">Seluruhnya</p>
                    <p class="text-2xl font-bold text-slate-800 tabular-nums">{{ number_format($ringkasan['total']) }}</p>
                </div>
            </div>

            {{-- ================= FILTER ================= --}}
            <form method="GET" action="{{ route('riwayat.index') }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 sm:p-5 space-y-4">
                <div class="flex flex-col sm:flex-row gap-3">
                    <div class="relative flex-1">
                        <svg class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 10.5a6.5 6.5 0 1 1-13 0 6.5 6.5 0 0 1 13 0Z"/></svg>
                        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari keterangan, nama data, atau nama pengguna…" class="{{ $input }} ps-9 py-2.5">
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 sm:flex-none px-5 py-2.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold shadow-sm transition">Terapkan</button>
                        @if ($adaFilter)
                            <a href="{{ route('riwayat.index') }}" class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-600 text-sm font-medium transition">Reset</a>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Modul</label>
                        <select name="modul" class="{{ $input }}">
                            <option value="">Semua modul</option>
                            @foreach (\App\Models\Riwayat::MODUL as $kode => $nama)
                                <option value="{{ $kode }}" @selected(request('modul') === $kode)>{{ $nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Aksi</label>
                        <select name="aksi" class="{{ $input }}">
                            <option value="">Semua aksi</option>
                            @foreach (\App\Models\Riwayat::AKSI as $kode => $nama)
                                <option value="{{ $kode }}" @selected(request('aksi') === $kode)>{{ $nama }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Pengguna</label>
                        <select name="user_id" class="{{ $input }}">
                            <option value="">Semua pengguna</option>
                            @foreach ($penggunaList as $p)
                                <option value="{{ $p->user_id }}" @selected((string) request('user_id') === (string) $p->user_id)>{{ $p->nama_user }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Dari tanggal</label>
                        <input type="date" name="dari" value="{{ request('dari') }}" class="{{ $input }}">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-slate-600 mb-1.5">Sampai tanggal</label>
                        <input type="date" name="sampai" value="{{ request('sampai') }}" class="{{ $input }}">
                    </div>
                </div>
                @error('dari')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                @error('sampai')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
            </form>

            {{-- ================= DAFTAR RIWAYAT ================= --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-4 sm:px-5 py-3.5 border-b border-slate-100 flex items-center justify-between gap-3">
                    <h3 class="font-semibold text-slate-800 text-sm">Catatan aktivitas</h3>
                    <p class="text-xs text-slate-500">
                        @if ($daftar->total() > 0)
                            Menampilkan <span class="font-medium text-slate-700">{{ $daftar->firstItem() }}–{{ $daftar->lastItem() }}</span>
                            dari <span class="font-medium text-slate-700">{{ number_format($daftar->total()) }}</span>
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
                                <th class="ps-5 pe-4 py-3 text-left font-semibold whitespace-nowrap">Waktu</th>
                                <th class="px-4 py-3 text-left font-semibold">Pengguna</th>
                                <th class="px-4 py-3 text-left font-semibold">Aksi</th>
                                <th class="px-4 py-3 text-left font-semibold">Modul</th>
                                <th class="ps-4 pe-5 py-3 text-left font-semibold">Keterangan</th>
                            </tr>
                        </thead>
                        @forelse ($daftar as $r)
                            <tbody x-data="{ buka: false }" class="border-t border-slate-100 first:border-t-0">
                                <tr class="hover:bg-teal-50/30 transition align-top">
                                    <td class="ps-5 pe-4 py-3.5 whitespace-nowrap">
                                        <p class="text-slate-700 tabular-nums">{{ $r->created_at->format('d/m/Y') }}</p>
                                        <p class="text-xs text-slate-400 tabular-nums">{{ $r->created_at->format('H:i:s') }}</p>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <p class="font-medium text-slate-800">{{ $r->nama_user ?? '-' }}</p>
                                        @if ($r->ip_address)<p class="text-[11px] text-slate-400 font-mono">{{ $r->ip_address }}</p>@endif
                                    </td>
                                    <td class="px-4 py-3.5">@include('riwayat.partials.badge-aksi', ['aksi' => $r->aksi])</td>
                                    <td class="px-4 py-3.5 text-slate-600 whitespace-nowrap">{{ \App\Models\Riwayat::MODUL[$r->modul] ?? $r->modul }}</td>
                                    <td class="ps-4 pe-5 py-3.5 max-w-md">
                                        <p class="text-slate-700">{{ $r->keterangan }}</p>
                                        @if ($r->objek_label)<p class="mt-0.5 text-xs text-slate-400">{{ $r->objek_label }}</p>@endif
                                        @if ($r->data_lama || $r->data_baru)
                                            <button type="button" @click="buka = ! buka" class="mt-1.5 inline-flex items-center gap-1 text-xs font-semibold text-teal-700 hover:underline">
                                                <span x-text="buka ? 'Sembunyikan rincian' : 'Lihat rincian'"></span>
                                                <svg class="w-3.5 h-3.5 transition" :class="buka && 'rotate-180'" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m19 9-7 7-7-7"/></svg>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                                @if ($r->data_lama || $r->data_baru)
                                    <tr x-show="buka" x-cloak>
                                        <td colspan="5" class="px-5 pb-4 pt-0">
                                            <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                                                @include('riwayat.partials.perubahan', ['r' => $r])
                                            </div>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        @empty
                            <tbody>
                                <tr><td colspan="5" class="px-4 py-14">@include('riwayat.partials.kosong', ['adaFilter' => $adaFilter])</td></tr>
                            </tbody>
                        @endforelse
                    </table>
                </div>

                {{-- Kartu (mobile) --}}
                <div class="md:hidden divide-y divide-slate-100">
                    @forelse ($daftar as $r)
                        <div class="p-4" x-data="{ buka: false }">
                            <div class="flex items-center justify-between gap-3">
                                @include('riwayat.partials.badge-aksi', ['aksi' => $r->aksi])
                                <p class="text-xs text-slate-400 tabular-nums">{{ $r->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                            <p class="mt-2 text-sm text-slate-800">{{ $r->keterangan }}</p>
                            @if ($r->objek_label)<p class="mt-0.5 text-xs text-slate-400">{{ $r->objek_label }}</p>@endif
                            <p class="mt-2 text-xs text-slate-500">
                                oleh <span class="font-medium text-slate-700">{{ $r->nama_user ?? '-' }}</span>
                                · {{ \App\Models\Riwayat::MODUL[$r->modul] ?? $r->modul }}
                            </p>
                            @if ($r->data_lama || $r->data_baru)
                                <button type="button" @click="buka = ! buka" class="mt-2 text-xs font-semibold text-teal-700">
                                    <span x-text="buka ? 'Sembunyikan rincian' : 'Lihat rincian'"></span>
                                </button>
                                <div x-show="buka" x-cloak class="mt-2 p-3 rounded-xl bg-slate-50 border border-slate-200">
                                    @include('riwayat.partials.perubahan', ['r' => $r])
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="px-4 py-12">@include('riwayat.partials.kosong', ['adaFilter' => $adaFilter])</div>
                    @endforelse
                </div>

                @if ($daftar->hasPages())
                    <div class="px-4 sm:px-5 py-3 border-t border-slate-100 bg-slate-50/50">{{ $daftar->links() }}</div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
