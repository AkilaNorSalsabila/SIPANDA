<x-app-layout>
<div class="p-4 sm:p-6 lg:p-8 max-w-7xl">

    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Progres Mitra</h1>
        <p class="text-sm text-slate-500 mt-1">
            Pantau kerja tiap mitra berdasarkan SLS yang ditugaskan: titik yang sudah di-tag, koreksi, dan sisa perbaikan.
        </p>
    </div>

    @if (! $kegiatan)
        <div class="p-8 bg-white rounded-2xl ring-1 ring-slate-200/70 text-center text-sm text-slate-500">
            Belum ada kegiatan. Buat kegiatan dulu di menu Kegiatan.
        </div>
    @else
        <form method="GET" class="flex flex-wrap items-center gap-3 mb-4">
            <select name="kegiatan" onchange="this.form.submit()"
                class="text-sm rounded-lg border-slate-200 bg-white focus:ring-teal-500 focus:border-teal-500">
                @foreach ($kegiatanList as $k)
                    <option value="{{ $k->id }}" @selected($k->id === $kegiatan->id)>{{ $k->nama_kegiatan }}</option>
                @endforeach
            </select>
            <input type="search" name="q" value="{{ $search }}" placeholder="Cari nama / username mitra…"
                class="text-sm rounded-lg border-slate-200 bg-white focus:ring-teal-500 focus:border-teal-500 w-64">
            <button class="px-3 py-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold">Cari</button>
        </form>

        <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-5">
            <div class="bg-white rounded-xl ring-1 ring-slate-200/70 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Mitra aktif</p>
                <p class="text-2xl font-bold text-slate-900 mt-1">{{ $total['mitra'] }}</p>
            </div>
            <div class="bg-white rounded-xl ring-1 ring-slate-200/70 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Belum mulai</p>
                <p class="text-2xl font-bold text-slate-900 mt-1">{{ $total['belum'] }}</p>
            </div>
            <div class="bg-white rounded-xl ring-1 ring-slate-200/70 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Berjalan / selesai</p>
                <p class="text-2xl font-bold text-slate-900 mt-1">{{ $total['berjalan'] }} <span class="text-slate-300">/</span> {{ $total['selesai'] }}</p>
            </div>
            <div class="bg-white rounded-xl ring-1 ring-slate-200/70 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Titik di-tag</p>
                <p class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($total['titik_tag']) }}</p>
            </div>
            <div class="bg-white rounded-xl ring-1 ring-slate-200/70 p-4">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-slate-500">Koreksi menunggu verifikasi</p>
                <p class="text-2xl font-bold text-slate-900 mt-1">{{ number_format($total['menunggu']) }}</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-slate-200/70 shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-[11px] uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3 text-left font-semibold">Mitra</th>
                        <th class="px-5 py-3 text-left font-semibold min-w-[11rem]">SLS dikerjakan</th>
                        <th class="px-5 py-3 text-left font-semibold">Titik di-tag</th>
                        <th class="px-5 py-3 text-left font-semibold">Koreksi</th>
                        <th class="px-5 py-3 text-left font-semibold">Sisa perbaikan</th>
                        <th class="px-5 py-3 text-left font-semibold">Terakhir aktif</th>
                        <th class="px-5 py-3 text-left font-semibold">Status</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($mitra as $m)
                        <tr class="hover:bg-slate-50/60">
                            <td class="px-5 py-3.5">
                                <p class="font-semibold text-slate-800">{{ $m->name }}</p>
                                <p class="text-xs text-slate-400">&#64;{{ $m->username }}</p>
                            </td>
                            <td class="px-5 py-3.5">
                                @if ($m->sls_ditugaskan)
                                    <p class="text-xs text-slate-600 mb-1">
                                        <b class="text-slate-800">{{ $m->sls_aktif }}</b> dari {{ $m->sls_ditugaskan }} SLS
                                        <span class="text-slate-400">({{ $m->persen_sls }}%)</span>
                                    </p>
                                    @include('admin.progres._bar', ['persen' => $m->persen_sls])
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-slate-700 tabular-nums">{{ number_format($m->titik_tag) }}</td>
                            <td class="px-5 py-3.5 text-xs text-slate-600">
                                <span class="tabular-nums">{{ $m->koreksi_menunggu }}</span> menunggu<br>
                                <span class="tabular-nums text-slate-400">{{ $m->koreksi_terverifikasi }} terverifikasi</span>
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-600">
                                <span class="tabular-nums {{ $m->sisa_perbaikan ? 'font-semibold text-amber-700' : '' }}">{{ number_format($m->sisa_perbaikan) }}</span> titik
                                @if ($m->persen_perbaikan !== null)
                                    <br><span class="text-slate-400">{{ $m->persen_perbaikan }}% sudah diperbaiki</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-xs text-slate-500 whitespace-nowrap tabular-nums">{{ $m->terakhir_label ?? '—' }}</td>
                            <td class="px-5 py-3.5">@include('admin.progres._status', ['status' => $m->status])</td>
                            <td class="px-5 py-3.5 text-right whitespace-nowrap">
                                @if ($m->sls_ditugaskan)
                                    <a href="{{ route('admin.progres.show', [$m->id, 'kegiatan' => $kegiatan->id]) }}"
                                       class="px-3 py-1.5 rounded-lg bg-white ring-1 ring-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition">
                                        Rincian
                                    </a>
                                @else
                                    <a href="{{ route('admin.users.penugasan.tambah', [$m->id, 'kegiatan' => $kegiatan->id]) }}"
                                       class="px-3 py-1.5 rounded-lg bg-teal-600 hover:bg-teal-700 text-white text-xs font-semibold transition">
                                        + Tugaskan SLS
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-10 text-center text-sm text-slate-500">
                                Belum ada mitra (viewer) aktif.
                                <a href="{{ route('admin.users.index', ['status' => 'pending']) }}" class="text-teal-700 font-semibold hover:underline">Setujui pendaftaran di Manajemen Akun</a>.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <p class="mt-3 text-[11px] leading-relaxed text-slate-400 max-w-3xl">
            <b>SLS dikerjakan</b>: SLS ditugaskan yang sudah ada titik hasil tag atau koreksi dari mitra tersebut.
            <b>Sisa perbaikan</b>: titik berstatus "perlu perbaikan" di SLS tugas mitra (siapa pun yang mengerjakan).
            <b>Selesai</b>: semua SLS tugas sudah dikerjakan dan tidak ada sisa perbaikan.
            Sistem belum punya target jumlah titik per SLS, jadi "Selesai" tidak berarti semua bangunan sudah ter-tag.
            Angka mengikuti kegiatan yang dipilih.
        </p>
    @endif
</div>
</x-app-layout>
