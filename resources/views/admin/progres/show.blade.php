<x-app-layout>
<div class="p-4 sm:p-6 lg:p-8 max-w-7xl">

    <a href="{{ route('admin.progres.index', array_filter(['kegiatan' => $kegiatan?->id])) }}" class="text-xs font-semibold text-teal-700 hover:underline">&larr; Kembali ke Progres Mitra</a>

    <div class="mt-2 mb-6 flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Progres {{ $user->name }}</h1>
            <p class="text-sm text-slate-500 mt-1">
                <span class="text-slate-400">&#64;{{ $user->username }}</span>
                — progres per SLS yang ditugaskan.
            </p>
        </div>
        @if ($kegiatan)
            <a href="{{ route('admin.users.penugasan', [$user, 'kegiatan' => $kegiatan->id]) }}"
               class="px-4 py-2.5 rounded-xl bg-white ring-1 ring-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-semibold transition">
                Kelola penugasan
            </a>
        @endif
    </div>

    @if (! $kegiatan)
        <div class="p-8 bg-white rounded-2xl ring-1 ring-slate-200/70 text-center text-sm text-slate-500">
            Belum ada kegiatan. Buat kegiatan dulu di menu Kegiatan.
        </div>
    @else
        <div class="flex flex-wrap items-end gap-3 mb-4">
            <div class="w-72">
                <label class="block text-xs font-semibold text-slate-700 mb-1.5">Kegiatan</label>
                <select onchange="window.location = this.value"
                    class="w-full text-sm rounded-xl border-slate-200 bg-white focus:ring-teal-500 focus:border-teal-500">
                    @foreach ($kegiatanList as $k)
                        <option value="{{ route('admin.progres.show', [$user, 'kegiatan' => $k->id]) }}" @selected($k->id === $kegiatan->id)>{{ $k->nama_kegiatan }}</option>
                    @endforeach
                </select>
            </div>
            <form method="GET" class="flex items-end gap-2">
                <input type="hidden" name="kegiatan" value="{{ $kegiatan->id }}">
                <input type="search" name="q" value="{{ $q }}" placeholder="Cari SLS / wilayah…"
                    class="text-sm rounded-lg border-slate-200 bg-white focus:ring-teal-500 focus:border-teal-500 w-64">
                <button class="px-3 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold">Cari</button>
            </form>
        </div>

        @if ($ringkas && $ringkas->sls_ditugaskan)
            <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-5">
                <div class="bg-white rounded-xl ring-1 ring-slate-200/70 p-4 col-span-2 lg:col-span-2">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">SLS dikerjakan</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">
                        {{ $ringkas->sls_aktif }} <span class="text-base font-semibold text-slate-400">dari {{ $ringkas->sls_ditugaskan }} SLS ({{ $ringkas->persen_sls }}%)</span>
                    </p>
                    <div class="mt-2">@include('admin.progres._bar', ['persen' => $ringkas->persen_sls])</div>
                </div>
                <div class="bg-white rounded-xl ring-1 ring-slate-200/70 p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Titik di-tag</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($ringkas->titik_tag) }}</p>
                </div>
                <div class="bg-white rounded-xl ring-1 ring-slate-200/70 p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Koreksi</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">{{ $ringkas->koreksi_menunggu + $ringkas->koreksi_terverifikasi }}</p>
                    <p class="text-[11px] text-slate-400">{{ $ringkas->koreksi_menunggu }} menunggu verifikasi</p>
                </div>
                <div class="bg-white rounded-xl ring-1 ring-slate-200/70 p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Sisa perbaikan</p>
                    <p class="text-2xl font-bold {{ $ringkas->sisa_perbaikan ? 'text-amber-700' : 'text-slate-900' }} mt-1">{{ number_format($ringkas->sisa_perbaikan) }}</p>
                    <p class="text-[11px] text-slate-400">Terakhir aktif: {{ $ringkas->terakhir_label ?? '—' }}</p>
                </div>
            </div>
        @endif

        <div class="bg-white rounded-2xl ring-1 ring-slate-200/70 shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">SLS</th>
                        <th class="px-5 py-3 text-left font-semibold">Wilayah</th>
                        <th class="px-5 py-3 text-left font-semibold">Total titik</th>
                        <th class="px-5 py-3 text-left font-semibold">Di-tag mitra</th>
                        <th class="px-5 py-3 text-left font-semibold">Koreksi mitra</th>
                        <th class="px-5 py-3 text-left font-semibold">Sisa perbaikan</th>
                        <th class="px-5 py-3 text-left font-semibold">Terakhir aktif</th>
                        <th class="px-5 py-3 text-left font-semibold">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($daftar as $r)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-5 py-3.5">
                                <p class="font-semibold text-slate-800">{{ $r->id_sls }}</p>
                                <p class="text-xs text-slate-400">{{ $r->nama_sls }}</p>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-500">{{ $r->desa_kelurahan }}<br>{{ $r->kecamatan }}</td>
                            <td class="px-5 py-3.5 text-slate-700 tabular-nums">{{ number_format($r->total_titik) }}</td>
                            <td class="px-5 py-3.5 text-slate-700 tabular-nums">{{ number_format($r->titik_tag) }}</td>
                            <td class="px-5 py-3.5 text-xs text-slate-600">
                                <span class="tabular-nums">{{ $r->koreksi_menunggu }}</span> menunggu<br>
                                <span class="tabular-nums text-slate-400">{{ $r->koreksi_terverifikasi }} terverifikasi</span>
                            </td>
                            <td class="px-5 py-3.5 tabular-nums {{ $r->sisa_perbaikan ? 'font-semibold text-amber-700' : 'text-slate-500' }}">{{ number_format($r->sisa_perbaikan) }}</td>
                            <td class="px-5 py-3.5 text-xs text-slate-500 whitespace-nowrap tabular-nums">{{ $r->terakhir_label ?? '—' }}</td>
                            <td class="px-5 py-3.5">@include('admin.progres._status', ['status' => $r->status])</td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                <a href="{{ route('peta.index', ['kegiatan' => $kegiatan->id, 'sls' => $r->sls_id]) }}"
                                   class="px-3 py-1.5 rounded-lg bg-white ring-1 ring-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition">
                                    Lihat di peta
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-10 text-center text-sm text-slate-500">
                                {{ $q !== '' ? 'Tidak ada SLS yang cocok dengan pencarian.' : 'Mitra ini belum ditugaskan ke SLS mana pun pada kegiatan ini.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($daftar && $daftar->hasPages())
            <div class="mt-4">{{ $daftar->links() }}</div>
        @endif
    @endif
</div>
</x-app-layout>
